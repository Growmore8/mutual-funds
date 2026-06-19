<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laragear\WebAuthn\Http\Requests\AssertedRequest;
use Laragear\WebAuthn\Http\Requests\AssertionRequest;
use Laragear\WebAuthn\Http\Requests\AttestationRequest;
use Laragear\WebAuthn\Http\Requests\AttestedRequest;

class WebAuthnController extends Controller
{
    /* ---------- Registration (logged-in user adds a passkey) ---------- */

    public function registerOptions(AttestationRequest $request)
    {
        // Platform authenticator (Face ID / fingerprint), user-presence only.
        return $request->fastRegistration()->toCreate();
    }

    public function register(AttestedRequest $request)
    {
        $request->save();

        return response()->json(['ok' => true]);
    }

    /* ---------- Unlock (logged-in user proves a passkey to lift app-lock) ---------- */

    public function unlockOptions(AssertionRequest $request)
    {
        // Scope the challenge to the current user's own credentials only.
        return $request->toVerify(['email' => $request->user()->email]);
    }

    public function unlock(AssertedRequest $request)
    {
        $current = $request->user()->id;

        // Validate the assertion. login() returns the credential owner (or null).
        $user = $request->login();

        if (! $user || $user->id !== $current) {
            return response()->json(['ok' => false], 422);
        }

        $request->session()->put('pin_unlocked_at', now()->timestamp);

        return response()->json(['ok' => true]);
    }

    /* ---------- Passwordless login (biometric fast login on the login page) ---------- */

    public function loginOptions(AssertionRequest $request)
    {
        // Optional email scopes to that user's passkeys; otherwise discoverable.
        return $request->toVerify($request->only('email'));
    }

    public function login(AssertedRequest $request)
    {
        $user = $request->login();   // verifies the assertion AND logs the user in

        if (! $user) {
            return response()->json(['ok' => false], 422);
        }

        $request->session()->regenerate();
        $request->session()->put('pin_unlocked_at', now()->timestamp);

        if ($user->isAdmin()) {
            $token = \Illuminate\Support\Str::random(40);
            $user->forceFill(['session_token' => $token])->save();
            $request->session()->put('admin_session_token', $token);
        }

        return response()->json(['ok' => true, 'redirect' => route('dashboard', absolute: false)]);
    }

    /* ---------- Manage ---------- */

    public function destroy(Request $request)
    {
        $request->user()->webAuthnCredentials()->delete();

        return back()->with('status', 'Biometric unlock disabled.');
    }
}
