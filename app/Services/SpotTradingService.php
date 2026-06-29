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

    /** Live USD/INR rate (cached 1h, fallback 84). */
    public function usdInr(): float
    {
        // Never call the network here — read the cached/stored rate (warmed by the cron). Page render must not block.
        $base = (float) \Illuminate\Support\Facades\Cache::get('fx.usdinr', 0);
        if ($base <= 0) {
            $base = (float) \App\Models\Setting::get('fx_usdinr_last', 84);
        }

        return round($base * (1 + $this->markupPct() / 100), 4);
    }

    /** Refresh FX from the network — call ONLY from the scheduler (CLI), never inline in a web request. */
    public function refreshFx(): void
    {
        try {
            $p = (float) (app(TwelveDataClient::class)->price('USD/INR')['price'] ?? 0);
            if ($p > 0) {
                \Illuminate\Support\Facades\Cache::put('fx.usdinr', $p, 7200);
                \App\Models\Setting::put('fx_usdinr_last', $p);
            }
        } catch (\Throwable $e) {
            // keep the last known rate
        }
    }

    /** Admin markup % applied on top of every live FX rate (all countries). */
    public function markupPct(): float
    {
        return (float) \App\Models\Setting::get('fx_markup_pct', 0);
    }

    /** Full 1 USD → currency rate map (live, cached 6h). */
    public function ratesMap(): array
    {
        $map = (array) \Illuminate\Support\Facades\Cache::remember('fx.rates.full', 21600, function () {
            try {
                $res = \Illuminate\Support\Facades\Http::timeout(8)->get('https://open.er-api.com/v6/latest/USD');
                if ($res->ok() && $res->json('result') === 'success') {
                    return (array) $res->json('rates');
                }
            } catch (\Throwable $e) {
                // fall through
            }

            return [];
        });
        // Apply the admin markup % to every currency so all countries get the same treatment.
        $f = 1 + $this->markupPct() / 100;
        $out = [];
        foreach ($map as $code => $rate) {
            $out[strtoupper($code)] = (float) $rate * $f;
        }
        $out['INR'] = $this->usdInr(); // INR uses the live USD/INR feed + markup
        $out['USD'] = 1.0;

        return $out;
    }

    /** 1 USD → given currency (live, cached 6h). Used for deposit/withdraw conversion. */
    public function usdRate(string $currency): float
    {
        $c = strtoupper($currency);
        if ($c === 'USD') {
            return 1.0;
        }
        $map = $this->ratesMap();
        if (! empty($map[$c]) && (float) $map[$c] > 0) {
            return (float) $map[$c];
        }

        return $c === 'INR' ? $this->usdInr() : 1.0;
    }

    /** Convert a native amount/price to the single USD base. */
    public function toUsd(float $amount, ?string $currency): float
    {
        if ($currency === 'INR') {
            $r = $this->usdInr();

            return $r > 0 ? $amount / $r : $amount / 84;
        }

        return $amount; // already USD
    }

    /** Credit / debit a client's spot wallet for a given currency (used by funding + admin). */
    public function adjustBalance(int $userId, float $delta, string $currency = 'USD'): SpotAccount
    {
        $acc = SpotAccount::firstOrCreate(['user_id' => $userId, 'currency' => $currency], ['balance' => 0]);
        $acc->increment('balance', $delta);

        return $acc->fresh();
    }

    public function account(int $userId, string $currency = 'USD'): SpotAccount
    {
        return SpotAccount::firstOrCreate(['user_id' => $userId, 'currency' => $currency], ['balance' => 0]);
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

        $cur = 'USD'; // single USD base — instrument prices are stored in USD

        return DB::transaction(function () use ($userId, $instrument, $side, $type, $price, $qty, $cur) {
            $acc = SpotAccount::lockForUpdate()->firstOrCreate(['user_id' => $userId, 'currency' => $cur], ['balance' => 0]);

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

            // Cap a market BUY by the taker's remaining balance (in the instrument's currency).
            if ($taker->side === 'buy') {
                $bal = (float) SpotAccount::where('user_id', $taker->user_id)->where('currency', 'USD')->value('balance');
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
        $cur = 'USD'; // prices are stored in USD; the spot wallet is single-currency USD

        // Buyer (skip if house maker).
        if ($buy->user_id) {
            SpotAccount::where('user_id', $buy->user_id)->where('currency', $cur)->decrement('balance', $cash);
            $h = SpotHolding::firstOrCreate(['user_id' => $buy->user_id, 'instrument_id' => $instrument->id], ['qty' => 0, 'avg_price' => 0]);
            $newQty = (float) $h->qty + $qty;
            $h->avg_price = $newQty > 0 ? (((float) $h->qty * (float) $h->avg_price) + $cash) / $newQty : 0;
            $h->qty = $newQty;
            $h->save();
        }

        // Seller (skip if house maker).
        if ($sell->user_id) {
            SpotAccount::where('user_id', $sell->user_id)->where('currency', $cur)->increment('balance', $cash);
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

    /**
     * Trigger resting limit orders against the live market price.
     * Buy limit fills when the live price falls to/below its price; sell limit when it rises to/above.
     * Fills execute at the live price against the house. Returns the number of orders touched.
     */
    public function triggerLimitOrders(SpotInstrument $instrument, float $livePrice): int
    {
        if ($livePrice <= 0) {
            return 0;
        }
        $cur = 'USD';
        $touched = 0;

        DB::transaction(function () use ($instrument, $livePrice, $cur, &$touched) {
            // Buy limits priced at/above the live price are "in the money".
            $buys = SpotOrder::where('instrument_id', $instrument->id)->where('side', 'buy')->where('type', 'limit')
                ->whereIn('status', ['open', 'partial'])->whereNotNull('user_id')
                ->where('price', '>=', $livePrice - self::EPS)
                ->lockForUpdate()->orderBy('price', 'desc')->orderBy('id')->get();
            foreach ($buys as $o) {
                $touched += $this->fillAgainstHouse($o, $instrument, $livePrice, $cur) ? 1 : 0;
            }

            // Sell limits priced at/below the live price are "in the money".
            $sells = SpotOrder::where('instrument_id', $instrument->id)->where('side', 'sell')->where('type', 'limit')
                ->whereIn('status', ['open', 'partial'])->whereNotNull('user_id')
                ->where('price', '<=', $livePrice + self::EPS)
                ->lockForUpdate()->orderBy('price', 'asc')->orderBy('id')->get();
            foreach ($sells as $o) {
                $touched += $this->fillAgainstHouse($o, $instrument, $livePrice, $cur) ? 1 : 0;
            }
        });

        return $touched;
    }

    /** Fill a resting limit order's remaining qty at $price against the house. */
    private function fillAgainstHouse(SpotOrder $order, SpotInstrument $instrument, float $price, string $cur): bool
    {
        $fill = $order->remaining();
        if ($fill <= self::EPS) {
            return false;
        }

        if ($order->side === 'buy') {
            $bal = (float) SpotAccount::where('user_id', $order->user_id)->where('currency', $cur)->value('balance');
            $fill = min($fill, $price > 0 ? $bal / $price : 0);
            if ($fill <= self::EPS) {
                return false; // can't afford — leave it resting
            }
        } else {
            $h = SpotHolding::where('user_id', $order->user_id)->where('instrument_id', $instrument->id)->first();
            $fill = min($fill, $h ? (float) $h->qty : 0);
            if ($fill <= self::EPS) {
                $order->update(['status' => 'cancelled']); // nothing left to sell
                return false;
            }
        }

        $house = SpotOrder::create([
            'user_id' => null, 'instrument_id' => $instrument->id,
            'side' => $order->side === 'buy' ? 'sell' : 'buy', 'type' => 'limit',
            'price' => $price, 'qty' => $fill, 'filled_qty' => 0, 'status' => 'open', 'is_maker' => true,
        ]);

        $this->settle($order, $house, $fill, $price, $instrument);

        $order->refresh();
        $done = (float) $order->filled_qty + self::EPS >= (float) $order->qty;
        $order->update(['status' => $done ? 'filled' : 'partial']);
        $house->update(['status' => 'filled']);

        // Tell the client their resting limit order executed.
        \App\Models\AppNotification::notify($order->user_id, 'trade',
            'Limit order ' . ($done ? 'filled' : 'partially filled'),
            ucfirst($order->side) . ' ' . $instrument->symbol . ' ×' . rtrim(rtrim((string) $fill, '0'), '.') . ' @ $' . number_format($price, 2),
            route('spot.index', ['symbol' => $instrument->symbol]));

        return true;
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
