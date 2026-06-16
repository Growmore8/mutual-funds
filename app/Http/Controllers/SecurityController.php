<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class SecurityController extends Controller
{
    /** Security settings: PIN + biometric. */
    public function index(Request $request)
    {
        return view('client.security', [
            'user' => $request->user(),
            'hasPin' => $request->user()->hasPin(),
            'hasPasskey' => $request->user()->webAuthnCredentials()->exists(),
        ]);
    }

    public function setPin(Request $request)
    {
        $data = $request->validate([
            'pin' => ['required', 'digits_between:4,6', 'confirmed'],
        ], [], ['pin' => 'PIN']);

        $user = $request->user();
        $user->pin_hash = Hash::make($data['pin']);
        $user->save();

        // Setting a PIN counts as an unlock for this session.
        $request->session()->put('pin_unlocked_at', now()->timestamp);

        return back()->with('status', 'App-lock PIN saved.');
    }

    public function removePin(Request $request)
    {
        $user = $request->user();
        $user->pin_hash = null;
        $user->save();

        return back()->with('status', 'App-lock PIN removed.');
    }

    /** Lock screen. */
    public function showLock(Request $request)
    {
        if (! $request->user()->hasPin()) {
            return redirect()->route('client.dashboard');
        }

        return view('client.lock', [
            'user' => $request->user(),
            'hasPasskey' => $request->user()->webAuthnCredentials()->exists(),
        ]);
    }

    public function unlock(Request $request)
    {
        $key = 'pin-unlock:' . $request->user()->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'pin' => 'Too many attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.',
            ]);
        }

        $request->validate(['pin' => ['required', 'digits_between:4,6']]);

        if (! Hash::check($request->pin, $request->user()->pin_hash)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['pin' => 'Incorrect PIN.']);
        }

        RateLimiter::clear($key);
        $request->session()->put('pin_unlocked_at', now()->timestamp);

        return redirect()->intended(route('client.dashboard'));
    }

    public function lockNow(Request $request)
    {
        $request->session()->forget('pin_unlocked_at');

        return redirect()->route('lock.show');
    }
}
