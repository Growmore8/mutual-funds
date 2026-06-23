<?php

namespace App\Http\Controllers;

use App\Models\Withdrawal;
use App\Models\WithdrawalMethod;
use App\Services\SpotTradingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WithdrawalController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();
        $purpose = $request->get('for') === 'spot' ? 'spot' : 'fund';
        $currency = $purpose === 'spot' && strtoupper($request->get('cur', 'USD')) === 'INR' ? 'INR' : 'USD';

        if ($purpose === 'spot') {
            $available = (float) app(SpotTradingService::class)->account($user->id, $currency)->balance;
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
            ? (float) app(SpotTradingService::class)->account($user->id, $currency)->balance
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
