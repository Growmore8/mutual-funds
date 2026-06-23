<?php

namespace App\Http\Controllers;

use App\Models\SpotHolding;
use App\Models\SpotInstrument;
use App\Models\SpotOrder;
use App\Models\SpotTrade;
use App\Services\SpotTradingService;
use App\Services\TwelveDataClient;
use Illuminate\Http\Request;

class SpotController extends Controller
{
    public function __construct(private SpotTradingService $svc, private TwelveDataClient $td) {}

    public function index(Request $request)
    {
        $instruments = SpotInstrument::enabled()->orderBy('sort')->get();
        $selected = $instruments->firstWhere('symbol', $request->get('symbol'))
            ?? ($request->get('market') ? $instruments->firstWhere('market', $request->get('market')) : null)
            ?? $instruments->first();
        $cur = $selected->currency ?? 'USD';

        $user = $request->user();
        $usd = $this->svc->account($user->id, 'USD');
        $inr = $this->svc->account($user->id, 'INR');
        $account = $cur === 'INR' ? $inr : $usd;
        $holdings = SpotHolding::with('instrument')->where('user_id', $user->id)->where('qty', '>', 0)->get();

        // Spot P&L for the ACTIVE currency — kept entirely separate from the mutual-fund pool.
        $curHoldings = $holdings->filter(fn ($h) => ($h->instrument->currency ?: 'USD') === $cur);
        $holdingsValue = $curHoldings->sum(fn ($h) => (float) $h->qty * (float) ($h->instrument->last_price ?: $h->avg_price));
        $holdingsCost = $curHoldings->sum(fn ($h) => (float) $h->qty * (float) $h->avg_price);
        $unrealized = round($holdingsValue - $holdingsCost, 2);
        $equity = round((float) $account->balance + $holdingsValue, 2);
        $orders = SpotOrder::with('instrument')->where('user_id', $user->id)
            ->whereIn('status', ['open', 'partial'])->latest('id')->get();
        $trades = SpotTrade::with('instrument')->where(fn ($q) => $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id))
            ->latest('id')->limit(20)->get();

        return view('client.spot.index', compact('instruments', 'selected', 'cur', 'usd', 'inr', 'account', 'holdings', 'orders', 'trades', 'holdingsValue', 'unrealized', 'equity'));
    }

    /** Live quote (price + change) for one instrument. */
    public function quote(Request $request)
    {
        $ins = SpotInstrument::findOrFail($request->get('id'));
        // /price is the most real-time value; /quote gives the day change %.
        $p = $this->td->price($ins->symbol, $ins->exchange);
        $q = $this->td->quote($ins->symbol, $ins->exchange);
        $price = (float) ($p['price'] ?? $q['close'] ?? $ins->last_price ?? 0);

        return response()->json([
            'price' => $price,
            'change' => (float) ($q['percent_change'] ?? 0),
            'name' => $q['name'] ?? $ins->name,
            'last' => $price,
        ])->header('Cache-Control', 'no-store');
    }

    /** OHLC candles for the in-house chart. */
    public function candles(Request $request)
    {
        $ins = SpotInstrument::findOrFail($request->get('id'));
        $interval = $request->get('interval', '1day');
        $data = $this->td->timeSeries($ins->symbol, $interval, 90, $ins->exchange);
        $values = collect($data['values'] ?? [])->reverse()->values()->map(fn ($c) => [
            'time' => $c['datetime'],
            'close' => (float) $c['close'],
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
