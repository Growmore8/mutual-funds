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

        // Live prices from our own CubeX API — ONE request for every symbol, no rate limit.
        $prices = $this->cubex->prices($instruments->pluck('symbol')->all());
        foreach ($instruments as $ins) {
            $native = (float) ($prices[$ins->symbol] ?? 0);
            if ($native > 0 && $this->seedFromPrice($ins, $native)) {
                $count++;
            }
        }

        return $count;
    }

    /** Single-instrument refresh (admin). */
    public function seed(SpotInstrument $ins): bool
    {
        $native = (float) ($this->cubex->prices([$ins->symbol])[$ins->symbol] ?? 0);
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
