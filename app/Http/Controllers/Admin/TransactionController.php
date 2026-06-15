<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = Transaction::with('user')
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $clients = User::where('role', 'client')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.transactions.index', compact('transactions', 'clients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'type' => ['required', 'in:deposit,withdrawal,profit,fee,adjustment'],
            'amount' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $last = Transaction::where('user_id', $data['user_id'])->latest()->first();
        $balanceAfter = (float) ($last->balance_after ?? 0) + (float) $data['amount'];

        Transaction::create([
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'currency' => 'USD',
            'balance_after' => $balanceAfter,
            'status' => 'completed',
            'description' => $data['description'] ?? null,
        ]);

        return back()->with('status', 'Transaction recorded.');
    }
}
