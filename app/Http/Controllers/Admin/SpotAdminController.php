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
