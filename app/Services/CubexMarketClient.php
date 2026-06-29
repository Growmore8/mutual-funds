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
            $res = Http::withHeaders(['x-api-key' => $this->key])->timeout(8)->get($this->url, $params);
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
}
