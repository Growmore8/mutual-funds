<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Deposit;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class ClientDepositController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user()->load('accountType');

        return view('client.deposit.create', [
            'user' => $user,
            'methods' => PaymentMethod::orderBy('sort_order')->get(),
            'recent' => $user->deposits()->latest()->limit(8)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'method' => ['required', 'string', 'max:140'],
            'amount' => ['required', 'numeric', 'min:1'],
            'slip' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $slip = $request->file('slip')->store('deposits', 'local');

        $deposit = Deposit::create([
            'user_id' => $user->id,
            'pool_account_id' => $user->pool_account_id,
            'account_type_id' => $user->account_type_id,
            'amount' => $data['amount'],
            'currency' => 'USD',
            'method' => $data['method'],
            'proof_path' => $slip,
            'note' => $data['note'] ?? null,
            'value_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        AppNotification::notifyAdmins(
            'deposit',
            'New deposit request',
            $user->name . ' · $' . number_format((float) $data['amount'], 2) . ' · ' . $data['method'],
            route('admin.deposits.index'),
        );

        return redirect()->route('client.deposit.create')
            ->with('status', 'Deposit submitted with your slip. It will reflect in your balance once approved.');
    }
}
