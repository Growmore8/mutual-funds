<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUnlocked
{
    /** Minutes a PIN/biometric unlock stays valid before re-prompting. */
    private const TTL_MINUTES = 30;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // No app lock configured -> nothing to enforce.
        if (! $user || ! $user->hasPin()) {
            return $next($request);
        }

        $unlockedAt = $request->session()->get('pin_unlocked_at');

        if ($unlockedAt && now()->timestamp - $unlockedAt < self::TTL_MINUTES * 60) {
            return $next($request);
        }

        return redirect()->route('lock.show');
    }
}
