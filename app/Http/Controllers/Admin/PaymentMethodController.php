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
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:bank,crypto,card,ewallet'],
            'currency' => ['nullable', 'string', 'max:20'],
            'instructions' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]) + ['is_active' => (bool) $request->boolean('is_active')];
    }
}
