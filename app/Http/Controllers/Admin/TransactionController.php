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

        // Spot Trading transactions — trades + spot deposits/withdrawals, merged.
        $trades = \App\Models\SpotTrade::with('instrument')->latest('id')->limit(50)->get();
        $deps = Deposit::where('purpose', 'spot')->latest('id')->limit(50)->get();
        $wds = Withdrawal::where('purpose', 'spot')->latest('id')->limit(50)->get();
        $names = User::whereIn('id', $trades->flatMap(fn ($t) => [$t->buyer_id, $t->seller_id])
            ->merge($deps->pluck('user_id'))->merge($wds->pluck('user_id'))->filter()->unique())
            ->pluck('name', 'id');

        $spotItems = collect();
        $trades->each(function ($t) use ($spotItems, $names) {
            $cid = $t->buyer_id ?: $t->seller_id;
            $isBuy = (bool) $t->buyer_id;
            $spotItems->push((object) ['when' => $t->created_at, 'client' => $names[$cid] ?? '—',
                'detail' => ($isBuy ? 'Buy ' : 'Sell ') . $t->instrument->symbol . ' ×' . rtrim(rtrim((string) $t->qty, '0'), '.'),
                'cs' => $t->instrument->currencySymbol(), 'amount' => (float) $t->qty * (float) $t->price, 'credit' => ! $isBuy,
                'kind' => 'Trade', 'del' => route('admin.spot.trade.delete', $t)]);
        });
        $deps->each(fn ($d) => $spotItems->push((object) ['when' => $d->created_at, 'client' => $names[$d->user_id] ?? '—',
            'detail' => 'Deposit · ' . ($d->method ?: 'spot'), 'cs' => $d->currency === 'INR' ? '₹' : '$',
            'amount' => (float) $d->amount, 'credit' => true, 'kind' => 'Deposit', 'del' => null]));
        $wds->each(fn ($w) => $spotItems->push((object) ['when' => $w->created_at, 'client' => $names[$w->user_id] ?? '—',
            'detail' => 'Withdrawal · ' . ($w->method ?: 'spot'), 'cs' => $w->currency === 'INR' ? '₹' : '$',
            'amount' => (float) $w->amount, 'credit' => false, 'kind' => 'Withdrawal', 'del' => null]));
        if ($search !== '') {
            $needle = strtolower($search);
            $spotItems = $spotItems->filter(fn ($s) => str_contains(strtolower((string) $s->client), $needle));
        }
        $spotItems = $spotItems->sortByDesc('when')->take(80)->values();

        // Account picker (searchable) for the "Add transaction" form — incl. client's local fiat.
        $accounts = FundAccount::with('user')->get()->map(fn ($a) => [
            'id' => $a->id,
            'label' => ($a->user->name ?? 'Client') . ' · ' . $a->code() . ' · ' . $a->label,
            'search' => strtolower(trim(($a->user->name ?? '') . ' ' . ($a->user->email ?? '') . ' ' . $a->code() . ' ' . $a->label . ' ' . ($a->user?->clientCode() ?? ''))),
            'localCur' => $a->user?->localCurrency() ?? 'USD',
        ])->values();
        $fxMap = app(\App\Services\SpotTradingService::class)->ratesMap();

        return view('admin.transactions.index', compact('transactions', 'accounts', 'search', 'spotItems', 'fxMap'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fund_account_id' => ['required', 'exists:fund_accounts,id'],
            'type' => ['required', 'in:deposit,withdrawal,profit,fee,reversal,adjustment'],
            'amount' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'in:fund,spot_usd,spot_inr'],
            'entered_currency' => ['nullable', 'string', 'max:8'],
        ]);

        $account = FundAccount::findOrFail($data['fund_account_id']);
        $user = $account->user;
        $spotSvc = app(\App\Services\SpotTradingService::class);

        // Amount may be entered in the client's local fiat — convert to the USD base and
        // auto-note the original fiat amount on the description.
        $enteredCur = strtoupper($data['entered_currency'] ?? 'USD');
        $raw = (float) $data['amount'];
        $amtUsd = $enteredCur === 'USD' ? $raw : round($raw / max(0.0000001, $spotSvc->usdRate($enteredCur)), 2);
        $desc = $data['description'] ?? null;
        if ($enteredCur !== 'USD') {
            $desc = trim('Paid ' . number_format(abs($raw), 2) . ' ' . $enteredCur . ($desc ? ' · ' . $desc : ''));
        }

        // Book to a Spot wallet instead of the mutual-fund ledger when chosen.
        $destination = ($data['destination'] ?? 'fund') === 'fund' ? 'fund' : 'spot';
        if ($destination !== 'fund') {
            $signed = match ($data['type']) {
                'deposit' => abs($amtUsd),
                'withdrawal', 'fee' => -abs($amtUsd),
                default => $amtUsd, // adjustment / reversal: respect entered sign (use − to debit)
            };
            $spotSvc->adjustBalance($user->id, $signed, 'USD');

            // Record it so it shows in transactions (client + admin).
            if ($signed >= 0) {
                Deposit::create(['user_id' => $user->id, 'purpose' => 'spot', 'currency' => 'USD',
                    'amount' => abs($signed), 'method' => trim('Admin ' . $data['type'] . ($enteredCur !== 'USD' ? ' · ' . number_format(abs($raw), 2) . ' ' . $enteredCur : '')), 'status' => 'approved',
                    'value_date' => now()->toDateString(), 'approved_at' => now()]);
            } else {
                Withdrawal::create(['user_id' => $user->id, 'purpose' => 'spot', 'currency' => 'USD',
                    'amount' => abs($signed), 'method' => trim('Admin ' . $data['type'] . ($enteredCur !== 'USD' ? ' · ' . number_format(abs($raw), 2) . ' ' . $enteredCur : '')), 'status' => 'approved', 'processed_at' => now()]);
            }

            AppNotification::notify($user->id, in_array($data['type'], ['deposit', 'withdrawal']) ? $data['type'] : 'transaction',
                ucfirst($data['type']) . ' · Spot',
                ($signed < 0 ? '-' : '+') . '$' . number_format(abs($signed), 2) . ' on your Spot wallet.',
                route('spot.index'));

            return back()->with('status', ucfirst($data['type']) . ' booked to ' . $user->name . "'s Spot wallet ($" . number_format(abs($signed), 2) . ').');
        }

        // Balance runs per fund account.
        $last = Transaction::where('fund_account_id', $account->id)->latest('id')->first();
        $balanceAfter = round((float) ($last->balance_after ?? 0) + $amtUsd, 2);

        $tx = Transaction::create([
            'user_id' => $user->id,
            'fund_account_id' => $account->id,
            'type' => $data['type'],
            'amount' => $amtUsd,
            'currency' => 'USD',
            'balance_after' => $balanceAfter,
            'status' => 'completed',
            'description' => $desc,
        ]);

        // A deposit/withdrawal here also creates the matching record so it counts
        // as invested capital for THIS account (Total Deposit, share, distribution).
        if ($data['type'] === 'deposit') {
            $dep = Deposit::create([
                'user_id' => $user->id,
                'fund_account_id' => $account->id,
                'pool_account_id' => $account->pool_account_id,
                'account_type_id' => $account->account_type_id,
                'amount' => abs($amtUsd),
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
                'amount' => abs($amtUsd),
                'currency' => 'USD',
                'method' => $enteredCur !== 'USD' ? 'Admin · ' . number_format(abs($raw), 2) . ' ' . $enteredCur : 'Admin adjustment',
                'status' => 'approved',
                'processed_at' => now(),
            ]);
            $tx->update(['source_type' => Withdrawal::class, 'source_id' => $wd->id]);
        }

        AppNotification::notify(
            $user->id,
            in_array($data['type'], ['deposit', 'withdrawal']) ? $data['type'] : 'transaction',
            ucfirst($data['type']) . ' recorded',
            (($amtUsd < 0 ? '-' : '+') . '$' . number_format(abs($amtUsd), 2)) . ' · ' . $account->label,
            route('client.transactions'),
        );

        return back()->with('status', 'Transaction recorded to ' . $account->code() . '.');
    }

    /** Move a client's funds between Mutual Fund and Spot (single USD base). */
    public function transfer(Request $request)
    {
        $data = $request->validate([
            'fund_account_id' => ['required', 'exists:fund_accounts,id'],
            'direction' => ['required', 'in:mf_to_spot,spot_to_mf'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $account = FundAccount::findOrFail($data['fund_account_id']);
        $user = $account->user;
        $amount = round((float) $data['amount'], 2);
        $spot = app(\App\Services\SpotTradingService::class);

        if ($data['direction'] === 'mf_to_spot') {
            $max = $account->availableToWithdraw();
            if ($amount > $max + 0.001) {
                return back()->with('status', "Only mutual-fund profit can be moved (max $" . number_format($max, 2) . ' for ' . $account->code() . ').');
            }
            $last = Transaction::where('fund_account_id', $account->id)->latest('id')->first();
            $tx = Transaction::create([
                'user_id' => $user->id, 'fund_account_id' => $account->id, 'type' => 'withdrawal',
                'amount' => -$amount, 'currency' => 'USD',
                'balance_after' => round((float) ($last->balance_after ?? 0) - $amount, 2),
                'status' => 'completed', 'description' => 'Transfer to Spot wallet (admin)',
            ]);
            $wd = Withdrawal::create(['user_id' => $user->id, 'fund_account_id' => $account->id, 'purpose' => 'fund',
                'amount' => $amount, 'currency' => 'USD', 'method' => 'Internal transfer to Spot', 'status' => 'approved', 'processed_at' => now()]);
            $tx->update(['source_type' => Withdrawal::class, 'source_id' => $wd->id]);
            $spot->adjustBalance($user->id, $amount, 'USD');

            return back()->with('status', '$' . number_format($amount, 2) . ' moved from Mutual Fund to Spot for ' . $user->name . '.');
        }

        // spot_to_mf
        $spotBal = (float) $spot->account($user->id, 'USD')->balance;
        if ($amount > $spotBal + 0.001) {
            return back()->with('status', 'Insufficient Spot balance ($' . number_format($spotBal, 2) . ') for ' . $user->name . '.');
        }
        $spot->adjustBalance($user->id, -$amount, 'USD');
        $last = Transaction::where('fund_account_id', $account->id)->latest('id')->first();
        $tx = Transaction::create([
            'user_id' => $user->id, 'fund_account_id' => $account->id, 'type' => 'deposit',
            'amount' => $amount, 'currency' => 'USD',
            'balance_after' => round((float) ($last->balance_after ?? 0) + $amount, 2),
            'status' => 'completed', 'description' => 'Transfer from Spot wallet (admin)',
        ]);
        $dep = Deposit::create(['user_id' => $user->id, 'fund_account_id' => $account->id,
            'pool_account_id' => $account->pool_account_id, 'account_type_id' => $account->account_type_id,
            'amount' => $amount, 'currency' => 'USD', 'status' => 'approved', 'value_date' => now()->toDateString(), 'approved_at' => now()]);
        $tx->update(['source_type' => Deposit::class, 'source_id' => $dep->id]);
        $account->recalcPlan();

        return back()->with('status', '$' . number_format($amount, 2) . ' moved from Spot to Mutual Fund for ' . $user->name . '.');
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
