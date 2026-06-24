<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\StatementMail;
use App\Models\AccountType;
use App\Models\KycDocument;
use App\Models\PoolAccount;
use App\Models\User;
use App\Services\StatementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = User::where('role', 'client')
            ->with('poolAccount', 'accountType', 'fundAccounts.accountType', 'fundAccounts.poolAccount')
            ->when($request->q, fn ($q) => $q->where(fn ($w) =>
                $w->where('name', 'like', "%{$request->q}%")->orWhere('email', 'like', "%{$request->q}%")))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('admin.clients.create', [
            'accountTypes' => AccountType::orderBy('sort_order')->get(),
            'pools' => PoolAccount::orderBy('account_ref')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'account_type_id' => ['nullable', 'exists:account_types,id'],
            'pool_account_id' => ['nullable', 'exists:pool_accounts,id'],
            'status' => ['required', 'in:pending,active,suspended,locked'],
        ]);

        $client = User::create($data + [
            'role' => 'client',
            'kyc_status' => 'not_submitted',
            // Admin-created accounts skip the email-OTP onboarding step.
            'email_verified_at' => now(),
            'otp_verified_at' => now(),
        ]);

        return redirect()->route('admin.clients.show', $client)->with('status', 'Client created.');
    }

    public function show(User $client)
    {
        abort_unless($client->role === 'client', 404);
        $client->load([
            'accountType', 'poolAccount',
            'fundAccounts.accountType', 'fundAccounts.poolAccount',
            'deposits.paymentMethod',
            'transactions' => fn ($q) => $q->latest()->limit(50),
            'kycDocuments',
            'accountRequests.accountType',
        ]);

        // Spot Trading — single USD base (managed here too — one place per client).
        $svc = app(\App\Services\SpotTradingService::class);
        $spotUsd = $svc->account($client->id, 'USD');
        $spotHoldings = \App\Models\SpotHolding::with('instrument')->where('user_id', $client->id)->where('qty', '>', 0)->get();
        $spotTrades = \App\Models\SpotTrade::with('instrument')->where(fn ($q) => $q->where('buyer_id', $client->id)->orWhere('seller_id', $client->id))->latest('id')->limit(15)->get();

        // Unrealized spot P&L (all holdings, USD).
        $spotPnl = round($spotHoldings->sum(fn ($h) => (float) $h->qty * (((float) ($h->instrument->last_price ?: $h->avg_price)) - (float) $h->avg_price)), 2);

        // Merged recent activity: mutual fund transactions + spot trades + spot deposits/withdrawals.
        $activity = collect();
        $client->transactions->each(fn ($t) => $activity->push((object) [
            'when' => $t->created_at, 'area' => 'Mutual Fund',
            'detail' => ucfirst($t->type) . ($t->description ? ' · ' . $t->description : ''),
            'amount' => (float) $t->amount]));
        $spotTrades->each(function ($t) use ($activity, $client) {
            $isBuy = $t->buyer_id === $client->id;
            $activity->push((object) ['when' => $t->created_at, 'area' => 'Spot',
                'detail' => ($isBuy ? 'Buy ' : 'Sell ') . $t->instrument->symbol . ' ×' . rtrim(rtrim((string) $t->qty, '0'), '.'),
                'amount' => ($isBuy ? -1 : 1) * (float) $t->qty * (float) $t->price]);
        });
        \App\Models\Deposit::where('user_id', $client->id)->where('purpose', 'spot')->latest('id')->limit(15)->get()
            ->each(fn ($d) => $activity->push((object) ['when' => $d->created_at, 'area' => 'Spot',
                'detail' => 'Deposit' . ($d->currency && $d->currency !== 'USD' ? ' · ' . number_format((float) $d->amount, 2) . ' ' . $d->currency : ''),
                'amount' => $svc->toUsd((float) $d->amount, $d->currency ?: 'USD')]));
        \App\Models\Withdrawal::where('user_id', $client->id)->where('purpose', 'spot')->latest('id')->limit(15)->get()
            ->each(fn ($w) => $activity->push((object) ['when' => $w->created_at, 'area' => 'Spot',
                'detail' => 'Withdrawal' . ($w->currency && $w->currency !== 'USD' ? ' · ' . number_format((float) $w->amount, 2) . ' ' . $w->currency : ''),
                'amount' => -1 * $svc->toUsd((float) $w->amount, $w->currency ?: 'USD')]));
        $activity = $activity->sortByDesc('when')->take(30)->values();

        return view('admin.clients.show', [
            'client' => $client,
            'accountTypes' => AccountType::orderBy('sort_order')->get(),
            'pools' => PoolAccount::orderBy('account_ref')->get(),
            'spotUsd' => $spotUsd,
            'spotHoldings' => $spotHoldings,
            'spotTrades' => $spotTrades,
            'spotPnl' => $spotPnl,
            'activity' => $activity,
        ]);
    }

    public function update(Request $request, User $client)
    {
        abort_unless($client->role === 'client', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($client->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'account_type_id' => ['nullable', 'exists:account_types,id'],
            'pool_account_id' => ['nullable', 'exists:pool_accounts,id'],
            'status' => ['required', 'in:pending,active,suspended,locked'],
        ]);

        $oldPool = $client->pool_account_id;
        $client->update($data + ['plan_locked' => $request->boolean('plan_locked')]);

        // If admin moved the client's Live ID, move their capital to that pool too.
        if ($client->pool_account_id && (int) $client->pool_account_id !== (int) $oldPool) {
            $client->deposits()->update(['pool_account_id' => $client->pool_account_id]);
        }

        return back()->with('status', 'Client updated.');
    }

    /** Approve/reject a client's KYC directly from the client page. */
    public function kycDecision(Request $request, User $client)
    {
        abort_unless($client->role === 'client', 404);
        $decision = $request->validate(['decision' => ['required', 'in:approved,rejected']])['decision'];

        $client->update([
            'kyc_status' => $decision,
            'status' => $decision === 'approved' ? 'active' : $client->status,
        ]);

        // Reflect the decision on the latest submitted document, if any.
        if ($doc = $client->kycDocuments()->where('status', 'submitted')->latest()->first()) {
            $doc->update([
                'status' => $decision,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        }

        \App\Models\AppNotification::notify(
            $client->id, 'kyc',
            $decision === 'approved' ? 'KYC approved' : 'KYC not approved',
            $decision === 'approved' ? 'Your identity is verified — full access unlocked.' : 'Please re-upload your documents.',
            route('client.dashboard'),
        );

        return back()->with('status', 'KYC ' . $decision . ' for ' . $client->name . '.');
    }

    /** Update one of a client's fund accounts (plan / pool / lock). */
    public function updateAccount(Request $request, User $client, \App\Models\FundAccount $account)
    {
        abort_unless($account->user_id === $client->id, 404);

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:60'],
            'account_type_id' => ['nullable', 'exists:account_types,id'],
            'pool_account_id' => ['nullable', 'exists:pool_accounts,id'],
        ]);

        $oldPool = $account->pool_account_id;
        $account->update($data + ['plan_locked' => $request->boolean('plan_locked')]);

        if ($account->pool_account_id && (int) $account->pool_account_id !== (int) $oldPool) {
            $account->deposits()->update(['pool_account_id' => $account->pool_account_id]);
        }

        return back()->with('status', 'Account updated.');
    }

    /** Delete one of a client's fund accounts (and its records). */
    public function destroyAccount(Request $request, User $client, \App\Models\FundAccount $account)
    {
        abort_unless($account->user_id === $client->id, 404);

        if ($client->fundAccounts()->count() <= 1) {
            return back()->with('status', 'Cannot delete the only account — delete the client instead.');
        }

        // Remove this account's financial records, then the account.
        $account->transactions()->delete();
        $account->pnlAllocations()->delete();
        $account->deposits()->delete();
        $account->withdrawals()->delete();

        $wasPrimary = $account->is_primary;
        $account->delete();

        // If we removed the primary, promote the oldest remaining account.
        if ($wasPrimary) {
            $client->fundAccounts()->orderBy('id')->first()?->update(['is_primary' => true]);
        }

        return back()->with('status', 'Account deleted.');
    }

    public function updateStatus(Request $request, User $client)
    {
        $data = $request->validate(['status' => ['required', 'in:pending,active,suspended,locked']]);
        $client->update($data);

        return back()->with('status', "Client status updated to {$data['status']}.");
    }

    /** Admin uploads a client's ID document (front + back) on their behalf. */
    public function uploadKyc(Request $request, User $client)
    {
        abort_unless($client->role === 'client', 404);

        $data = $request->validate([
            'document_number' => ['nullable', 'string', 'max:100'],
            'front' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'back' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $front = $request->file('front')->store('kyc', 'local');
        $back = $request->file('back')->store('kyc', 'local');

        KycDocument::create([
            'user_id' => $client->id,
            'doc_type' => 'identity',
            'document_number' => $data['document_number'] ?? null,
            'front_path' => $front,
            'back_path' => $back,
            'file_path' => $front,
            'status' => 'submitted',
        ]);

        if ($client->kyc_status === 'not_submitted') {
            $client->update(['kyc_status' => 'submitted']);
        }

        return back()->with('status', 'KYC document uploaded for ' . $client->name . '.');
    }

    /** Client PDF statement — download or email to the client, by period. */
    public function statement(Request $request, User $client, StatementService $svc)
    {
        abort_unless($client->role === 'client', 404);

        [$start, $end, $label] = $svc->period($request->get('period', 'month'), $request->get('from'), $request->get('to'));
        $scope = $request->get('scope', 'fund');

        // Spot / combined (all) scopes use the multi-section statement view.
        if (in_array($scope, ['spot', 'all'])) {
            $payload = [
                'client' => $client, 'name' => $client->name, 'email' => $client->email, 'code' => $client->clientCode(),
                'label' => $label, 'start' => $start, 'end' => $end, 'generatedAt' => now(), 'scope' => $scope,
                'fund' => $scope === 'all' ? $svc->data($client, $start, $end, $label) : null,
                'spot' => in_array($scope, ['spot', 'all']) ? $svc->spotSection($client, $start, $end) : null,
            ];

            if ($request->get('action') === 'email') {
                $pdf = $svc->pdfFromView('pdf.account-statement', $payload);
                try {
                    Mail::to($client->email)->send(new StatementMail($payload, $pdf?->output(), 'emails.statement-generic', 'Your GrowthCapital statement · ' . $label));
                } catch (\Throwable $e) {
                    return $request->wantsJson()
                        ? response()->json(['ok' => false, 'message' => 'Could not email statement.'], 500)
                        : back()->with('status', 'Could not email statement.');
                }

                return $request->wantsJson()
                    ? response()->json(['ok' => true, 'message' => 'Statement emailed to ' . $client->email . '.'])
                    : back()->with('status', 'Statement emailed to ' . $client->email . '.');
            }

            $pdf = $svc->pdfFromView('pdf.account-statement', $payload);

            return $pdf ? $pdf->download('GrowthCapital-Statement-' . $client->clientCode() . '.pdf')
                : view('pdf.account-statement', $payload + ['print' => true]);
        }

        $data = $svc->data($client, $start, $end, $label);

        if ($request->get('action') === 'email') {
            $pdf = $svc->pdf($data);
            try {
                Mail::to($client->email)->send(new StatementMail($data, $pdf?->output()));
            } catch (\Throwable $e) {
                if ($request->wantsJson()) {
                    return response()->json(['ok' => false, 'message' => 'Could not email statement: ' . $e->getMessage()], 500);
                }

                return back()->with('status', 'Could not email statement: ' . $e->getMessage());
            }

            if ($request->wantsJson()) {
                return response()->json(['ok' => true, 'message' => 'Statement emailed to ' . $client->email . '.']);
            }

            return back()->with('status', 'Statement emailed to ' . $client->email . '.');
        }

        $pdf = $svc->pdf($data);
        if ($pdf) {
            return $pdf->download($svc->filename($data));
        }

        return view('pdf.statement', $data + ['print' => true]);
    }

    public function destroy(User $client)
    {
        abort_unless($client->role === 'client', 404);
        $client->delete();

        return redirect()->route('admin.clients.index')->with('status', 'Client deleted.');
    }
}
