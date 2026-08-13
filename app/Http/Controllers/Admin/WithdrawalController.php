<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FiltersClients;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    use FiltersClients;

    public function index(Request $request)
    {
        $search = trim((string) $request->get('q'));
        $withdrawals = Withdrawal::with('user', 'fundAccount')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($search !== '', fn ($q) => $q->whereHas('user', fn ($u) => $this->matchClient($u, $search)))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.withdrawals.index', compact('withdrawals', 'search'));
    }

    public function approve(Request $request, Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->with('status', 'This request is already ' . $withdrawal->status . '.');
        }

        // Spot withdrawals debit the single USD spot wallet (INR amounts converted).
        if ($withdrawal->purpose === 'spot') {
            $svc = app(\App\Services\SpotTradingService::class);
            $usd = round($svc->toUsd((float) $withdrawal->amount, $withdrawal->currency ?: 'USD'), 2);
            $svc->adjustBalance($withdrawal->user_id, -1 * $usd, 'USD');
            $withdrawal->update(['status' => 'approved', 'processed_at' => now(), 'admin_note' => $request->input('admin_note'), 'usd_amount' => $usd]);
            $amt = '$' . number_format($usd, 2);
            \App\Models\AppNotification::notify($withdrawal->user_id, 'withdrawal', 'Spot withdrawal approved', $amt . ' debited from your Spot wallet.', route('spot.index'));

            return back()->with('status', 'Spot withdrawal approved and trading balance debited (' . $amt . ').');
        }

        DB::transaction(function () use ($withdrawal, $request) {
            $last = Transaction::where('fund_account_id', $withdrawal->fund_account_id)->latest('id')->first();
            $balanceAfter = round((float) ($last->balance_after ?? 0) - (float) $withdrawal->amount, 2);

            Transaction::create([
                'user_id' => $withdrawal->user_id,
                'fund_account_id' => $withdrawal->fund_account_id,
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

        $amt = '$' . number_format((float) $withdrawal->amount, 2);
        \App\Models\AppNotification::notify($withdrawal->user_id, 'withdrawal', 'Withdrawal approved', $amt . ' approved and being sent.', route('client.transactions'));
        Notifier::send(
            $withdrawal->user,
            'Your withdrawal has been approved',
            'Withdrawal approved',
            [
                'Your withdrawal request of ' . $amt . ' has been approved.',
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

        $amt = '$' . number_format((float) $withdrawal->amount, 2);
        $reason = $request->input('admin_note');
        \App\Models\AppNotification::notify($withdrawal->user_id, 'withdrawal', 'Withdrawal not approved', $amt . ($reason ? ' — ' . $reason : ''), route('withdraw.create'));
        Notifier::send(
            $withdrawal->user,
            'Update on your withdrawal request',
            'Withdrawal not approved',
            [
                'Your withdrawal request of ' . $amt . ' was not approved at this time.',
                $reason ? 'Reason: ' . $reason : 'If you have questions, please open a support ticket from your dashboard.',
            ],
        );

        return back()->with('status', 'Withdrawal rejected.');
    }

    /**
     * Reverse an already-APPROVED withdrawal (e.g. approved by mistake).
     * Restores the balance (fund ledger credit / spot wallet credit) and marks
     * the request rejected with the reason. Idempotent-safe: only touches approved.
     */
    public function reverse(Request $request, Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'approved') {
            return back()->with('status', 'Only an approved withdrawal can be reversed (this one is ' . $withdrawal->status . ').');
        }

        $reason = trim((string) $request->input('admin_note'));
        $note = 'Reversed by admin' . ($reason !== '' ? ': ' . $reason : '');

        // Spot: credit the USD wallet back by the same USD amount that was debited.
        if ($withdrawal->purpose === 'spot') {
            $svc = app(\App\Services\SpotTradingService::class);
            $usd = $withdrawal->usd_amount !== null
                ? (float) $withdrawal->usd_amount
                : round($svc->toUsd((float) $withdrawal->amount, $withdrawal->currency ?: 'USD'), 2);
            $svc->adjustBalance($withdrawal->user_id, $usd, 'USD');
            $withdrawal->update(['status' => 'rejected', 'admin_note' => $note, 'processed_at' => now()]);

            $amt = '$' . number_format($usd, 2);
            \App\Models\AppNotification::notify($withdrawal->user_id, 'withdrawal', 'Withdrawal reversed', $amt . ' credited back to your Spot wallet.' . ($reason ? ' — ' . $reason : ''), route('spot.index'));

            return back()->with('status', 'Spot withdrawal reversed; ' . $amt . ' credited back.');
        }

        // Fund: post a compensating ledger credit so balance_after returns to its prior level.
        DB::transaction(function () use ($withdrawal, $note) {
            $last = Transaction::where('fund_account_id', $withdrawal->fund_account_id)->latest('id')->first();
            $balanceAfter = round((float) ($last->balance_after ?? 0) + (float) $withdrawal->amount, 2);

            Transaction::create([
                'user_id' => $withdrawal->user_id,
                'fund_account_id' => $withdrawal->fund_account_id,
                'type' => 'deposit',
                'amount' => (float) $withdrawal->amount,
                'currency' => $withdrawal->currency,
                'balance_after' => $balanceAfter,
                'status' => 'completed',
                'description' => 'Withdrawal reversed · ' . ($withdrawal->method ?? 'payout'),
                'source_type' => Withdrawal::class,
                'source_id' => $withdrawal->id,
            ]);

            $withdrawal->update(['status' => 'rejected', 'admin_note' => $note, 'processed_at' => now()]);
        });

        $amt = '$' . number_format((float) $withdrawal->amount, 2);
        \App\Models\AppNotification::notify($withdrawal->user_id, 'withdrawal', 'Withdrawal reversed', $amt . ' has been returned to your account.' . ($reason ? ' — ' . $reason : ''), route('client.transactions'));
        Notifier::send(
            $withdrawal->user,
            'Update on your withdrawal',
            'Withdrawal reversed',
            [
                'Your previously approved withdrawal of ' . $amt . ' has been reversed and the amount returned to your account.',
                $reason !== '' ? 'Reason: ' . $reason : 'Please contact support if you have any questions.',
            ],
            route('client.transactions'),
            'View transactions',
        );

        return back()->with('status', 'Withdrawal reversed and balance restored.');
    }
}
