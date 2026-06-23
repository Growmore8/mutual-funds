<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Models\WithdrawalMethod;
use App\Services\SpotTradingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WithdrawalController extends Controller
{
    /** Spot is profit-only: withdrawable = wallet balance minus net deposited capital (that currency). */
    private function spotProfitAvailable(int $userId, string $currency): float
    {
        $balance = (float) app(SpotTradingService::class)->account($userId, $currency)->balance;
        $deposited = (float) Deposit::where('user_id', $userId)->where('purpose', 'spot')->where('currency', $currency)->where('status', 'approved')->sum('amount');
        $withdrawn = (float) Withdrawal::where('user_id', $userId)->where('purpose', 'spot')->where('currency', $currency)->where('status', 'approved')->sum('amount');

        return max(0, round($balance - ($deposited - $withdrawn), 2));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $purpose = $request->get('for') === 'spot' ? 'spot' : 'fund';
        $currency = $purpose === 'spot' && strtoupper($request->get('cur', 'USD')) === 'INR' ? 'INR' : 'USD';

        if ($purpose === 'spot') {
            $available = $this->spotProfitAvailable($user->id, $currency);
            $withdrawals = Withdrawal::where('user_id', $user->id)->where('purpose', 'spot')->latest()->limit(10)->get();
        } else {
            $account = $user->currentAccount();
            $available = $account ? $account->availableToWithdraw() : 0.0;
            $withdrawals = $account ? $account->withdrawals()->where('purpose', 'fund')->latest()->limit(10)->get() : collect();
        }

        return view('client.withdraw.create', [
            'available' => $available,
            'payoutMethods' => $user->withdrawalMethods,
            'withdrawals' => $withdrawals,
            'purpose' => $purpose,
            'currency' => $currency,
            'usdInr' => app(SpotTradingService::class)->usdInr(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'withdrawal_method_id' => ['required', 'exists:withdrawal_methods,id'],
            'purpose' => ['nullable', 'in:fund,spot'],
            'currency' => ['nullable', 'in:USD,INR'],
        ]);

        $purpose = $data['purpose'] ?? 'fund';
        $currency = $purpose === 'spot' && ($data['currency'] ?? 'USD') === 'INR' ? 'INR' : 'USD';
        $account = $user->currentAccount();

        $available = $purpose === 'spot'
            ? $this->spotProfitAvailable($user->id, $currency)
            : ($account ? $account->availableToWithdraw() : 0.0);

        $method = WithdrawalMethod::where('id', $data['withdrawal_method_id'])->where('user_id', $user->id)->first();
        if (! $method) {
            throw ValidationException::withMessages(['withdrawal_method_id' => 'Please choose one of your saved payout methods.']);
        }

        if ($data['amount'] > $available) {
            throw ValidationException::withMessages([
                'amount' => 'You can withdraw at most $' . number_format($available, 2) . ($purpose === 'spot' ? ' from your Spot balance.' : ' (profit only).'),
            ]);
        }

        Withdrawal::create([
            'user_id' => $user->id,
            'fund_account_id' => $purpose === 'spot' ? null : $account?->id,
            'amount' => $data['amount'],
            'currency' => $currency,
            'method' => $method->title(),
            'withdrawal_method_id' => $method->id,
            'payout_details' => $method->summary(),
            'status' => 'pending',
            'purpose' => $purpose,
        ]);

        \App\Models\AppNotification::notifyAdmins(
            'withdrawal',
            'New ' . ($purpose === 'spot' ? 'Spot' : 'Mutual Fund') . ' withdrawal request',
            $user->name . ' · $' . number_format((float) $data['amount'], 2) . ' · ' . $method->title(),
            route('admin.withdrawals.index'),
        );

        return redirect()->route('withdraw.create', $purpose === 'spot' ? ['for' => 'spot'] : [])
            ->with('status', 'Withdrawal request submitted. Our team will review it shortly.');
    }
}
