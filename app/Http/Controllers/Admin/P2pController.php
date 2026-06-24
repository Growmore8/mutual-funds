<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\P2pMerchant;
use App\Models\P2pOrder;
use Illuminate\Http\Request;

class P2pController extends Controller
{
    public function index()
    {
        $merchants = P2pMerchant::orderBy('side')->orderBy('sort')->orderBy('id')->get();
        $orders = P2pOrder::with('user', 'merchant')->latest('id')->limit(50)->get();

        return view('admin.p2p.index', compact('merchants', 'orders'));
    }

    public function store(Request $request)
    {
        $data = $this->validateMerchant($request);
        P2pMerchant::create($data);

        return back()->with('status', 'Merchant added.');
    }

    public function update(Request $request, P2pMerchant $merchant)
    {
        $merchant->update($this->validateMerchant($request));

        return back()->with('status', 'Merchant updated.');
    }

    public function toggle(P2pMerchant $merchant)
    {
        $merchant->update(['is_active' => ! $merchant->is_active]);

        return back()->with('status', 'Merchant ' . ($merchant->is_active ? 'enabled' : 'disabled') . '.');
    }

    public function destroy(P2pMerchant $merchant)
    {
        $merchant->delete();

        return back()->with('status', 'Merchant deleted.');
    }

    public function orderStatus(Request $request, P2pOrder $order)
    {
        $data = $request->validate(['status' => ['required', 'in:pending,completed,cancelled']]);
        $order->update(['status' => $data['status']]);

        \App\Models\AppNotification::notify($order->user_id, 'p2p', 'P2P order ' . $data['status'],
            ucfirst($order->side) . ' ' . $order->asset_amount . ' ' . $order->asset . ' · ' . $order->status, route('p2p.index'));

        return back()->with('status', 'Order marked ' . $data['status'] . '.');
    }

    private function validateMerchant(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'side' => ['required', 'in:buy,sell'],
            'asset' => ['required', 'string', 'max:10'],
            'currency' => ['required', 'string', 'max:8'],
            'price' => ['required', 'numeric', 'min:0'],
            'available' => ['required', 'numeric', 'min:0'],
            'min_limit' => ['required', 'numeric', 'min:0'],
            'max_limit' => ['required', 'numeric', 'min:0'],
            'pay_methods' => ['nullable', 'string', 'max:255'],
            'completion' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'orders_30d' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
