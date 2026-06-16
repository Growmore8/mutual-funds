<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $withdrawals = Withdrawal::with('user')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.withdrawals.index', compact('withdrawals'));
    }

    public function approve(Request $request, Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->with('status', 'This request is already ' . $withdrawal->status . '.');
        }

        DB::transaction(function () use ($withdrawal, $request) {
            $last = Transaction::where('user_id', $withdrawal->user_id)->latest('id')->first();
            $balanceAfter = round((float) ($last->balance_after ?? 0) - (float) $withdrawal->amount, 2);

            Transaction::create([
                'user_id' => $withdrawal->user_id,
                'type' => 'withdrawal',
                'amount' => -1 * (float) $withdrawal->amount,
                'currency' => $withdrawal->currency,
                'balance_after' => $balanceAfter,
                'status' => 'completed',
                'description' => 'Withdrawal · ' . ($withdrawal->method ?? 'payout'),
                'source_type' => Withdrawal::class,
                'source_id' => $withdrawal->id,
            ]);

            $withdrawal->update([
                'status' => 'approved',
                'processed_at' => now(),
                'admin_note' => $request->input('admin_note'),
            ]);
        });

        return back()->with('status', 'Withdrawal approved and balance debited.');
    }

    public function reject(Request $request, Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->with('status', 'This request is already ' . $withdrawal->status . '.');
        }

        $withdrawal->update([
            'status' => 'rejected',
            'processed_at' => now(),
            'admin_note' => $request->input('admin_note'),
        ]);

        return back()->with('status', 'Withdrawal rejected.');
    }
}
