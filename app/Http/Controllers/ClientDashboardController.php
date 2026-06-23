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
        $user = $request->user();
        $account = $user->currentAccount();
        $account?->load('accountType', 'poolAccount');
        $at = $account?->accountType;
        $aid = $account?->id;

        // Pools tied to THIS account: its deposits' pools + its assigned Live ID.
        $poolIds = $account ? $account->deposits()->where('status', 'approved')->distinct()->pluck('pool_account_id')->filter() : collect();
        if ($account?->pool_account_id) {
            $poolIds = $poolIds->push($account->pool_account_id)->unique()->values();
        }
        $pools = PoolAccount::whereIn('id', $poolIds)->get();
        $pool = $pools->first() ?? $account?->poolAccount ?? PoolAccount::where('is_active', true)->first();   // display pool
        $latestSnap = $pool?->snapshots()->latest('snapshot_date')->first();

        $investment = $account ? $account->totalDeposited() : 0.0;   // principal (locked) for this account
        $balanceAfter = (float) (Transaction::where('fund_account_id', $aid)->latest('id')->value('balance_after') ?? 0);
        $totalEarned = (float) Transaction::where('fund_account_id', $aid)->where('type', 'profit')->sum('amount');
        $runningPnl = $account ? $account->runningPnl() : 0.0;
        $withdrawable = $account ? $account->availableToWithdraw() : 0.0;

        $today = (float) PnlAllocation::where('fund_account_id', $aid)->whereDate('allocation_date', today())->sum('net_pnl');
        $yesterday = (float) PnlAllocation::where('fund_account_id', $aid)->whereDate('allocation_date', today()->subDay())->sum('net_pnl');
        $month = (float) Transaction::where('fund_account_id', $aid)->where('type', 'profit')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount');

        // Profit share = invested / the plan's fixed pool amount (× 100), capped at 100%.
        $planPool = (float) ($at->pool_amount ?? 0);
        $poolsCapacity = $planPool > 0 ? $planPool : (float) $pools->sum('capacity');
        $poolsBalance = (float) $pools->sum('balance');
        $sharePct = $planPool > 0 ? round(min(100, $investment / $planPool * 100), 2) : 0.0;

        $poolToday = (float) PoolSnapshot::whereIn('pool_account_id', $poolIds)
            ->whereDate('snapshot_date', $latestSnap->snapshot_date ?? today())
            ->sum('pnl');
        $poolBalance = $poolsBalance;

        // Open (floating, unrealized) P/L — this account's proportional share.
        $poolsFloating = $pools->isNotEmpty() ? (float) $pools->sum('floating_pnl') : (float) ($pool->floating_pnl ?? 0);
        $shareWeight = $planPool > 0 ? min(1.0, $investment / $planPool) : 0.0;
        $floatingShare = round($poolsFloating * $shareWeight, 2);
        $liveRef = $pool->account_ref ?? null;

        // Forex-style running P&L: one point per profit/loss event so the line
        // moves up and down with every change (not just daily aggregates).
        $profitTx = Transaction::where('fund_account_id', $aid)
            ->where('type', 'profit')
            ->orderBy('id')
            ->get(['amount', 'created_at']);

        $run = 0.0;
        $series = [];
        foreach ($profitTx as $t) {
            $run += (float) $t->amount;
            $series[] = (object) ['allocation_date' => $t->created_at, 'net_pnl' => round($run, 2)];
        }
        // Keep the most recent ~80 movements for a dense, trading-style line.
        $chart = collect(array_slice($series, -80));

        $recent = Transaction::where('fund_account_id', $aid)
            ->whereIn('type', ['profit', 'deposit', 'withdrawal', 'referral'])
            ->latest('id')->limit(8)->get();

        $referralEarned = (float) Transaction::where('fund_account_id', $aid)->where('type', 'referral')->sum('amount');
        $announcement = \App\Models\Announcement::active()->latest()->first();

        // Spot wallets (separate products, separate currencies) for the home overview.
        $spotUsd = (float) \App\Models\SpotAccount::where('user_id', $user->id)->where('currency', 'USD')->value('balance');
        $spotInr = (float) \App\Models\SpotAccount::where('user_id', $user->id)->where('currency', 'INR')->value('balance');

        return view('client.dashboard', compact(
            'user', 'account', 'at', 'pool', 'pools', 'latestSnap', 'investment', 'balanceAfter', 'totalEarned',
            'today', 'yesterday', 'month', 'sharePct', 'poolBalance', 'poolsCapacity', 'poolToday', 'chart', 'recent',
            'poolsFloating', 'floatingShare', 'liveRef', 'withdrawable', 'runningPnl', 'referralEarned', 'announcement',
            'spotUsd', 'spotInr'
        ));
    }

    /** Live figures for the client dashboard's auto-refresh (JSON). */
    public function live(Request $request, PoolApiClient $api)
    {
        $user = $request->user();
        $account = $user->currentAccount();
        $account?->load('accountType');
        $aid = $account?->id;

        $poolIds = $account ? $account->deposits()->where('status', 'approved')->distinct()->pluck('pool_account_id')->filter() : collect();
        if ($account?->pool_account_id) {
            $poolIds = $poolIds->push($account->pool_account_id)->unique()->values();
        }
        $pools = PoolAccount::whereIn('id', $poolIds)->get();
        if ($pools->isEmpty() && ($first = PoolAccount::where('is_active', true)->first())) {
            $pools = collect([$first]);   // show the managed pool's live P/L even before investing
        }
        $investment = $account ? $account->totalDeposited() : 0.0;
        $planPool = (float) ($account?->accountType->pool_amount ?? 0);
        $shareWeight = $planPool > 0 ? min(1.0, $investment / $planPool) : 0.0;

        $poolFloating = 0.0;
        foreach ($pools as $pool) {
            $poolFloating += (float) (PoolController::liveFigures($api, $pool)['floating'] ?? 0);
        }

        $floatingShare = round($poolFloating * $shareWeight, 2);
        $today = (float) PnlAllocation::where('fund_account_id', $aid)->whereDate('allocation_date', today())->sum('net_pnl');

        return response()->json([
            'poolFloating' => round($poolFloating, 2),
            'floatingShare' => $floatingShare,
            'today' => $today,
            'at' => now()->format('H:i:s'),
        ]);
    }
}
