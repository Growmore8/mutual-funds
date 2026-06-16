<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountRequest;
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

        return back()->with('status', 'Account request rejected.');
    }
}
