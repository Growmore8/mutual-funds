<?php

namespace App\Services;

use App\Models\PoolAccount;
use App\Models\PoolSnapshot;
use Illuminate\Support\Facades\Http;

/**
 * Client for the external trading/pool server REST API.
 *
 * When POOL_API_URL is set it calls the real endpoint; otherwise it returns
 * simulated data so the PnL pipeline is fully testable before the API spec
 * is available. Swap the endpoint/field mapping in fetch() once you have it.
 */
class PoolApiClient
{
    public function __construct(
        private ?string $baseUrl = null,
        private ?string $apiKey = null,
    ) {
        $this->baseUrl ??= config('services.pool.url');
        $this->apiKey ??= config('services.pool.key');
    }

    public function isLive(): bool
    {
        return ! empty($this->baseUrl);
    }

    /**
     * Return a normalised snapshot for the given pool account.
     *
     * @return array{balance: float, equity: float, pnl: float, pnl_pct: float, raw: array}
     */
    public function snapshot(PoolAccount $pool): array
    {
        return $this->isLive()
            ? $this->fetchLive($pool)
            : $this->fetchStub($pool);
    }

    private function fetchLive(PoolAccount $pool): array
    {
        // CubeX external API:
        //   GET {url}?accountId={ref}   header: x-api-key
        //   -> { "ok": true, "accountId": "...", "pnl": <closed P&L>, "currency": "USD" }
        $res = Http::withHeaders(['x-api-key' => $this->apiKey])
            ->acceptJson()
            ->timeout(20)
            ->get($this->baseUrl, ['accountId' => $pool->account_ref]);

        $res->throw();
        $d = $res->json();

        // CubeX returns the account's *cumulative* closed P&L. Convert it to the
        // day's delta = current total − everything already booked on prior days.
        $cumulative = (float) ($d['pnl'] ?? 0);
        $priorTotal = (float) PoolSnapshot::where('pool_account_id', $pool->id)
            ->whereDate('snapshot_date', '<', now()->toDateString())
            ->sum('pnl');

        $daily   = round($cumulative - $priorTotal, 2);
        $opening = (float) $pool->balance;
        $balance = round($opening + $daily, 2);

        // Floating (unrealized) P&L — present only if CubeX adds it to the
        // response (floating_pnl / floatingPnl / flt_pnl), else 0.
        $floating = (float) ($d['floating_pnl'] ?? $d['floatingPnl'] ?? $d['flt_pnl'] ?? 0);
        $equity   = isset($d['equity']) ? (float) $d['equity'] : round($balance + $floating, 2);

        return [
            'balance'  => $balance,
            'equity'   => $equity,
            'pnl'      => $daily,
            'floating' => $floating,
            'pnl_pct'  => $opening > 0 ? round($daily / $opening * 100, 4) : 0,
            'raw'      => $d,
        ];
    }

    private function fetchStub(PoolAccount $pool): array
    {
        // Deterministic-ish daily simulation: small positive-biased move.
        $opening = (float) $pool->balance ?: (float) $pool->capacity;
        $pct = (mt_rand(-40, 140) / 100); // -0.40% .. +1.40%
        $pnl = round($opening * $pct / 100, 2);
        $balance = round($opening + $pnl, 2);

        $floating = round($opening * (mt_rand(-60, 180) / 100) / 100, 2);

        return [
            'balance'  => $balance,
            'equity'   => round($balance + $floating, 2),
            'pnl'      => $pnl,
            'floating' => $floating,
            'pnl_pct'  => round($pct, 4),
            'raw'      => ['simulated' => true, 'pct' => $pct],
        ];
    }
}
