<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboarded
{
    /**
     * Gate the client dashboard behind: email OTP verified, then KYC approved.
     * Admins bypass these checks.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isAdmin()) {
            return $next($request);
        }

        if (! $user->otp_verified_at) {
            return redirect()->route('otp.show');
        }

        if ($user->kyc_status !== 'approved') {
            return redirect()->route('kyc.show');
        }

        return $next($request);
    }
}
