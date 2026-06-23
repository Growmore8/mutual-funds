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

        // Recent activity = mutual-fund transactions + spot trades, merged.
        $recent = collect();
        Transaction::where('fund_account_id', $aid)
            ->whereIn('type', ['profit', 'deposit', 'withdrawal', 'referral'])
            ->latest('id')->limit(6)->get()->each(fn ($t) => $recent->push((object) [
                'when' => $t->created_at, 'label' => $t->description ?? ucfirst($t->type), 'amount' => (float) $t->amount, 'cs' => '$',
            ]));
        \App\Models\SpotTrade::with('instrument')
            ->where(fn ($q) => $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id))
            ->latest('id')->limit(6)->get()->each(function ($t) use ($recent, $user) {
                $isBuy = $t->buyer_id === $user->id;
                $recent->push((object) ['when' => $t->created_at,
                    'label' => ($isBuy ? 'Buy ' : 'Sell ') . $t->instrument->symbol,
                    'amount' => ($isBuy ? -1 : 1) * (float) $t->qty * (float) $t->price,
                    'cs' => $t->instrument->currencySymbol()]);
            });
        \App\Models\Deposit::where('user_id', $user->id)->where('purpose', 'spot')->latest('id')->limit(5)->get()
            ->each(fn ($d) => $recent->push((object) ['when' => $d->created_at, 'label' => 'Spot deposit (' . $d->currency . ')',
                'amount' => (float) $d->amount, 'cs' => $d->currency === 'INR' ? '₹' : '$']));
        \App\Models\Withdrawal::where('user_id', $user->id)->where('purpose', 'spot')->latest('id')->limit(5)->get()
            ->each(fn ($w) => $recent->push((object) ['when' => $w->created_at, 'label' => 'Spot withdrawal (' . $w->currency . ')',
                'amount' => -1 * (float) $w->amount, 'cs' => $w->currency === 'INR' ? '₹' : '$']));
        $recent = $recent->sortByDesc('when')->take(8)->values();

        $referralEarned = (float) Transaction::where('fund_account_id', $aid)->where('type', 'referral')->sum('amount');
        $announcement = \App\Models\Announcement::active()->latest()->first();

        // Single USD spot wallet (Binance/Bybit model) for the home overview.
        $svc = app(\App\Services\SpotTradingService::class);
        $spotUsd = (float) \App\Models\SpotAccount::where('user_id', $user->id)->where('currency', 'USD')->value('balance');
        $spotHoldings = \App\Models\SpotHolding::with('instrument')->where('user_id', $user->id)->where('qty', '>', 0)->get();
        $spotFloating = round($spotHoldings->sum(fn ($h) => (float) $h->qty * (((float) ($h->instrument->last_price ?: $h->avg_price)) - (float) $h->avg_price)), 2);
        $spotEquity = round($spotUsd + $spotHoldings->sum(fn ($h) => (float) $h->qty * (float) ($h->instrument->last_price ?: $h->avg_price)), 2);

        // Total spot deposit (capital in, net of withdrawals — both BSE + NYSE, in USD).
        $spotDeposited = round(
            \App\Models\Deposit::where('user_id', $user->id)->where('purpose', 'spot')->where('status', 'approved')
                ->get(['amount', 'currency'])->sum(fn ($d) => $svc->toUsd((float) $d->amount, $d->currency))
            - \App\Models\Withdrawal::where('user_id', $user->id)->where('purpose', 'spot')->where('status', 'approved')
                ->get(['amount', 'currency'])->sum(fn ($w) => $svc->toUsd((float) $w->amount, $w->currency)), 2);

        // Total spot P&L (realized + floating) = current value − capital in.
        $spotTotalPnl = round($spotEquity - $spotDeposited, 2);

        // Combined portfolio P&L (mutual fund + total spot).
        $totalPnlUsd = round($runningPnl + $spotTotalPnl, 2);

        // Live FX rates (1 USD → currency) for the Binance-style currency switcher.
        // One call returns ~160 currencies; we curate a wide list. Cached 6h.
        $wanted = ['USD', 'AED', 'ARS', 'AUD', 'BDT', 'BHD', 'BOB', 'BRL', 'CAD', 'CHF', 'CLP', 'CNY', 'COP', 'CZK',
            'DKK', 'EGP', 'EUR', 'GBP', 'GEL', 'HKD', 'HUF', 'IDR', 'ILS', 'INR', 'JPY', 'KES', 'KRW', 'KWD', 'KZT',
            'LKR', 'MAD', 'MNT', 'MXN', 'MYR', 'NGN', 'NOK', 'NZD', 'OMR', 'PEN', 'PHP', 'PKR', 'PLN', 'QAR', 'RON',
            'RUB', 'SAR', 'SEK', 'SGD', 'THB', 'TRY', 'TWD', 'UAH', 'VND', 'ZAR'];

        $fxRates = (array) \Illuminate\Support\Facades\Cache::remember('fx.rates.map', 21600, function () use ($wanted) {
            $map = ['USD' => 1.0];
            try {
                $res = \Illuminate\Support\Facades\Http::timeout(8)->get('https://open.er-api.com/v6/latest/USD');
                if ($res->ok() && $res->json('result') === 'success') {
                    foreach ((array) $res->json('rates') as $code => $rate) {
                        if (in_array($code, $wanted, true) && (float) $rate > 0) {
                            $map[$code] = round((float) $rate, 6);
                        }
                    }
                }
            } catch (\Throwable $e) {
                // fall through to minimal map
            }
            if (count($map) < 2) {
                $map['INR'] = app(\App\Services\SpotTradingService::class)->usdInr(); // fallback so at least INR works
            }

            return $map;
        });

        return view('client.dashboard', compact(
            'user', 'account', 'at', 'pool', 'pools', 'latestSnap', 'investment', 'balanceAfter', 'totalEarned',
            'today', 'yesterday', 'month', 'sharePct', 'poolBalance', 'poolsCapacity', 'poolToday', 'chart', 'recent',
            'poolsFloating', 'floatingShare', 'liveRef', 'withdrawable', 'runningPnl', 'referralEarned', 'announcement',
            'spotUsd', 'spotDeposited', 'spotTotalPnl', 'spotFloating', 'spotEquity', 'totalPnlUsd', 'fxRates'
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
