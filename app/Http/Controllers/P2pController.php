<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\P2pMerchant;
use App\Models\P2pOrder;
use Illuminate\Http\Request;

class P2pController extends Controller
{
    public function index(Request $request)
    {
        $side = $request->get('side') === 'sell' ? 'sell' : 'buy';
        $merchants = P2pMerchant::active()->where('side', $side)
            ->orderBy('sort')->orderBy('id')->get();

        $orders = P2pOrder::with('merchant')->where('user_id', $request->user()->id)
            ->latest('id')->limit(10)->get();

        return view('client.p2p.index', compact('side', 'merchants', 'orders'));
    }

    public function order(Request $request)
    {
        $data = $request->validate([
            'p2p_merchant_id' => ['required', 'exists:p2p_merchants,id'],
            'fiat_amount' => ['required', 'numeric', 'min:1'],
        ]);

        $m = P2pMerchant::active()->findOrFail($data['p2p_merchant_id']);
        $fiat = round((float) $data['fiat_amount'], 2);
        if ($fiat < (float) $m->min_limit || ($m->max_limit > 0 && $fiat > (float) $m->max_limit)) {
            return back()->with('status', 'Amount must be between ' . $m->curSym() . number_format($m->min_limit, 0) . ' and ' . $m->curSym() . number_format($m->max_limit, 0) . '.');
        }

        $order = P2pOrder::create([
            'user_id' => $request->user()->id,
            'p2p_merchant_id' => $m->id,
            'side' => $m->side,                 // client's action mirrors the ad's side
            'asset' => $m->asset,
            'currency' => $m->currency,
            'price' => $m->price,
            'fiat_amount' => $fiat,
            'asset_amount' => $m->price > 0 ? round($fiat / (float) $m->price, 4) : 0,
            'status' => 'pending',
        ]);

        AppNotification::notifyAdmins('p2p', 'New P2P order',
            $request->user()->name . ' · ' . ucfirst($m->side) . ' ' . $order->asset_amount . ' ' . $m->asset . ' (' . $m->curSym() . number_format($fiat, 2) . ') with ' . $m->name,
            route('admin.p2p.index'));

        return back()->with('status', 'Order placed with ' . $m->name . '. They will be in touch to complete the trade.');
    }
}
