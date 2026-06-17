<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\PoolController;
use App\Models\PnlAllocation;
use App\Models\PoolAccount;
use App\Models\PoolSnapshot;
use App\Models\Transaction;
use App\Services\PoolApiClient;
use Illuminate\Http\Request;

class ClientDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load('accountType', 'poolAccount');

        // Pools the client is tied to: their deposits' pools + the admin-assigned Live ID.
        $poolIds = $user->deposits()->where('status', 'approved')->distinct()->pluck('pool_account_id')->filter();
        if ($user->pool_account_id) {
            $poolIds = $poolIds->push($user->pool_account_id)->unique()->values();
        }
        $pools = PoolAccount::whereIn('id', $poolIds)->get();
        $pool = $pools->first() ?? $user->poolAccount ?? PoolAccount::where('is_active', true)->first();   // display pool
        $latestSnap = $pool?->snapshots()->latest('snapshot_date')->first();

        $investment = (float) $user->deposits()->where('status', 'approved')->sum('amount');   // principal (locked)
        $balanceAfter = (float) (Transaction::where('user_id', $user->id)->latest('id')->value('balance_after') ?? 0);
        $totalEarned = (float) Transaction::where('user_id', $user->id)->where('type', 'profit')->sum('amount'); // gross profit/loss credited
        $runningPnl = $user->runningPnl();              // profit/loss after payouts (can be negative)
        $withdrawable = $user->availableToWithdraw();   // positive PnL only

        $today = (float) PnlAllocation::where('user_id', $user->id)->whereDate('allocation_date', today())->sum('net_pnl');
        $yesterday = (float) PnlAllocation::where('user_id', $user->id)->whereDate('allocation_date', today()->subDay())->sum('net_pnl');
        $month = (float) Transaction::where('user_id', $user->id)->where('type', 'profit')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount');

        // Profit share = invested / the plan's fixed pool amount (× 100), capped at 100%.
        $planPool = (float) ($user->accountType->pool_amount ?? 0);
        $poolsCapacity = $planPool > 0 ? $planPool : (float) $pools->sum('capacity');
        $poolsBalance = (float) $pools->sum('balance');
        $sharePct = $planPool > 0 ? round(min(100, $investment / $planPool * 100), 2) : 0.0;

        // Today's combined PnL across the client's pools.
        $poolToday = (float) PoolSnapshot::whereIn('pool_account_id', $poolIds)
            ->whereDate('snapshot_date', $latestSnap->snapshot_date ?? today())
            ->sum('pnl');
        $poolBalance = $poolsBalance;

        // Open (floating, unrealized) P/L — the client's proportional share.
        $poolsFloating = $pools->isNotEmpty() ? (float) $pools->sum('floating_pnl') : (float) ($pool->floating_pnl ?? 0);
        $shareWeight = $planPool > 0 ? min(1.0, $investment / $planPool) : 0.0;
        $floatingShare = round($poolsFloating * $shareWeight, 2);
        $liveRef = $pool->account_ref ?? null;   // assigned Live ID / pool account

        // last 14 days of the client's net profit for the chart — from profit
        // transactions (same source as balance/profit history), summed per day.
        $chart = Transaction::where('user_id', $user->id)
            ->where('type', 'profit')
            ->where('created_at', '>=', today()->subDays(14))
            ->selectRaw('DATE(created_at) as allocation_date, SUM(amount) as net_pnl')
            ->groupBy('allocation_date')
            ->orderBy('allocation_date')
            ->get();

        $recent = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['profit', 'deposit', 'withdrawal', 'referral'])
            ->latest('id')->limit(8)->get();

        $referralEarned = $user->referralEarned();

        return view('client.dashboard', compact(
            'user', 'pool', 'pools', 'latestSnap', 'investment', 'balanceAfter', 'totalEarned',
            'today', 'yesterday', 'month', 'sharePct', 'poolBalance', 'poolsCapacity', 'poolToday', 'chart', 'recent',
            'poolsFloating', 'floatingShare', 'liveRef', 'withdrawable', 'runningPnl', 'referralEarned'
        ));
    }

    /** Live figures for the client dashboard's auto-refresh (JSON). */
    public function live(Request $request, PoolApiClient $api)
    {
        $user = $request->user()->load('accountType');

        $poolIds = $user->deposits()->where('status', 'approved')->distinct()->pluck('pool_account_id')->filter();
        if ($user->pool_account_id) {
            $poolIds = $poolIds->push($user->pool_account_id)->unique()->values();
        }
        $pools = PoolAccount::whereIn('id', $poolIds)->get();
        if ($pools->isEmpty() && ($first = PoolAccount::where('is_active', true)->first())) {
            $pools = collect([$first]);   // show the managed pool's live P/L even before investing
        }
        $investment = (float) $user->deposits()->where('status', 'approved')->sum('amount');
        $planPool = (float) ($user->accountType->pool_amount ?? 0);
        $shareWeight = $planPool > 0 ? min(1.0, $investment / $planPool) : 0.0;

        $poolFloating = 0.0;
        foreach ($pools as $pool) {
            $poolFloating += (float) (PoolController::liveFigures($api, $pool)['floating'] ?? 0);
        }

        $floatingShare = round($poolFloating * $shareWeight, 2);
        $today = (float) PnlAllocation::where('user_id', $user->id)->whereDate('allocation_date', today())->sum('net_pnl');

        return response()->json([
            'poolFloating' => round($poolFloating, 2),
            'floatingShare' => $floatingShare,
            'today' => $today,
            'at' => now()->format('H:i:s'),
        ]);
    }
}
