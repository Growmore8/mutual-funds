<?php

namespace App\Http\Controllers;

use App\Models\PnlAllocation;
use App\Models\Transaction;
use Illuminate\Http\Request;

class StatementController extends Controller
{
    /** Deposit / withdrawal / profit transaction history. */
    public function transactions(Request $request)
    {
        $type = $request->get('type');

        $transactions = Transaction::where('user_id', $request->user()->id)
            ->when(in_array($type, ['deposit', 'withdrawal', 'profit', 'fee', 'adjustment']),
                fn ($q) => $q->where('type', $type))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('client.transactions', compact('transactions', 'type'));
    }

    /** Daily profit history (per-day PnL allocations). */
    public function profit(Request $request)
    {
        $rows = PnlAllocation::where('user_id', $request->user()->id)
            ->latest('allocation_date')
            ->paginate(31);

        $totalProfit = $request->user()->totalProfit();

        return view('client.profit', compact('rows', 'totalProfit'));
    }
}
