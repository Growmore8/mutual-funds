<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WithdrawalMethodController extends Controller
{
    public function index(Request $request)
    {
        return view('client.payout.index', [
            'methods' => $request->user()->withdrawalMethods,
        ]);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'type' => ['required', 'in:crypto,bank,upi'],
            'network' => ['nullable', 'string', 'max:40'],
            'currency' => ['nullable', 'string', 'max:20'],
            'wallet' => ['nullable', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:60'],
            'upi_id' => ['nullable', 'string', 'max:140'],
            'account_name' => ['nullable', 'string', 'max:140'],
            'account_number' => ['nullable', 'string', 'max:60'],
            'bank_name' => ['nullable', 'string', 'max:140'],
            'ifsc' => ['nullable', 'string', 'max:40'],
        ]);

        // No third-party withdrawals: a bank payout name must match the client's own name.
        if ($v['type'] === 'bank') {
            $norm = fn ($s) => preg_replace('/[^a-z]/', '', strtolower((string) $s));
            $acct = $norm($v['account_name'] ?? '');
            $self = $norm($request->user()->name);
            if ($acct === '' || ! ($acct === $self || str_contains($acct, $self) || str_contains($self, $acct))) {
                throw ValidationException::withMessages([
                    'account_name' => 'The bank account name must match your own registered name (' . $request->user()->name . '). Third-party withdrawals are not allowed.',
                ]);
            }
        }

        $details = match ($v['type']) {
            'crypto' => ['network' => $v['network'] ?? null, 'currency' => $v['currency'] ?? 'USDT', 'wallet' => $v['wallet'] ?? null],
            'upi' => ['provider' => $v['provider'] ?? null, 'upi_id' => $v['upi_id'] ?? null],
            default => ['account_name' => $v['account_name'] ?? null, 'account_number' => $v['account_number'] ?? null, 'bank_name' => $v['bank_name'] ?? null, 'ifsc' => $v['ifsc'] ?? null],
        };

        $request->user()->withdrawalMethods()->create([
            'type' => $v['type'],
            'details' => $details,
        ]);

        return back()->with('status', 'Payout method added.');
    }

    public function destroy(Request $request, WithdrawalMethod $payout)
    {
        abort_unless($payout->user_id === $request->user()->id, 403);
        $payout->delete();

        return back()->with('status', 'Payout method removed.');
    }
}
