<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    private array $providers = ['google'];

    public function redirect(string $provider)
    {
        abort_unless(in_array($provider, $this->providers), 404);

        if (! class_exists(\Laravel\Socialite\Facades\Socialite::class) || ! config("services.$provider.client_id")) {
            return redirect()->route('login')->withErrors(['email' => ucfirst($provider) . ' login is not configured yet.']);
        }

        return \Laravel\Socialite\Facades\Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider)
    {
        abort_unless(in_array($provider, $this->providers), 404);

        if (! class_exists(\Laravel\Socialite\Facades\Socialite::class)) {
            return redirect()->route('login')->withErrors(['email' => 'Social login is not configured.']);
        }

        try {
            $social = \Laravel\Socialite\Facades\Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors(['email' => 'Could not sign in with ' . ucfirst($provider) . '. Please try again.']);
        }

        if (! $social->getEmail()) {
            return redirect()->route('login')->withErrors(['email' => 'Your ' . ucfirst($provider) . ' account has no email.']);
        }

        $user = User::where('email', $social->getEmail())->first();

        if (! $user) {
            $user = User::create([
                'name' => $social->getName() ?: $social->getNickname() ?: 'Client',
                'email' => $social->getEmail(),
                'password' => Hash::make(Str::random(40)),
                'role' => 'client',
                'status' => 'pending',
                'kyc_status' => 'not_submitted',
                'email_verified_at' => now(),
                'otp_verified_at' => now(),
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('pin_unlocked_at', now()->timestamp);

        if ($user->isAdmin()) {
            $token = Str::random(40);
            $user->forceFill(['session_token' => $token])->save();
            $request->session()->put('admin_session_token', $token);
        }

        return redirect()->route('dashboard');
    }
}
