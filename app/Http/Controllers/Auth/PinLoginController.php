<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PinLoginController extends Controller
{
    /** Fast login on the login page using a previously-set app PIN. */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'pin' => ['required', 'digits_between:4,6'],
        ]);

        $key = 'pin-login:' . strtolower($request->email) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'pin' => 'Too many attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.',
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! $user->hasPin() || ! Hash::check($request->pin, $user->pin_hash)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['pin' => 'Incorrect email or PIN.']);
        }

        RateLimiter::clear($key);

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('pin_unlocked_at', now()->timestamp);

        if ($user->isAdmin()) {
            $token = Str::random(40);
            $user->forceFill(['session_token' => $token])->save();
            $request->session()->put('admin_session_token', $token);
        }

        return redirect()->intended(route('dashboard'));
    }
}
