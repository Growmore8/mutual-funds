<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpotAccount;
use App\Models\SpotHolding;
use App\Models\SpotInstrument;
use App\Models\SpotOrder;
use App\Models\SpotTrade;
use App\Models\User;
use App\Services\SpotTradingService;
use Illuminate\Http\Request;

class SpotAdminController extends Controller
{
    public function __construct(private SpotTradingService $svc) {}

    public function index()
    {
        $accounts = SpotAccount::with('user')->orderByDesc('balance')->get();
        $instruments = SpotInstrument::orderBy('sort')->get();
        $stats = [
            'traders' => SpotAccount::where('balance', '>', 0)->count(),
            'open_orders' => SpotOrder::whereIn('status', ['open', 'partial'])->where('is_maker', false)->count(),
            'trades_today' => SpotTrade::whereDate('created_at', today())->count(),
            'balance' => (float) SpotAccount::sum('balance'),
        ];

        return view('admin.spot.index', compact('accounts', 'instruments', 'stats'));
    }

    /** Per-client spot management. */
    public function client(User $client)
    {
        abort_unless($client->role === 'client', 404);

        $usd = $this->svc->account($client->id, 'USD');
        $inr = $this->svc->account($client->id, 'INR');
        $holdings = SpotHolding::with('instrument')->where('user_id', $client->id)->where('qty', '>', 0)->get();
        $orders = SpotOrder::with('instrument')->where('user_id', $client->id)->whereIn('status', ['open', 'partial'])->latest('id')->get();
        $trades = SpotTrade::with('instrument')->where(fn ($q) => $q->where('buyer_id', $client->id)->orWhere('seller_id', $client->id))->latest('id')->limit(40)->get();

        return view('admin.spot.client', compact('client', 'usd', 'inr', 'holdings', 'orders', 'trades'));
    }

    public function adjust(Request $request, User $client)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric'],
            'direction' => ['required', 'in:credit,debit'],
            'currency' => ['required', 'in:USD,INR'],
        ]);

        $delta = $data['direction'] === 'credit' ? abs($data['amount']) : -abs($data['amount']);
        $this->svc->adjustBalance($client->id, $delta, $data['currency']);

        // Record it so it shows in transactions (client + admin).
        if ($delta >= 0) {
            \App\Models\Deposit::create(['user_id' => $client->id, 'purpose' => 'spot', 'currency' => $data['currency'],
                'amount' => abs($delta), 'method' => 'Admin adjustment', 'status' => 'approved',
                'value_date' => now()->toDateString(), 'approved_at' => now()]);
        } else {
            \App\Models\Withdrawal::create(['user_id' => $client->id, 'purpose' => 'spot', 'currency' => $data['currency'],
                'amount' => abs($delta), 'method' => 'Admin adjustment', 'status' => 'approved', 'processed_at' => now()]);
        }

        $sym = $data['currency'] === 'INR' ? '₹' : '$';
        \App\Models\AppNotification::notify($client->id, 'deposit', 'Spot balance updated',
            ($delta < 0 ? '-' : '+') . $sym . number_format(abs($delta), 2) . ' on your Spot ' . $data['currency'] . ' wallet.', route('spot.index'));

        return back()->with('status', 'Spot balance updated.');
    }

    public function cancelOrder(SpotOrder $order)
    {
        $this->svc->cancelOrder($order);

        return back()->with('status', 'Order cancelled.');
    }

    /** Delete a spot trade and reverse its effect on balances + holdings (all areas). */
    public function deleteTrade(SpotTrade $trade)
    {
        $cash = (float) $trade->qty * (float) $trade->price;
        $cur = $trade->instrument->currency ?: 'USD';

        // Buyer side (refund cash, remove the bought qty).
        if ($trade->buyer_id) {
            $this->svc->adjustBalance($trade->buyer_id, $cash, $cur);
            $h = SpotHolding::where('user_id', $trade->buyer_id)->where('instrument_id', $trade->instrument_id)->first();
            if ($h) {
                $h->qty = max(0, (float) $h->qty - (float) $trade->qty);
                if ($h->qty <= 0) {
                    $h->avg_price = 0;
                }
                $h->save();
            }
        }
        // Seller side (take cash back, restore the sold qty).
        if ($trade->seller_id) {
            $this->svc->adjustBalance($trade->seller_id, -$cash, $cur);
            $h = SpotHolding::firstOrCreate(['user_id' => $trade->seller_id, 'instrument_id' => $trade->instrument_id], ['qty' => 0, 'avg_price' => 0]);
            $h->qty = (float) $h->qty + (float) $trade->qty;
            $h->save();
        }

        $trade->delete();

        return back()->with('status', 'Trade deleted and balances/holdings reversed.');
    }

    /** Wipe a client's spot account everywhere: zero wallets, clear holdings/orders/trades. */
    public function resetAccount(User $client)
    {
        \App\Models\SpotAccount::where('user_id', $client->id)->update(['balance' => 0]);
        SpotHolding::where('user_id', $client->id)->delete();
        SpotOrder::where('user_id', $client->id)->delete();
        SpotTrade::where(fn ($q) => $q->where('buyer_id', $client->id)->orWhere('seller_id', $client->id))->delete();

        return back()->with('status', 'Spot account reset — wallets zeroed and history cleared.');
    }

    public function storeInstrument(Request $request)
    {
        $data = $request->validate([
            'symbol' => ['required', 'string', 'max:40'],
            'name' => ['nullable', 'string', 'max:120'],
            'exchange' => ['nullable', 'string', 'max:40'],
            'market' => ['required', 'in:india,global,crypto,forex,commodity'],
            'type' => ['required', 'in:stock,crypto,forex,commodity,index'],
        ]);

        SpotInstrument::updateOrCreate(
            ['symbol' => $data['symbol'], 'exchange' => $data['exchange'] ?: null],
            $data + ['enabled' => true, 'sort' => (int) SpotInstrument::max('sort') + 1],
        );

        return back()->with('status', 'Instrument saved.');
    }

    public function toggleInstrument(SpotInstrument $instrument)
    {
        $instrument->update(['enabled' => ! $instrument->enabled]);

        return back()->with('status', 'Instrument ' . ($instrument->enabled ? 'enabled' : 'disabled') . '.');
    }
}
