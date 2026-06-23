<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Deposit;
use App\Models\FundAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('q'));

        $transactions = Transaction::with('user', 'source', 'fundAccount')
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    // by transaction id
                    if (ctype_digit($search)) {
                        $w->orWhere('id', (int) $search);
                    }
                    // by client ID (GC000003 -> user 3)
                    if (stripos($search, 'GC') === 0 && stripos($search, 'GCA') !== 0 && ($digits = ltrim(preg_replace('/\D/', '', $search), '0')) !== '') {
                        $w->orWhere('user_id', (int) $digits);
                    }
                    // by account number (GCA000006)
                    $w->orWhereHas('fundAccount', fn ($fa) => $fa->where('account_no', 'like', "%{$search}%"));
                    // by client name / email
                    $w->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    });
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        // Spot Trading transactions (trades) — shown below, with delete (reverses everywhere).
        $spotTrades = \App\Models\SpotTrade::with('instrument')->latest('id')->limit(60)->get();
        $spotUsers = User::whereIn('id', $spotTrades->flatMap(fn ($t) => [$t->buyer_id, $t->seller_id])->filter()->unique())
            ->get(['id', 'name'])->keyBy('id');

        // Account picker (searchable) for the "Add transaction" form.
        $accounts = FundAccount::with('user')->get()->map(fn ($a) => [
            'id' => $a->id,
            'label' => ($a->user->name ?? 'Client') . ' · ' . $a->code() . ' · ' . $a->label,
            'search' => strtolower(trim(($a->user->name ?? '') . ' ' . ($a->user->email ?? '') . ' ' . $a->code() . ' ' . $a->label . ' ' . ($a->user?->clientCode() ?? ''))),
        ])->values();

        return view('admin.transactions.index', compact('transactions', 'accounts', 'search', 'spotTrades', 'spotUsers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fund_account_id' => ['required', 'exists:fund_accounts,id'],
            'type' => ['required', 'in:deposit,withdrawal,profit,fee,reversal,adjustment'],
            'amount' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'in:fund,spot_usd,spot_inr'],
        ]);

        $account = FundAccount::findOrFail($data['fund_account_id']);
        $user = $account->user;

        // Book to a Spot wallet instead of the mutual-fund ledger when chosen.
        $destination = $data['destination'] ?? 'fund';
        if ($destination !== 'fund') {
            $currency = $destination === 'spot_inr' ? 'INR' : 'USD';
            $signed = in_array($data['type'], ['withdrawal', 'fee']) ? -abs($data['amount']) : abs($data['amount']);
            app(\App\Services\SpotTradingService::class)->adjustBalance($user->id, $signed, $currency);

            $sym = $currency === 'INR' ? '₹' : '$';
            AppNotification::notify($user->id, in_array($data['type'], ['deposit', 'withdrawal']) ? $data['type'] : 'transaction',
                ucfirst($data['type']) . ' · Spot ' . $currency,
                ($signed < 0 ? '-' : '+') . $sym . number_format(abs($signed), 2) . ' on your Spot ' . $currency . ' wallet.',
                route('spot.index'));

            return back()->with('status', ucfirst($data['type']) . ' booked to ' . $user->name . "'s Spot {$currency} wallet.");
        }

        // Balance runs per fund account.
        $last = Transaction::where('fund_account_id', $account->id)->latest('id')->first();
        $balanceAfter = round((float) ($last->balance_after ?? 0) + (float) $data['amount'], 2);

        $tx = Transaction::create([
            'user_id' => $user->id,
            'fund_account_id' => $account->id,
            'type' => $data['type'],
            'amount' => $data['amount'],
            'currency' => 'USD',
            'balance_after' => $balanceAfter,
            'status' => 'completed',
            'description' => $data['description'] ?? null,
        ]);

        // A deposit/withdrawal here also creates the matching record so it counts
        // as invested capital for THIS account (Total Deposit, share, distribution).
        if ($data['type'] === 'deposit') {
            $dep = Deposit::create([
                'user_id' => $user->id,
                'fund_account_id' => $account->id,
                'pool_account_id' => $account->pool_account_id,
                'account_type_id' => $account->account_type_id,
                'amount' => abs((float) $data['amount']),
                'currency' => 'USD',
                'status' => 'approved',
                'value_date' => now()->toDateString(),
                'approved_at' => now(),
            ]);
            $tx->update(['source_type' => Deposit::class, 'source_id' => $dep->id]);
            $account->recalcPlan();
        } elseif ($data['type'] === 'withdrawal') {
            $wd = Withdrawal::create([
                'user_id' => $user->id,
                'fund_account_id' => $account->id,
                'amount' => abs((float) $data['amount']),
                'currency' => 'USD',
                'method' => 'Admin adjustment',
                'status' => 'approved',
                'processed_at' => now(),
            ]);
            $tx->update(['source_type' => Withdrawal::class, 'source_id' => $wd->id]);
        }

        AppNotification::notify(
            $user->id,
            in_array($data['type'], ['deposit', 'withdrawal']) ? $data['type'] : 'transaction',
            ucfirst($data['type']) . ' recorded',
            (($data['amount'] < 0 ? '-' : '+') . '$' . number_format(abs((float) $data['amount']), 2)) . ' · ' . $account->label,
            route('client.transactions'),
        );

        return back()->with('status', 'Transaction recorded to ' . $account->code() . '.');
    }

    public function update(Request $request, Transaction $transaction)
    {
        $data = $request->validate([
            'type' => ['required', 'in:deposit,withdrawal,profit,fee,reversal,adjustment'],
            'amount' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $oldAmount = (float) $transaction->getOriginal('amount');
        $transaction->update($data);

        // Keep the linked deposit/withdrawal record's amount in sync.
        $cls = $transaction->source_type;
        if ($cls && $transaction->source_id && class_exists($cls)) {
            optional($cls::find($transaction->source_id))->update(['amount' => abs((float) $data['amount'])]);
        }

        // Keep the PnL allocation in sync when a profit/loss row is edited.
        if ($transaction->type === 'profit' && $transaction->pnl_allocation_id) {
            $delta = round((float) $data['amount'] - $oldAmount, 2);
            if (abs($delta) >= 0.005 && ($alloc = \App\Models\PnlAllocation::find($transaction->pnl_allocation_id))) {
                $alloc->increment('net_pnl', $delta);
                $alloc->increment('gross_pnl', $delta);
            }
        }

        $this->recalcAccount($transaction->fund_account_id);
        optional(FundAccount::find($transaction->fund_account_id))->recalcPlan();

        return back()->with('status', 'Transaction updated.');
    }

    public function destroy(Transaction $transaction)
    {
        $accountId = $transaction->fund_account_id;

        // Deleting a deposit/withdrawal ledger entry also removes its source
        // record, so Total Deposit / requests stay consistent with the balance.
        $cls = $transaction->source_type;
        if ($cls && $transaction->source_id && class_exists($cls)) {
            $cls::where('id', $transaction->source_id)->delete();
        }

        // Deleting a profit/loss row reverses its share from the PnL allocation,
        // so profit history and the distributed total stay consistent.
        if ($transaction->type === 'profit' && $transaction->pnl_allocation_id
            && ($alloc = \App\Models\PnlAllocation::find($transaction->pnl_allocation_id))) {
            $alloc->decrement('net_pnl', (float) $transaction->amount);
            $alloc->decrement('gross_pnl', (float) $transaction->amount);
            if (abs((float) $alloc->fresh()->net_pnl) < 0.005 && abs((float) $alloc->fresh()->gross_pnl) < 0.005) {
                $alloc->delete();
            }
        }

        $transaction->delete();
        $this->recalcAccount($accountId);
        optional(FundAccount::find($accountId))->recalcPlan();

        return back()->with('status', 'Transaction deleted.');
    }

    /** Recompute the running balance for ONE fund account after an edit/delete. */
    private function recalcAccount(?int $accountId): void
    {
        if (! $accountId) {
            return;
        }

        $running = 0.0;
        Transaction::where('fund_account_id', $accountId)->orderBy('id')->get()->each(function ($t) use (&$running) {
            $running = round($running + (float) $t->amount, 2);
            if ((float) $t->balance_after !== $running) {
                $t->update(['balance_after' => $running]);
            }
        });
    }
}
