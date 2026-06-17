<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AccountRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load('accountType', 'poolAccount');
        $investment = (float) $user->deposits()->where('status', 'approved')->sum('amount');
        $accountTypes = AccountType::orderBy('min_deposit')->get();
        $pendingRequest = $user->accountRequests()->with('accountType')->where('status', 'pending')->latest()->first();
        $pastRequests = $user->accountRequests()->with('accountType')->whereIn('status', ['approved', 'rejected'])->latest()->limit(5)->get();

        return view('client.accounts.index', compact('user', 'investment', 'accountTypes', 'pendingRequest', 'pastRequests'));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // Only one open request at a time.
        if ($user->accountRequests()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'account_type_id' => 'You already have a pending account request awaiting admin approval.',
            ]);
        }

        $data = $request->validate([
            'account_type_id' => ['required', 'exists:account_types,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $req = $user->accountRequests()->create([
            'account_type_id' => $data['account_type_id'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        $plan = AccountType::find($data['account_type_id']);
        \App\Models\AppNotification::notifyAdmins(
            'info',
            'New account request',
            $user->name . ' requested a new ' . ($plan->name ?? 'account') . '.',
            route('admin.account-requests.index'),
        );

        return redirect()->route('accounts.index')
            ->with('status', 'Request submitted. An admin will review your additional account shortly.');
    }
}
