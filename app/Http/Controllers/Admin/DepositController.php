<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FiltersClients;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\PoolAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DepositController extends Controller
{
    use FiltersClients;

    public function index(Request $request)
    {
        $search = trim((string) $request->get('q'));
        $deposits = Deposit::with(['user', 'poolAccount', 'accountType', 'fundAccount'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($search !== '', fn ($q) => $q->whereHas('user', fn ($u) => $this->matchClient($u, $search)))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $clients = User::where('role', 'client')->orderBy('name')->get(['id', 'name', 'email', 'account_type_id']);
        $pools = PoolAccount::where('is_active', true)->get();

        return view('admin.deposits.index', compact('deposits', 'clients', 'pools', 'search'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'pool_account_id' => ['required', 'exists:pool_accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'value_date' => ['required', 'date'],          // joining date for this capital
            'status' => ['required', 'in:pending,approved'],
            'reference' => ['nullable', 'string', 'max:190'],
        ]);

        $client = User::findOrFail($data['user_id']);
        $data['account_type_id'] = $client->account_type_id;
        $data['currency'] = 'USD';
        if ($data['status'] === 'approved') {
            $data['approved_at'] = now();
        }

        $deposit = Deposit::create($data);

        if ($deposit->status === 'approved') {
            $this->creditCapital($deposit);
        }

        return back()->with('status', 'Deposit recorded.');
    }

    public function slip(Deposit $deposit)
    {
        abort_if(! $deposit->proof_path || ! Storage::disk('local')->exists($deposit->proof_path), 404);

        return Storage::disk('local')->response($deposit->proof_path);
    }

    public function approve(Deposit $deposit)
    {
        if ($deposit->status === 'approved') {
            return back()->with('status', 'Already approved.');
        }

        $deposit->update([
            'status' => 'approved',
            'approved_at' => now(),
            'value_date' => $deposit->value_date ?? now()->toDateString(),
        ]);

        // Spot deposits credit the single USD trading wallet (INR amounts converted).
        if ($deposit->purpose === 'spot') {
            $svc = app(\App\Services\SpotTradingService::class);
            $usd = round($svc->toUsd((float) $deposit->amount, $deposit->currency ?: 'USD'), 2);
            $svc->adjustBalance($deposit->user_id, $usd, 'USD');
            $deposit->forceFill(['usd_amount' => $usd])->saveQuietly();   // freeze the credited USD
            $amt = '$' . number_format($usd, 2);
            \App\Models\AppNotification::notify($deposit->user_id, 'deposit', 'Spot deposit approved', $amt . ' added to your Spot wallet.', route('spot.index'));

            return back()->with('status', 'Spot deposit approved and trading balance credited (' . $amt . ').');
        }

        $this->creditCapital($deposit);

        $amt = '$' . number_format((float) $deposit->amount, 2);
        \App\Models\AppNotification::notify($deposit->user_id, 'deposit', 'Deposit approved', $amt . ' credited to your account.', route('client.dashboard'));
        Notifier::send(
            $deposit->user,
            'Your deposit has been approved',
            'Deposit approved',
            [
                'Your deposit of ' . $amt . ' has been approved and credited to your account.',
                'Your capital is now active in the pool and will start earning daily profit.',
            ],
            route('client.dashboard'),
            'Go to dashboard',
        );

        return back()->with('status', 'Deposit approved and capital credited.');
    }

    public function reject(Request $request, Deposit $deposit)
    {
        $reason = $request->input('admin_note');
        $deposit->update(['status' => 'rejected', 'admin_note' => $reason]);

        $amt = '$' . number_format((float) $deposit->amount, 2);
        \App\Models\AppNotification::notify($deposit->user_id, 'deposit', 'Deposit not approved', $amt . ($reason ? ' — ' . $reason : ''), route('client.deposit.create'));
        Notifier::send(
            $deposit->user,
            'Update on your deposit',
            'Deposit not approved',
            [
                'Your deposit of ' . $amt . ' was not approved.',
                $reason ? 'Reason: ' . $reason : 'Please re-submit with a valid slip, or contact support.',
            ],
        );

        return back()->with('status', 'Deposit rejected.');
    }

    private function creditCapital(Deposit $deposit): void
    {
        DB::transaction(function () use ($deposit) {
            $last = Transaction::where('fund_account_id', $deposit->fund_account_id)->latest('id')->first();
            $balanceAfter = round((float) ($last->balance_after ?? 0) + (float) $deposit->amount, 2);

            Transaction::create([
                'user_id' => $deposit->user_id,
                'fund_account_id' => $deposit->fund_account_id,
                'type' => 'deposit',
                'amount' => $deposit->amount,
                'currency' => 'USD',
                'balance_after' => $balanceAfter,
                'status' => 'completed',
                'description' => 'Deposit · ' . ($deposit->poolAccount->account_ref ?? 'pool'),
                'source_type' => Deposit::class,
                'source_id' => $deposit->id,
            ]);
        });

        // Re-evaluate the plan/pool for the fund account this deposit belongs to.
        optional(\App\Models\FundAccount::find($deposit->fund_account_id))->recalcPlan();
        $deposit->user->recalcPlan();
        $this->creditReferral($deposit);
    }

    /** Pay the client's referrer 1% of this deposit (on every deposit). */
    private function creditReferral(Deposit $deposit): void
    {
        $client = $deposit->user;
        if (! $client || ! $client->referred_by) {
            return;
        }

        $referrer = User::find($client->referred_by);
        $bonus = round((float) $deposit->amount * 0.01, 2);
        if (! $referrer || $bonus <= 0) {
            return;
        }

        $refAccountId = optional($referrer->primaryAccount())->id;

        DB::transaction(function () use ($referrer, $bonus, $client, $refAccountId) {
            $last = Transaction::where('fund_account_id', $refAccountId)->latest('id')->first();
            Transaction::create([
                'user_id' => $referrer->id,
                'fund_account_id' => $refAccountId,
                'type' => 'referral',
                'amount' => $bonus,
                'currency' => 'USD',
                'balance_after' => round((float) ($last->balance_after ?? 0) + $bonus, 2),
                'status' => 'completed',
                'description' => 'Referral bonus · ' . $client->name,
            ]);
        });

        \App\Models\AppNotification::notify(
            $referrer->id, 'info', 'Referral bonus earned',
            '+$' . number_format($bonus, 2) . " from {$client->name}'s deposit.",
            route('client.referrals'),
        );
    }
}
