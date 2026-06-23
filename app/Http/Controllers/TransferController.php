<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Services\SpotTradingService;
use Illuminate\Http\Request;

/**
 * Within-account transfer between the Mutual Fund and the Spot wallet.
 * Single USD base: both sides are USD, so a transfer is just moving USD.
 * Mutual Fund -> Spot can only move profit (not locked capital).
 */
class TransferController extends Controller
{
    public function __construct(private SpotTradingService $spot) {}

    public function create(Request $request)
    {
        $user = $request->user();
        $account = $user->currentAccount();
        $spotUsd = (float) $this->spot->account($user->id, 'USD')->balance;
        $mfWithdrawable = $account ? $account->availableToWithdraw() : 0.0;

        return view('client.transfer.create', compact('account', 'spotUsd', 'mfWithdrawable'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'direction' => ['required', 'in:mf_to_spot,spot_to_mf'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $user = $request->user();
        $account = $user->currentAccount();
        if (! $account) {
            return back()->with('status', 'No mutual fund account found.');
        }
        $amount = round((float) $data['amount'], 2);

        if ($data['direction'] === 'mf_to_spot') {
            $max = $account->availableToWithdraw();
            if ($amount > $max + 0.001) {
                return back()->with('status', 'You can only move mutual-fund profit (max $' . number_format($max, 2) . ').');
            }

            // Reduce MF (approved withdrawal record + ledger entry), credit Spot.
            $last = Transaction::where('fund_account_id', $account->id)->latest('id')->first();
            $tx = Transaction::create([
                'user_id' => $user->id, 'fund_account_id' => $account->id, 'type' => 'withdrawal',
                'amount' => -$amount, 'currency' => 'USD',
                'balance_after' => round((float) ($last->balance_after ?? 0) - $amount, 2),
                'status' => 'completed', 'description' => 'Transfer to Spot wallet',
            ]);
            $wd = Withdrawal::create([
                'user_id' => $user->id, 'fund_account_id' => $account->id, 'purpose' => 'fund',
                'amount' => $amount, 'currency' => 'USD', 'method' => 'Internal transfer to Spot',
                'status' => 'approved', 'processed_at' => now(),
            ]);
            $tx->update(['source_type' => Withdrawal::class, 'source_id' => $wd->id]);

            $this->spot->adjustBalance($user->id, $amount, 'USD');

            return redirect()->route('spot.index')->with('status', '$' . number_format($amount, 2) . ' moved to your Spot wallet.');
        }

        // spot_to_mf
        $spotBal = (float) $this->spot->account($user->id, 'USD')->balance;
        if ($amount > $spotBal + 0.001) {
            return back()->with('status', 'Insufficient Spot balance (you have $' . number_format($spotBal, 2) . ').');
        }

        $this->spot->adjustBalance($user->id, -$amount, 'USD');

        $last = Transaction::where('fund_account_id', $account->id)->latest('id')->first();
        $tx = Transaction::create([
            'user_id' => $user->id, 'fund_account_id' => $account->id, 'type' => 'deposit',
            'amount' => $amount, 'currency' => 'USD',
            'balance_after' => round((float) ($last->balance_after ?? 0) + $amount, 2),
            'status' => 'completed', 'description' => 'Transfer from Spot wallet',
        ]);
        $dep = Deposit::create([
            'user_id' => $user->id, 'fund_account_id' => $account->id,
            'pool_account_id' => $account->pool_account_id, 'account_type_id' => $account->account_type_id,
            'amount' => $amount, 'currency' => 'USD', 'status' => 'approved',
            'value_date' => now()->toDateString(), 'approved_at' => now(),
        ]);
        $tx->update(['source_type' => Deposit::class, 'source_id' => $dep->id]);
        $account->recalcPlan();

        return redirect()->route('client.dashboard')->with('status', '$' . number_format($amount, 2) . ' moved to your Mutual Fund.');
    }
}
