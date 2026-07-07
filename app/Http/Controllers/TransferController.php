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
        $spotProfit = $this->spotProfit($user->id);

        return view('client.transfer.create', compact('account', 'spotUsd', 'mfWithdrawable', 'spotProfit'));
    }

    /** Realized spot profit = wallet balance − net capital deposited into spot. */
    private function spotProfit(int $userId): float
    {
        $bal = (float) $this->spot->account($userId, 'USD')->balance;
        $dep = (float) Deposit::where('user_id', $userId)->where('purpose', 'spot')->where('status', 'approved')
            ->get(['amount', 'currency', 'usd_amount'])->sum(fn ($d) => $d->usd_amount !== null ? (float) $d->usd_amount : $this->spot->toUsd((float) $d->amount, $d->currency));
        $wd = (float) Withdrawal::where('user_id', $userId)->where('purpose', 'spot')->where('status', 'approved')
            ->get(['amount', 'currency', 'usd_amount'])->sum(fn ($w) => $w->usd_amount !== null ? (float) $w->usd_amount : $this->spot->toUsd((float) $w->amount, $w->currency));

        return max(0, round($bal - ($dep - $wd), 2));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'direction' => ['required', 'in:mf_to_spot,spot_to_mf,mf_reinvest,spot_reinvest'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $user = $request->user();
        $account = $user->currentAccount();
        if (! $account) {
            return back()->with('status', 'No mutual fund account found.');
        }
        $amount = round((float) $data['amount'], 2);

        // Reinvest MF profit -> MF capital (compound): profit becomes locked principal; net balance unchanged.
        if ($data['direction'] === 'mf_reinvest') {
            $max = $account->availableToWithdraw();
            if ($amount > $max + 0.001) {
                return back()->with('status', 'You can only reinvest mutual-fund profit (max $' . number_format($max, 2) . ').');
            }
            $base = (float) (Transaction::where('fund_account_id', $account->id)->latest('id')->value('balance_after') ?? 0);
            $wtx = Transaction::create(['user_id' => $user->id, 'fund_account_id' => $account->id, 'type' => 'withdrawal',
                'amount' => -$amount, 'currency' => 'USD', 'balance_after' => round($base - $amount, 2),
                'status' => 'completed', 'description' => 'Reinvested profit to capital']);
            $wd = Withdrawal::create(['user_id' => $user->id, 'fund_account_id' => $account->id, 'purpose' => 'fund',
                'amount' => $amount, 'currency' => 'USD', 'method' => 'Reinvest profit to capital', 'status' => 'approved', 'processed_at' => now()]);
            $wtx->update(['source_type' => Withdrawal::class, 'source_id' => $wd->id]);
            $dtx = Transaction::create(['user_id' => $user->id, 'fund_account_id' => $account->id, 'type' => 'deposit',
                'amount' => $amount, 'currency' => 'USD', 'balance_after' => round($base, 2),
                'status' => 'completed', 'description' => 'Reinvested profit to capital']);
            $dep = Deposit::create(['user_id' => $user->id, 'fund_account_id' => $account->id,
                'pool_account_id' => $account->pool_account_id, 'account_type_id' => $account->account_type_id,
                'amount' => $amount, 'currency' => 'USD', 'status' => 'approved', 'value_date' => now()->toDateString(), 'approved_at' => now()]);
            $dtx->update(['source_type' => Deposit::class, 'source_id' => $dep->id]);
            $account->recalcPlan();

            return back()->with('status', '$' . number_format($amount, 2) . ' profit reinvested into your Mutual Fund capital.');
        }

        // Reinvest Spot profit -> Spot capital (lock in gains): money stays in the wallet, now counted as capital.
        if ($data['direction'] === 'spot_reinvest') {
            $max = $this->spotProfit($user->id);
            if ($amount > $max + 0.001) {
                return back()->with('status', 'You can only reinvest spot profit (max $' . number_format($max, 2) . ').');
            }
            Deposit::create(['user_id' => $user->id, 'purpose' => 'spot', 'currency' => 'USD',
                'amount' => $amount, 'usd_amount' => $amount, 'method' => 'Reinvest profit to capital',
                'status' => 'approved', 'value_date' => now()->toDateString(), 'approved_at' => now()]);

            return redirect()->route('spot.index')->with('status', '$' . number_format($amount, 2) . ' spot profit locked into capital.');
        }

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
