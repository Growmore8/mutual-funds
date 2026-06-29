<?php

namespace App\Http\Controllers;

use App\Models\SpotHolding;
use App\Models\SpotInstrument;
use App\Models\SpotOrder;
use App\Models\SpotTrade;
use App\Services\CubexMarketClient;
use App\Services\SpotTradingService;
use App\Services\TwelveDataClient;
use Illuminate\Http\Request;

class SpotController extends Controller
{
    public function __construct(private SpotTradingService $svc, private TwelveDataClient $td, private CubexMarketClient $cubex) {}

    public function index(Request $request)
    {
        $instruments = SpotInstrument::enabled()->orderBy('sort')->get();
        $selected = $instruments->firstWhere('symbol', $request->get('symbol'))
            ?? ($request->get('market') ? $instruments->firstWhere('market', $request->get('market')) : null)
            ?? $instruments->first();
        $cur = 'USD'; // single USD base — every spot price/balance is in USD now

        $user = $request->user();
        $account = $this->svc->account($user->id, 'USD');
        $holdings = SpotHolding::with('instrument')->where('user_id', $user->id)->where('qty', '>', 0)->get();

        // Spot P&L (all holdings, USD) — kept entirely separate from the mutual-fund pool.
        $holdingsValue = $holdings->sum(fn ($h) => (float) $h->qty * (float) ($h->instrument->last_price ?: $h->avg_price));
        $holdingsCost = $holdings->sum(fn ($h) => (float) $h->qty * (float) $h->avg_price);
        $unrealized = round($holdingsValue - $holdingsCost, 2);
        $equity = round((float) $account->balance + $holdingsValue, 2);

        // Total spot deposit (capital in, net of withdrawals; NSE + NYSE in USD) and total P&L.
        $spotDeposited = round(
            \App\Models\Deposit::where('user_id', $user->id)->where('purpose', 'spot')->where('status', 'approved')
                ->get(['amount', 'currency', 'usd_amount'])->sum(fn ($d) => $d->usd_amount !== null ? (float) $d->usd_amount : $this->svc->toUsd((float) $d->amount, $d->currency))
            - \App\Models\Withdrawal::where('user_id', $user->id)->where('purpose', 'spot')->where('status', 'approved')
                ->get(['amount', 'currency', 'usd_amount'])->sum(fn ($w) => $w->usd_amount !== null ? (float) $w->usd_amount : $this->svc->toUsd((float) $w->amount, $w->currency)), 2);
        $spotTotalPnl = round($equity - $spotDeposited, 2);

        $orders = SpotOrder::with('instrument')->where('user_id', $user->id)
            ->whereIn('status', ['open', 'partial'])->latest('id')->get();
        $trades = SpotTrade::with('instrument')->where(fn ($q) => $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id))
            ->latest('id')->limit(20)->get();

        return view('client.spot.index', compact('instruments', 'selected', 'cur', 'account', 'holdings', 'orders', 'trades', 'holdingsValue', 'unrealized', 'equity', 'spotDeposited', 'spotTotalPnl'));
    }

    /** Markets list page (all NYSE + NSE + Crypto instruments). */
    public function markets()
    {
        $instruments = SpotInstrument::enabled()->orderBy('sort')->get();

        return view('client.markets.index', compact('instruments'));
    }

    /** Batched live quotes for the markets list (shared cache, light on the API). */
    public function marketQuotes()
    {
        $data = \Illuminate\Support\Facades\Cache::remember('markets.quotes.v3', 5, function () {
            return SpotInstrument::enabled()->get()
                ->mapWithKeys(fn ($i) => [$i->id => ['price' => (float) $i->last_price, 'change' => 0]])
                ->all();
        });

        return response()->json($data)->header('Cache-Control', 'no-store');
    }

    /** Live quote (price + change) for one instrument. */
    public function quote(Request $request)
    {
        $ins = SpotInstrument::findOrFail($request->get('id'));
        // Live price from CubeX (single symbol, no slash); fall back to the stored last price.
        $key = str_replace('/', '', strtoupper($ins->symbol));
        $native = (float) ($this->cubex->prices([$key])[$key] ?? 0);
        $price = $native > 0 ? round($this->svc->toUsd($native, $ins->currency), 6) : (float) $ins->last_price;

        // Auto-execute any resting limit orders the live price has now reached.
        if ($price > 0) {
            $ins->update(['last_price' => $price]);
            $this->svc->triggerLimitOrders($ins, $price);
        }

        return response()->json([
            'price' => $price,
            'change' => 0,
            'name' => $ins->name,
            'last' => $price,
        ])->header('Cache-Control', 'no-store');
    }

    /** Live prices for the markets list (id => USD price) — fast DB read, refreshed by spot:seed. */
    public function prices(Request $request)
    {
        return response()->json(
            SpotInstrument::enabled()->pluck('last_price', 'id')->map(fn ($p) => (float) $p)
        )->header('Cache-Control', 'no-store');
    }

    /** OHLC candles for the in-house chart. */
    public function candles(Request $request)
    {
        $ins = SpotInstrument::findOrFail($request->get('id'));
        $interval = $request->get('interval', '1day');
        $data = $this->td->timeSeries($ins->symbol, $interval, 90, $ins->exchange);
        $values = collect($data['values'] ?? [])->reverse()->values()->map(fn ($c) => [
            'time' => $c['datetime'],
            'close' => round($this->svc->toUsd((float) $c['close'], $ins->currency), 6),
        ]);

        return response()->json(['values' => $values]);
    }

    /** Aggregated order book for one instrument. */
    public function book(Request $request)
    {
        $id = (int) $request->get('id');

        $agg = fn ($side, $dir) => SpotOrder::where('instrument_id', $id)->where('side', $side)
            ->whereIn('status', ['open', 'partial'])->whereNotNull('price')
            ->selectRaw('price, SUM(qty - filled_qty) as q')->groupBy('price')
            ->orderBy('price', $dir)->limit(8)->get()
            ->map(fn ($r) => ['price' => (float) $r->price, 'qty' => round((float) $r->q, 4)]);

        $ins = SpotInstrument::find($id);

        return response()->json([
            'asks' => $agg('sell', 'asc'),
            'bids' => $agg('buy', 'desc'),
            'last' => (float) ($ins->last_price ?? 0),
        ]);
    }

    public function order(Request $request)
    {
        $data = $request->validate([
            'instrument_id' => ['required', 'exists:spot_instruments,id'],
            'side' => ['required', 'in:buy,sell'],
            'type' => ['required', 'in:market,limit'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'qty' => ['required', 'numeric', 'min:0.000001'],
        ]);

        $ins = SpotInstrument::findOrFail($data['instrument_id']);

        try {
            $order = $this->svc->placeOrder(
                $request->user()->id, $ins, $data['side'], $data['type'],
                isset($data['price']) ? (float) $data['price'] : null, (float) $data['qty'],
            );
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => ucfirst($data['side']) . ' order ' . $order->status . ' (' . rtrim(rtrim((string) $order->filled_qty, '0'), '.') . ' filled).',
        ]);
    }

    public function cancel(Request $request, SpotOrder $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $this->svc->cancelOrder($order);

        return back()->with('status', 'Order cancelled.');
    }
}
