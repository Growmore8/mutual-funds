<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountType;
use App\Models\PoolAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = User::where('role', 'client')
            ->when($request->q, fn ($q) => $q->where(fn ($w) =>
                $w->where('name', 'like', "%{$request->q}%")->orWhere('email', 'like', "%{$request->q}%")))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.clients.index', compact('clients'));
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
            'account_type_id' => ['nullable', 'exists:account_types,id'],
            'pool_account_id' => ['nullable', 'exists:pool_accounts,id'],
            'status' => ['required', 'in:pending,active,suspended'],
            'kyc_status' => ['required', 'in:not_submitted,submitted,approved,rejected'],
        ]);

        $client->update($data);

        return back()->with('status', 'Client updated.');
    }

    public function updateStatus(Request $request, User $client)
    {
        $data = $request->validate(['status' => ['required', 'in:pending,active,suspended']]);
        $client->update($data);

        return back()->with('status', "Client status updated to {$data['status']}.");
    }

    public function destroy(User $client)
    {
        abort_unless($client->role === 'client', 404);
        $client->delete();

        return redirect()->route('admin.clients.index')->with('status', 'Client deleted.');
    }
}
