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
        $search = trim((string) $request->get('q'));

        $transactions = Transaction::with('user')
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    // by transaction id
                    if (ctype_digit($search)) {
                        $w->orWhere('id', (int) $search);
                    }
                    // by client ID (GC000003 -> user 3)
                    if (stripos($search, 'GC') === 0 && ($digits = ltrim(preg_replace('/\D/', '', $search), '0')) !== '') {
                        $w->orWhere('user_id', (int) $digits);
                    }
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

        $clients = User::where('role', 'client')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.transactions.index', compact('transactions', 'clients', 'search'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'type' => ['required', 'in:deposit,withdrawal,profit,fee,reversal,adjustment'],
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

    public function update(Request $request, Transaction $transaction)
    {
        $data = $request->validate([
            'type' => ['required', 'in:deposit,withdrawal,profit,fee,reversal,adjustment'],
            'amount' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $transaction->update($data);

        // Keep the linked deposit/withdrawal record's amount in sync.
        $cls = $transaction->source_type;
        if ($cls && $transaction->source_id && class_exists($cls)) {
            optional($cls::find($transaction->source_id))->update(['amount' => abs((float) $data['amount'])]);
        }

        $this->recalc($transaction->user_id);
        optional(User::find($transaction->user_id))->recalcPlan();

        return back()->with('status', 'Transaction updated.');
    }

    public function destroy(Transaction $transaction)
    {
        $userId = $transaction->user_id;

        // Deleting a deposit/withdrawal ledger entry also removes its source
        // record, so Total Deposit / requests stay consistent with the balance.
        $cls = $transaction->source_type;
        if ($cls && $transaction->source_id && class_exists($cls)) {
            $cls::where('id', $transaction->source_id)->delete();
        }

        $transaction->delete();
        $this->recalc($userId);
        optional(User::find($userId))->recalcPlan();

        return back()->with('status', 'Transaction deleted.');
    }

    /** Recompute the running balance for a client after an edit/delete. */
    private function recalc(int $userId): void
    {
        $running = 0.0;
        Transaction::where('user_id', $userId)->orderBy('id')->get()->each(function ($t) use (&$running) {
            $running = round($running + (float) $t->amount, 2);
            if ((float) $t->balance_after !== $running) {
                $t->update(['balance_after' => $running]);
            }
        });
    }
}
