<?php

namespace App\Services;

use App\Models\PoolAccount;
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
        // TODO: adjust the path + field mapping to the real API once provided.
        $res = Http::withToken($this->apiKey)
            ->acceptJson()
            ->timeout(20)
            ->get(rtrim($this->baseUrl, '/') . "/accounts/{$pool->account_ref}");

        $res->throw();
        $d = $res->json();

        $balance = (float) ($d['balance'] ?? $d['equity'] ?? $pool->balance);
        $equity  = (float) ($d['equity'] ?? $balance);
        $pnl     = (float) ($d['pnl'] ?? $d['daily_pnl'] ?? ($balance - (float) $pool->balance));

        return [
            'balance' => $balance,
            'equity'  => $equity,
            'pnl'     => $pnl,
            'pnl_pct' => $pool->balance > 0 ? round($pnl / (float) $pool->balance * 100, 4) : 0,
            'raw'     => $d,
        ];
    }

    private function fetchStub(PoolAccount $pool): array
    {
        // Deterministic-ish daily simulation: small positive-biased move.
        $opening = (float) $pool->balance ?: (float) $pool->capacity;
        $pct = (mt_rand(-40, 140) / 100); // -0.40% .. +1.40%
        $pnl = round($opening * $pct / 100, 2);
        $balance = round($opening + $pnl, 2);

        return [
            'balance' => $balance,
            'equity'  => $balance,
            'pnl'     => $pnl,
            'pnl_pct' => round($pct, 4),
            'raw'     => ['simulated' => true, 'pct' => $pct],
        ];
    }
}
