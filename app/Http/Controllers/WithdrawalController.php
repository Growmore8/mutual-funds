<?php

namespace App\Http\Controllers;

use App\Models\Withdrawal;
use App\Models\WithdrawalMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WithdrawalController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();
        $account = $user->currentAccount();

        return view('client.withdraw.create', [
            'available' => $account ? $account->availableToWithdraw() : 0.0,
            'payoutMethods' => $user->withdrawalMethods,
            'withdrawals' => $account ? $account->withdrawals()->latest()->limit(10)->get() : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $account = $user->currentAccount();
        $available = $account ? $account->availableToWithdraw() : 0.0;

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'withdrawal_method_id' => ['required', 'exists:withdrawal_methods,id'],
        ]);

        $method = WithdrawalMethod::where('id', $data['withdrawal_method_id'])->where('user_id', $user->id)->first();
        if (! $method) {
            throw ValidationException::withMessages(['withdrawal_method_id' => 'Please choose one of your saved payout methods.']);
        }

        if ($data['amount'] > $available) {
            throw ValidationException::withMessages([
                'amount' => 'You can withdraw at most $' . number_format($available, 2) . ' (profit only).',
            ]);
        }

        Withdrawal::create([
            'user_id' => $user->id,
            'fund_account_id' => $account?->id,
            'amount' => $data['amount'],
            'currency' => 'USD',
            'method' => $method->title(),
            'withdrawal_method_id' => $method->id,
            'payout_details' => $method->summary(),
            'status' => 'pending',
        ]);

        \App\Models\AppNotification::notifyAdmins(
            'withdrawal',
            'New withdrawal request',
            $user->name . ' · $' . number_format((float) $data['amount'], 2) . ' · ' . $method->title(),
            route('admin.withdrawals.index'),
        );

        return redirect()->route('withdraw.create')
            ->with('status', 'Withdrawal request submitted. Our team will review it shortly.');
    }
}
