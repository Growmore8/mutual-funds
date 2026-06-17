<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $methods = PaymentMethod::orderBy('sort_order')->get();

        return view('admin.payment-methods.index', compact('methods'));
    }

    public function create()
    {
        return view('admin.payment-methods.form', ['method' => new PaymentMethod(['is_active' => true, 'type' => 'bank'])]);
    }

    public function store(Request $request)
    {
        PaymentMethod::create($this->validated($request));

        return redirect()->route('admin.payment-methods.index')->with('status', 'Payment method created.');
    }

    public function edit(PaymentMethod $payment_method)
    {
        return view('admin.payment-methods.form', ['method' => $payment_method]);
    }

    public function update(Request $request, PaymentMethod $payment_method)
    {
        $payment_method->update($this->validated($request));

        return redirect()->route('admin.payment-methods.index')->with('status', 'Payment method updated.');
    }

    public function destroy(PaymentMethod $payment_method)
    {
        $payment_method->delete();

        return back()->with('status', 'Payment method deleted.');
    }

    private function validated(Request $request): array
    {
        $v = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:crypto,upi,bank'],
            'instructions' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
            // crypto
            'network' => ['nullable', 'string', 'max:40'],
            'currency' => ['nullable', 'string', 'max:20'],
            'wallet' => ['nullable', 'string', 'max:255'],
            // upi
            'provider' => ['nullable', 'string', 'max:60'],
            'upi_id' => ['nullable', 'string', 'max:140'],
            // bank
            'account_name' => ['nullable', 'string', 'max:140'],
            'account_number' => ['nullable', 'string', 'max:60'],
            'bank_name' => ['nullable', 'string', 'max:140'],
            'ifsc' => ['nullable', 'string', 'max:40'],
        ]);

        [$details, $address] = match ($v['type']) {
            'crypto' => [['network' => $v['network'] ?? null, 'currency' => $v['currency'] ?? null, 'wallet' => $v['wallet'] ?? null], $v['wallet'] ?? null],
            'upi' => [['provider' => $v['provider'] ?? null, 'upi_id' => $v['upi_id'] ?? null], $v['upi_id'] ?? null],
            default => [['account_name' => $v['account_name'] ?? null, 'account_number' => $v['account_number'] ?? null, 'bank_name' => $v['bank_name'] ?? null, 'ifsc' => $v['ifsc'] ?? null], $v['account_number'] ?? null],
        };

        return [
            'name' => $v['name'],
            'type' => $v['type'],
            'network' => $v['network'] ?? null,
            'currency' => $v['currency'] ?? null,
            'address' => $address,
            'details' => $details,
            'instructions' => $v['instructions'] ?? null,
            'sort_order' => $v['sort_order'],
            'is_active' => (bool) $request->boolean('is_active'),
        ];
    }
}
