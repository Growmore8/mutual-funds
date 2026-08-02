<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * CubeX external market-data API client (Spot Trading prices).
 * One request returns live prices for many symbols — used instead of per-symbol
 * TwelveData calls to avoid rate limits. Prices are in each symbol's native currency.
 *
 *   GET {prices_url}?symbols=AAPL,RELIANCE   header: x-api-key: ck_live_...
 *   { "ok": true, "prices": { "AAPL": {"bid":..,"ask":..,"price":283.5,"category":"stocks"}, ... }, "ts": ... }
 */
class CubexMarketClient
{
    private ?string $url;

    private ?string $key;

    public function __construct()
    {
        $this->url = config('services.cubex.prices_url');
        $this->key = config('services.cubex.key');
    }

    public function configured(): bool
    {
        return ! empty($this->url) && ! empty($this->key);
    }

    /**
     * Fetch live prices. Returns a map of symbol => native price (float).
     *
     * @param  array<int,string>  $symbols  limit to these symbols (empty = all)
     * @return array<string,float>
     */
    public function prices(array $symbols = []): array
    {
        if (! $this->configured()) {
            return [];
        }

        $params = [];
        if (! empty($symbols)) {
            $params['symbols'] = implode(',', array_values(array_unique($symbols)));
        }

        try {
            $res = Http::withHeaders(['x-api-key' => $this->key])->timeout(4)->connectTimeout(3)->get($this->url, $params);
            if (! $res->ok()) {
                return [];
            }
            $data = $res->json();
            if (! is_array($data) || ! ($data['ok'] ?? false)) {
                return [];
            }

            $out = [];
            foreach (($data['prices'] ?? []) as $symbol => $p) {
                $price = (float) ($p['price'] ?? $p['ask'] ?? $p['bid'] ?? 0);
                if ($price > 0) {
                    $out[$symbol] = $price;
                }
            }

            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Fetch OHLC candles for one symbol (for the price chart).
     *
     *   GET {candles_url}?symbol=EURUSD&interval=1h&limit=150   header: x-api-key: ck_live_...
     *   { "ok": true, "candles": [ {"time":1719648000,"open":..,"high":..,"low":..,"close":..,"volume":0}, ... ] }
     *
     * Candles come back oldest-first, prices in the symbol's native currency.
     *
     * @return array<int,array{time:int,open:float,high:float,low:float,close:float,volume:float}>
     */
    public function candles(string $symbol, string $interval = '1h', int $limit = 150): array
    {
        if (! $this->configured()) {
            return [];
        }

        // Candles live on the same host as prices: .../v1/prices -> .../v1/candles (overridable).
        $url = config('services.cubex.candles_url');
        if (empty($url)) {
            $url = str_replace('/prices', '/candles', (string) $this->url);
        }

        try {
            $res = Http::withHeaders(['x-api-key' => $this->key])->timeout(6)->connectTimeout(3)
                ->get($url, ['symbol' => strtoupper($symbol), 'interval' => $interval, 'limit' => $limit]);
            if (! $res->ok()) {
                return [];
            }
            $data = $res->json();
            if (! is_array($data) || ! ($data['ok'] ?? false)) {
                return [];
            }

            $out = [];
            foreach (($data['candles'] ?? []) as $c) {
                $close = (float) ($c['close'] ?? 0);
                if ($close <= 0) {
                    continue;
                }
                $out[] = [
                    'time' => (int) ($c['time'] ?? 0),
                    'open' => (float) ($c['open'] ?? $close),
                    'high' => (float) ($c['high'] ?? $close),
                    'low' => (float) ($c['low'] ?? $close),
                    'close' => $close,
                    'volume' => (float) ($c['volume'] ?? 0),
                ];
            }

            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
