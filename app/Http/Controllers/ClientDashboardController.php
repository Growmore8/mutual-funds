<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\PoolController;
use App\Models\PnlAllocation;
use App\Models\PoolAccount;
use App\Models\Transaction;
use App\Services\PoolApiClient;
use Illuminate\Http\Request;

class ClientDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load('accountType');

        // The pools this client has capital in (a client can be in several).
        $poolIds = $user->deposits()->where('status', 'approved')->distinct()->pluck('pool_account_id')->filter();
        $pools = PoolAccount::whereIn('id', $poolIds)->get();
        $pool = $pools->first() ?? PoolAccount::first();   // for display fallback
        $latestSnap = $pool?->snapshots()->latest('snapshot_date')->first();

        $investment = (float) $user->deposits()->where('status', 'approved')->sum('amount');
        $balanceAfter = (float) (Transaction::where('user_id', $user->id)->latest('id')->value('balance_after') ?? 0);
        $totalEarned = (float) Transaction::where('user_id', $user->id)->where('type', 'profit')->sum('amount');

        $today = (float) PnlAllocation::where('user_id', $user->id)->whereDate('allocation_date', today())->sum('net_pnl');
        $yesterday = (float) PnlAllocation::where('user_id', $user->id)->whereDate('allocation_date', today()->subDay())->sum('net_pnl');
        $month = (float) Transaction::where('user_id', $user->id)->where('type', 'profit')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount');

        // Capital share = the client's capital across their pools / total balance of those pools.
        $poolsCapacity = (float) $pools->sum('capacity');
        $poolsBalance = (float) $pools->sum('balance');
        $sharePct = $poolsBalance > 0 ? round($investment / $poolsBalance * 100, 2) : 0.0;

        // Today's combined PnL across the client's pools.
        $poolToday = (float) PoolSnapshot::whereIn('pool_account_id', $poolIds)
            ->whereDate('snapshot_date', $latestSnap->snapshot_date ?? today())
            ->sum('pnl');
        $poolBalance = $poolsBalance;

        // Open (floating, unrealized) P/L — the client's proportional share.
        $poolsFloating = (float) $pools->sum('floating_pnl');
        $floatingShare = $poolsBalance > 0 ? round($poolsFloating * $investment / $poolsBalance, 2) : 0.0;

        // last 14 days of the client's net profit for the chart
        $chart = PnlAllocation::where('user_id', $user->id)
            ->where('allocation_date', '>=', today()->subDays(14))
            ->orderBy('allocation_date')
            ->get(['allocation_date', 'net_pnl']);

        $recent = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['profit', 'deposit', 'withdrawal'])
            ->latest('id')->limit(8)->get();

        return view('client.dashboard', compact(
            'user', 'pool', 'pools', 'latestSnap', 'investment', 'balanceAfter', 'totalEarned',
            'today', 'yesterday', 'month', 'sharePct', 'poolBalance', 'poolsCapacity', 'poolToday', 'chart', 'recent',
            'poolsFloating', 'floatingShare'
        ));
    }

    /** Live figures for the client dashboard's auto-refresh (JSON). */
    public function live(Request $request, PoolApiClient $api)
    {
        $user = $request->user();

        $poolIds = $user->deposits()->where('status', 'approved')->distinct()->pluck('pool_account_id')->filter();
        $pools = PoolAccount::whereIn('id', $poolIds)->get();
        $investment = (float) $user->deposits()->where('status', 'approved')->sum('amount');
        $poolsBalance = (float) $pools->sum('balance');

        $floatingTotal = 0.0;
        foreach ($pools as $pool) {
            $floatingTotal += (float) (PoolController::liveFigures($api, $pool)['floating'] ?? 0);
        }

        $floatingShare = $poolsBalance > 0 ? round($floatingTotal * $investment / $poolsBalance, 2) : 0.0;
        $today = (float) PnlAllocation::where('user_id', $user->id)->whereDate('allocation_date', today())->sum('net_pnl');

        return response()->json([
            'floatingShare' => $floatingShare,
            'today' => $today,
            'at' => now()->format('H:i:s'),
        ]);
    }
}
