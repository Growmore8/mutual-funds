<?php

namespace App\Services;

use App\Models\SpotInstrument;
use App\Models\SpotOrder;

/**
 * Seeds liquidity for the internal order book: posts "house" maker bids/asks around
 * the live reference price (from the CubeX prices API) so client orders always have a
 * counterparty. Run on a schedule (e.g. every minute) to keep quotes fresh.
 */
class SpotLiquiditySeeder
{
    public function __construct(
        private SpotTradingService $engine,
        private CubexMarketClient $cubex,
    ) {}

    private float $spread = 0.0008;   // 0.08% per level

    private int $levels = 5;

    private float $depth = 1000;      // qty per maker level

    public function seedAll(): int
    {
        if (! $this->cubex->configured()) {
            return 0;
        }

        $count = 0;
        $instruments = SpotInstrument::enabled()->get();
        $prices = $this->fetchPrices($instruments);

        foreach ($instruments as $ins) {
            $native = (float) ($prices[$this->cubexSymbol($ins->symbol)] ?? 0);
            if ($native > 0 && $this->seedFromPrice($ins, $native)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Lightweight: update only last_price (+ trigger limit orders) from CubeX.
     * Used by the spot:stream worker for sub-minute live prices without rebuilding the book.
     */
    public function refreshPrices(): int
    {
        if (! $this->cubex->configured()) {
            return 0;
        }

        $count = 0;
        $instruments = SpotInstrument::enabled()->get();
        $prices = $this->fetchPrices($instruments);

        foreach ($instruments as $ins) {
            $native = (float) ($prices[$this->cubexSymbol($ins->symbol)] ?? 0);
            if ($native <= 0) {
                continue;
            }
            $price = round($this->engine->toUsd($native, $ins->currency), 6);
            $ins->update(['last_price' => $price]);
            $this->engine->triggerLimitOrders($ins, $price);
            $count++;
        }

        return $count;
    }

    /** Fetch CubeX prices for the given instruments (chunked + paused to avoid rate-limit). */
    private function fetchPrices($instruments): array
    {
        $prices = [];
        $batches = $instruments->map(fn ($i) => $this->cubexSymbol($i->symbol))->unique()->chunk(50)->values();
        foreach ($batches as $n => $batch) {
            if ($n > 0) {
                usleep(350000); // 0.35s between requests
            }
            foreach ($this->cubex->prices($batch->values()->all()) as $sym => $p) {
                $prices[strtoupper($sym)] = $p;
            }
        }

        return $prices;
    }

    /** Single-instrument refresh (admin). */
    public function seed(SpotInstrument $ins): bool
    {
        $key = $this->cubexSymbol($ins->symbol);
        $native = (float) ($this->cubex->prices([$key])[$key] ?? 0);
        if ($native <= 0) {
            return false;
        }

        return $this->seedFromPrice($ins, $native);
    }

    /** CubeX symbol form: no slash, upper-case (BTC/USD -> BTCUSD, AAPL -> AAPL). */
    private function cubexSymbol(string $symbol): string
    {
        return str_replace('/', '', strtoupper($symbol));
    }

    private function seedFromPrice(SpotInstrument $ins, float $native): bool
    {
        // Single USD base: store every instrument's price in USD (INR markets converted).
        $price = round($this->engine->toUsd($native, $ins->currency), 6);

        $ins->update(['last_price' => $price]);

        // Auto-execute resting limit orders the live price has reached (fallback when nobody's watching).
        $this->engine->triggerLimitOrders($ins, $price);

        // Remove previous house maker quotes entirely (DELETE, not cancel) so the table
        // doesn't accumulate millions of dead rows. Real client orders are untouched.
        SpotOrder::where('instrument_id', $ins->id)->where('is_maker', true)->delete();

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
