<?php

namespace App\Services;

use App\Models\SpotAccount;
use App\Models\SpotHolding;
use App\Models\SpotInstrument;
use App\Models\SpotOrder;
use App\Models\SpotTrade;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Internal spot order book + matching engine (price-time priority).
 * Clients trade against each other and against seeded liquidity (the "house" maker).
 * Completely separate from the mutual-fund pool.
 */
class SpotTradingService
{
    private const EPS = 0.0000001;

    /** Credit / debit a client's spot wallet (used by funding + admin). */
    public function adjustBalance(int $userId, float $delta): SpotAccount
    {
        $acc = SpotAccount::firstOrCreate(['user_id' => $userId], ['balance' => 0]);
        $acc->increment('balance', $delta);

        return $acc->fresh();
    }

    public function account(int $userId): SpotAccount
    {
        return SpotAccount::firstOrCreate(['user_id' => $userId], ['balance' => 0]);
    }

    /** Place a client order and match it immediately. */
    public function placeOrder(int $userId, SpotInstrument $instrument, string $side, string $type, ?float $price, float $qty): SpotOrder
    {
        if ($qty <= 0) {
            throw new RuntimeException('Quantity must be greater than zero.');
        }
        if ($type === 'limit' && (! $price || $price <= 0)) {
            throw new RuntimeException('Limit orders need a price.');
        }

        return DB::transaction(function () use ($userId, $instrument, $side, $type, $price, $qty) {
            $acc = SpotAccount::lockForUpdate()->firstOrCreate(['user_id' => $userId], ['balance' => 0]);

            if ($side === 'buy') {
                $estPrice = $type === 'limit' ? $price : ($this->bestAsk($instrument->id) ?? (float) $instrument->last_price ?? 0);
                $need = $qty * (float) $estPrice;
                if ((float) $acc->balance + self::EPS < $need) {
                    throw new RuntimeException('Insufficient balance.');
                }
            } else {
                $h = SpotHolding::where('user_id', $userId)->where('instrument_id', $instrument->id)->first();
                if (! $h || (float) $h->qty + self::EPS < $qty) {
                    throw new RuntimeException('Insufficient holdings to sell.');
                }
            }

            $order = SpotOrder::create([
                'user_id' => $userId,
                'instrument_id' => $instrument->id,
                'side' => $side,
                'type' => $type,
                'price' => $type === 'limit' ? $price : null,
                'qty' => $qty,
                'filled_qty' => 0,
                'status' => 'open',
                'is_maker' => false,
            ]);

            $this->match($order, $instrument);

            return $order->fresh();
        });
    }

    public function cancelOrder(SpotOrder $order): void
    {
        if (in_array($order->status, ['open', 'partial'])) {
            $order->update(['status' => 'cancelled']);
        }
    }

    private function match(SpotOrder $taker, SpotInstrument $instrument): void
    {
        $oppSide = $taker->side === 'buy' ? 'sell' : 'buy';

        $q = SpotOrder::where('instrument_id', $instrument->id)
            ->where('side', $oppSide)
            ->whereIn('status', ['open', 'partial'])
            ->whereNotNull('price')
            ->lockForUpdate();

        $q = $taker->side === 'buy'
            ? $q->orderBy('price', 'asc')->orderBy('id', 'asc')   // buy hits lowest asks first
            : $q->orderBy('price', 'desc')->orderBy('id', 'asc'); // sell hits highest bids first

        $remaining = $taker->remaining();

        foreach ($q->get() as $maker) {
            if ($remaining <= self::EPS) {
                break;
            }
            if ($maker->user_id && $maker->user_id === $taker->user_id) {
                continue; // don't self-trade
            }

            $mp = (float) $maker->price;

            if ($taker->type === 'limit') {
                if ($taker->side === 'buy' && $mp > (float) $taker->price + self::EPS) {
                    break;
                }
                if ($taker->side === 'sell' && $mp < (float) $taker->price - self::EPS) {
                    break;
                }
            }

            $fill = min($remaining, $maker->remaining());

            // Cap a market BUY by the taker's remaining balance.
            if ($taker->side === 'buy') {
                $bal = (float) SpotAccount::where('user_id', $taker->user_id)->value('balance');
                $affordable = $mp > 0 ? $bal / $mp : 0;
                $fill = min($fill, $affordable);
            }
            if ($fill <= self::EPS) {
                break;
            }

            $this->settle($taker, $maker, $fill, $mp, $instrument);
            $remaining -= $fill;
        }

        // Final status: market leftovers are cancelled (don't rest); limit leftovers stay open/partial.
        $filled = (float) $taker->fresh()->filled_qty;
        if ($filled + self::EPS >= (float) $taker->qty) {
            $taker->update(['status' => 'filled']);
        } elseif ($taker->type === 'market') {
            $taker->update(['status' => $filled > 0 ? 'filled' : 'cancelled']);
        } else {
            $taker->update(['status' => $filled > 0 ? 'partial' : 'open']);
        }
    }

    private function settle(SpotOrder $taker, SpotOrder $maker, float $qty, float $price, SpotInstrument $instrument): void
    {
        $buy = $taker->side === 'buy' ? $taker : $maker;
        $sell = $taker->side === 'buy' ? $maker : $taker;

        // Move fill into each order.
        $taker->increment('filled_qty', $qty);
        $maker->increment('filled_qty', $qty);
        $maker->refresh();
        if ((float) $maker->filled_qty + self::EPS >= (float) $maker->qty) {
            $maker->update(['status' => 'filled']);
        } else {
            $maker->update(['status' => 'partial']);
        }

        $cash = $qty * $price;

        // Buyer (skip if house maker).
        if ($buy->user_id) {
            SpotAccount::where('user_id', $buy->user_id)->decrement('balance', $cash);
            $h = SpotHolding::firstOrCreate(['user_id' => $buy->user_id, 'instrument_id' => $instrument->id], ['qty' => 0, 'avg_price' => 0]);
            $newQty = (float) $h->qty + $qty;
            $h->avg_price = $newQty > 0 ? (((float) $h->qty * (float) $h->avg_price) + $cash) / $newQty : 0;
            $h->qty = $newQty;
            $h->save();
        }

        // Seller (skip if house maker).
        if ($sell->user_id) {
            SpotAccount::where('user_id', $sell->user_id)->increment('balance', $cash);
            $h = SpotHolding::firstOrCreate(['user_id' => $sell->user_id, 'instrument_id' => $instrument->id], ['qty' => 0, 'avg_price' => 0]);
            $h->qty = max(0, (float) $h->qty - $qty);
            if ($h->qty <= self::EPS) {
                $h->avg_price = 0;
            }
            $h->save();
        }

        SpotTrade::create([
            'instrument_id' => $instrument->id,
            'price' => $price,
            'qty' => $qty,
            'buyer_id' => $buy->user_id,
            'seller_id' => $sell->user_id,
            'buy_order_id' => $buy->id,
            'sell_order_id' => $sell->id,
        ]);

        $instrument->update(['last_price' => $price]);
    }

    public function bestAsk(int $instrumentId): ?float
    {
        return SpotOrder::where('instrument_id', $instrumentId)->where('side', 'sell')
            ->whereIn('status', ['open', 'partial'])->whereNotNull('price')->min('price');
    }

    public function bestBid(int $instrumentId): ?float
    {
        return SpotOrder::where('instrument_id', $instrumentId)->where('side', 'buy')
            ->whereIn('status', ['open', 'partial'])->whereNotNull('price')->max('price');
    }
}
