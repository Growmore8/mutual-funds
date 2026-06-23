<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Twelve Data market-data client for the Spot Trading module ONLY.
 * Completely separate from the mutual-fund / CubeX pool API — different key, different config.
 */
class TwelveDataClient
{
    private string $base = 'https://api.twelvedata.com';

    private ?string $key;

    public function __construct()
    {
        $this->key = config('services.twelvedata.spot_key');
    }

    public function configured(): bool
    {
        return ! empty($this->key);
    }

    /** Latest quote (price, change %, day high/low, volume). */
    public function quote(string $symbol, ?string $exchange = null): ?array
    {
        return $this->get('/quote', $this->sym($symbol, $exchange), 2);
    }

    /** Lightweight last price. */
    public function price(string $symbol, ?string $exchange = null): ?array
    {
        return $this->get('/price', $this->sym($symbol, $exchange), 2);
    }

    /** OHLC candles for the in-house chart. */
    public function timeSeries(string $symbol, string $interval = '1day', int $outputsize = 90, ?string $exchange = null): ?array
    {
        return $this->get('/time_series', $this->sym($symbol, $exchange) + [
            'interval' => $interval,
            'outputsize' => $outputsize,
        ], 30);
    }

    /** Search instruments by name/symbol. */
    public function symbolSearch(string $query): ?array
    {
        return $this->get('/symbol_search', ['symbol' => $query, 'outputsize' => 20], 300);
    }

    /** All exchanges available on the plan (cached for a day). */
    public function exchanges(): ?array
    {
        return $this->get('/exchanges', [], 86400);
    }

    private function sym(string $symbol, ?string $exchange): array
    {
        $p = ['symbol' => $symbol];
        if ($exchange) {
            $p['exchange'] = $exchange;
        }

        return $p;
    }

    private function get(string $path, array $params, int $ttl): ?array
    {
        if (! $this->configured()) {
            return null;
        }

        $params['apikey'] = $this->key;
        $cacheKey = 'td:' . md5($path . '|' . json_encode($params));

        return Cache::remember($cacheKey, $ttl, function () use ($path, $params) {
            try {
                $res = Http::timeout(8)->get($this->base . $path, $params);
                if (! $res->ok()) {
                    return null;
                }
                $data = $res->json();
                // Twelve Data signals failure with {"status":"error", ...}
                if (is_array($data) && (($data['status'] ?? null) === 'error')) {
                    return null;
                }

                return $data;
            } catch (\Throwable $e) {
                return null;
            }
        });
    }
}
