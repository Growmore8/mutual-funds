<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AccountType;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    /** Show the "complete your details" form (used after Google sign-up). */
    public function show(Request $request)
    {
        $user = $request->user();
        if ($this->complete($user)) {
            return redirect()->route('dashboard');
        }

        return view('auth.onboarding', [
            'user' => $user,
            'accountTypes' => AccountType::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'country' => ['required', 'string', 'max:100'],
            'account_type_id' => ['required', 'exists:account_types,id'],
        ]);

        $request->user()->update($data);

        return redirect()->route('dashboard')->with('status', 'Welcome! Your profile is complete.');
    }

    /** A client is "complete" once they have phone, country and a chosen plan. */
    public static function complete($user): bool
    {
        return $user && ($user->role !== 'client' || (! empty($user->phone) && ! empty($user->country) && ! empty($user->account_type_id)));
    }
}
