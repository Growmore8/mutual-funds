<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WithdrawalController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();

        return view('client.withdraw.create', [
            'available' => $user->availableToWithdraw(),
            'methods' => PaymentMethod::orderBy('sort_order')->get(),
            'withdrawals' => $user->withdrawals()->latest()->limit(10)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $available = $user->availableToWithdraw();

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'string', 'max:120'],
            'payout_details' => ['required', 'string', 'max:1000'],
        ]);

        if ($data['amount'] > $available) {
            throw ValidationException::withMessages([
                'amount' => 'You can withdraw at most $' . number_format($available, 2) . ' (profit only).',
            ]);
        }

        Withdrawal::create([
            'user_id' => $user->id,
            'amount' => $data['amount'],
            'currency' => 'USD',
            'method' => $data['method'],
            'payout_details' => $data['payout_details'],
            'status' => 'pending',
        ]);

        return redirect()->route('withdraw.create')
            ->with('status', 'Withdrawal request submitted. Our team will review it shortly.');
    }
}
