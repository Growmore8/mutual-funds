<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AccountRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load('accountType', 'accountRequests.accountType');

        return view('client.accounts.index', [
            'user' => $user,
            'requests' => $user->accountRequests->sortByDesc('id'),
            'accountTypes' => AccountType::orderBy('sort_order')->get(),
            'hasPending' => $user->accountRequests->where('status', 'pending')->isNotEmpty(),
        ]);
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

        $user->accountRequests()->create([
            'account_type_id' => $data['account_type_id'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('accounts.index')
            ->with('status', 'Request submitted. An admin will review your additional account shortly.');
    }
}
