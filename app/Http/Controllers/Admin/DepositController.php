<?php

namespace App\Http\Controllers\Admin;

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
    public function index(Request $request)
    {
        $deposits = Deposit::with(['user', 'poolAccount', 'accountType'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $clients = User::where('role', 'client')->orderBy('name')->get(['id', 'name', 'email', 'account_type_id']);
        $pools = PoolAccount::where('is_active', true)->get();

        return view('admin.deposits.index', compact('deposits', 'clients', 'pools'));
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

        $this->creditCapital($deposit);

        $amt = '$' . number_format((float) $deposit->amount, 2);
        \App\Models\AppNotification::push($deposit->user_id, 'deposit', 'Deposit approved', $amt . ' credited to your account.', route('client.dashboard'));
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
        \App\Models\AppNotification::push($deposit->user_id, 'deposit', 'Deposit not approved', $amt . ($reason ? ' — ' . $reason : ''), route('client.deposit.create'));
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
            $last = Transaction::where('user_id', $deposit->user_id)->latest('id')->first();
            $balanceAfter = round((float) ($last->balance_after ?? 0) + (float) $deposit->amount, 2);

            Transaction::create([
                'user_id' => $deposit->user_id,
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
    }
}
