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
            ->with('poolAccount', 'accountType')
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
            'deposits.paymentMethod',
            'transactions' => fn ($q) => $q->latest()->limit(50),
            'kycDocuments',
            'accountRequests.accountType',
        ]);

        return view('admin.clients.show', [
            'client' => $client,
            'accountTypes' => AccountType::orderBy('sort_order')->get(),
            'pools' => PoolAccount::orderBy('account_ref')->get(),
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
        $data = $svc->data($client, $start, $end, $label);

        if ($request->get('action') === 'email') {
            $pdf = $svc->pdf($data);
            try {
                Mail::to($client->email)->send(new StatementMail($data, $pdf?->output()));
            } catch (\Throwable $e) {
                return back()->with('status', 'Could not email statement: ' . $e->getMessage());
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
