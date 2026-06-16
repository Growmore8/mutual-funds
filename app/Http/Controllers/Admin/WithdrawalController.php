<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Services\Notifier;
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

        Notifier::send(
            $withdrawal->user,
            'Your withdrawal has been approved',
            'Withdrawal approved',
            [
                'Your withdrawal request of $' . number_format((float) $withdrawal->amount, 2) . ' has been approved.',
                'Payout method: ' . ($withdrawal->method ?? 'as provided') . '. Funds will be sent to your nominated destination.',
            ],
            route('client.transactions'),
            'View transactions',
        );

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

        Notifier::send(
            $withdrawal->user,
            'Update on your withdrawal request',
            'Withdrawal not approved',
            [
                'Your withdrawal request of $' . number_format((float) $withdrawal->amount, 2) . ' was not approved at this time.',
                $request->input('admin_note') ? 'Note from our team: ' . $request->input('admin_note') : 'If you have questions, please open a support ticket from your dashboard.',
            ],
        );

        return back()->with('status', 'Withdrawal rejected.');
    }
}
