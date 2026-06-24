<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FiltersClients;
use App\Http\Controllers\Controller;
use App\Models\AccountRequest;
use App\Services\Notifier;
use Illuminate\Http\Request;

class AccountRequestController extends Controller
{
    use FiltersClients;

    public function index(Request $request)
    {
        $search = trim((string) $request->get('q'));
        $requests = AccountRequest::with(['user', 'accountType'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($search !== '', fn ($q) => $q->whereHas('user', fn ($u) => $this->matchClient($u, $search)))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.account-requests.index', compact('requests', 'search'));
    }

    public function approve(Request $request, AccountRequest $accountRequest)
    {
        if ($accountRequest->status !== 'pending') {
            return back()->with('status', 'Already ' . $accountRequest->status . '.');
        }

        $accountRequest->update([
            'status' => 'approved',
            'processed_at' => now(),
            'admin_note' => $request->input('admin_note'),
        ]);

        // Create the actual additional fund account the client can switch into.
        $count = $accountRequest->user->fundAccounts()->count();
        $account = $accountRequest->user->fundAccounts()->create([
            'label' => 'Account ' . ($count + 1),
            'account_type_id' => $accountRequest->account_type_id,
            'pool_account_id' => optional($accountRequest->accountType)->pool_account_id,
            'is_primary' => false,
        ]);
        $accountRequest->forceFill(['fund_account_id' => $account->id])->save();

        Notifier::send(
            $accountRequest->user,
            'Your additional account is approved',
            'Account request approved',
            [
                'Your request for a ' . ($accountRequest->accountType->name ?? 'new') . ' account has been approved.',
                'You can now fund and manage it from your dashboard.',
            ],
            route('accounts.index'),
            'View my accounts',
        );

        \App\Models\AppNotification::notify($accountRequest->user_id, 'account', 'Account request approved',
            'Your ' . ($accountRequest->accountType->name ?? 'new') . ' account is ready — fund it from your dashboard.', route('accounts.index'));

        return back()->with('status', 'Additional account approved.');
    }

    public function reject(Request $request, AccountRequest $accountRequest)
    {
        if ($accountRequest->status !== 'pending') {
            return back()->with('status', 'Already ' . $accountRequest->status . '.');
        }

        $accountRequest->update([
            'status' => 'rejected',
            'processed_at' => now(),
            'admin_note' => $request->input('admin_note'),
        ]);

        Notifier::send(
            $accountRequest->user,
            'Update on your account request',
            'Account request not approved',
            [
                'Your request for a ' . ($accountRequest->accountType->name ?? 'new') . ' account was not approved at this time.',
                $request->input('admin_note') ? 'Note from our team: ' . $request->input('admin_note') : 'If you have questions, please open a support ticket from your dashboard.',
            ],
        );

        \App\Models\AppNotification::notify($accountRequest->user_id, 'account', 'Account request not approved',
            ($request->input('admin_note') ?: 'Your account request was not approved at this time.'), route('client.dashboard'));

        return back()->with('status', 'Account request rejected.');
    }
}
