<?php

namespace App\Http\Controllers;

use App\Models\PnlAllocation;
use App\Models\PoolAccount;
use App\Models\Transaction;
use Illuminate\Http\Request;

class ClientDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load('accountType');
        $pool = PoolAccount::first();
        $latestSnap = $pool?->snapshots()->latest('snapshot_date')->first();

        $investment = (float) $user->deposits()->where('status', 'approved')->sum('amount');
        $balanceAfter = (float) (Transaction::where('user_id', $user->id)->latest('id')->value('balance_after') ?? 0);
        $totalEarned = (float) Transaction::where('user_id', $user->id)->where('type', 'profit')->sum('amount');

        $today = (float) PnlAllocation::where('user_id', $user->id)->whereDate('allocation_date', today())->sum('net_pnl');
        $yesterday = (float) PnlAllocation::where('user_id', $user->id)->whereDate('allocation_date', today()->subDay())->sum('net_pnl');
        $month = (float) Transaction::where('user_id', $user->id)->where('type', 'profit')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount');

        $sharePct = (float) ($user->accountType->profit_share_pct ?? 0);
        // client's share of the pool (their capital / pool balance)
        $poolBalance = (float) ($pool->balance ?? 0);
        $poolToday = (float) ($latestSnap->pnl ?? 0);

        // last 14 days of the client's net profit for the chart
        $chart = PnlAllocation::where('user_id', $user->id)
            ->where('allocation_date', '>=', today()->subDays(14))
            ->orderBy('allocation_date')
            ->get(['allocation_date', 'net_pnl']);

        $recent = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['profit', 'deposit', 'withdrawal'])
            ->latest('id')->limit(8)->get();

        return view('client.dashboard', compact(
            'user', 'pool', 'latestSnap', 'investment', 'balanceAfter', 'totalEarned',
            'today', 'yesterday', 'month', 'sharePct', 'poolBalance', 'poolToday', 'chart', 'recent'
        ));
    }
}
