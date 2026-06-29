<?php

namespace App\Services;

use App\Models\SpotInstrument;
use App\Models\SpotOrder;

/**
 * Seeds liquidity for the internal order book: posts "house" maker bids/asks around
 * the Twelve Data reference price so client orders always have a counterparty.
 * Run on a schedule (e.g. every minute) to keep quotes fresh.
 */
class SpotLiquiditySeeder
{
    public function __construct(
        private TwelveDataClient $td,
        private SpotTradingService $engine,
        private CubexMarketClient $cubex,
    ) {}

    private float $spread = 0.0008;   // 0.08% per level

    private int $levels = 5;

    private float $depth = 1000;      // qty per maker level

    private int $logosThisRun = 0;          // cap logo lookups per run to avoid bursts

    private int $logosPerRun = 8;

    public function seedAll(): int
    {
        $this->logosThisRun = 0;
        $count = 0;
        $instruments = SpotInstrument::enabled()->get();

        // Preferred source: our own CubeX external prices API — ONE request for every symbol,
        // no per-symbol rate limit (unlike TwelveData direct).
        if ($this->cubex->configured()) {
            $prices = $this->cubex->prices($instruments->pluck('symbol')->all());
            if (! empty($prices)) {
                foreach ($instruments as $ins) {
                    $native = (float) ($prices[$ins->symbol] ?? 0);
                    if ($native > 0 && $this->seedFromPrice($ins, $native)) {
                        $count++;
                    }
                }

                return $count;
            }
        }

        // Fallback: TwelveData, batched per exchange.
        foreach ($instruments->groupBy(fn ($i) => (string) $i->exchange) as $exchange => $group) {
            foreach ($group->chunk(100) as $chunk) {
                $quotes = $this->td->quoteBatch($chunk->pluck('symbol')->all(), $exchange ?: null);
                foreach ($chunk as $ins) {
                    $q = $quotes[$ins->symbol] ?? null;
                    $native = (float) ($q['close'] ?? $q['price'] ?? 0);
                    if ($native > 0 && $this->seedFromPrice($ins, $native)) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    /** Single-instrument refresh (admin / fallback). */
    public function seed(SpotInstrument $ins): bool
    {
        $q = $this->td->quote($ins->symbol, $ins->exchange) ?? $this->td->price($ins->symbol, $ins->exchange);
        $native = (float) ($q['close'] ?? $q['price'] ?? 0);
        if ($native <= 0) {
            return false;
        }

        return $this->seedFromPrice($ins, $native);
    }

    private function seedFromPrice(SpotInstrument $ins, float $native): bool
    {
        // Single USD base: store every instrument's price in USD (INR markets converted).
        $price = round($this->engine->toUsd($native, $ins->currency), 6);

        $ins->update(['last_price' => $price]);

        // Backfill the real logo once (Twelve Data /logo), then it's cached on the row.
        if (empty($ins->logo_url) && $this->logosThisRun < $this->logosPerRun) {
            $this->logosThisRun++;
            if ($url = $this->td->logo($ins->symbol, $ins->exchange)) {
                $ins->update(['logo_url' => $url]);
            }
        }

        // Auto-execute resting limit orders the live price has reached (fallback when nobody's watching).
        $this->engine->triggerLimitOrders($ins, $price);

        // Clear previous maker quotes (leave real client orders alone).
        SpotOrder::where('instrument_id', $ins->id)->where('is_maker', true)
            ->whereIn('status', ['open', 'partial'])->update(['status' => 'cancelled']);

        // Level 0 sits at the exact reference price so market orders fill at qty × price.
        // Levels 1..n add depth around it for the order-book display.
        for ($i = 0; $i < $this->levels; $i++) {
            SpotOrder::create([
                'user_id' => null, 'instrument_id' => $ins->id, 'side' => 'sell', 'type' => 'limit',
                'price' => round($price * (1 + $this->spread * $i), 6), 'qty' => $this->depth,
                'filled_qty' => 0, 'status' => 'open', 'is_maker' => true,
            ]);
            SpotOrder::create([
                'user_id' => null, 'instrument_id' => $ins->id, 'side' => 'buy', 'type' => 'limit',
                'price' => round($price * (1 - $this->spread * $i), 6), 'qty' => $this->depth,
                'filled_qty' => 0, 'status' => 'open', 'is_maker' => true,
            ]);
        }

        return true;
    }
}
