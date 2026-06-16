<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountRequest;
use App\Services\Notifier;
use Illuminate\Http\Request;

class AccountRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = AccountRequest::with(['user', 'accountType'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.account-requests.index', compact('requests'));
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

        return back()->with('status', 'Account request rejected.');
    }
}
