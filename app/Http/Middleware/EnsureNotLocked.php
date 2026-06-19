<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotLocked
{
    /**
     * Block actions for a client whose account is "locked" (violation).
     * They can still VIEW pages, but cannot perform actions, export or email
     * statements, withdraw, deposit, etc. Admins are unaffected.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isAdmin() && $user->status === 'locked') {
            $msg = 'Your account is restricted (view-only). Please contact support.';

            if ($request->expectsJson()) {
                abort(403, $msg);
            }

            return redirect()->back()->withErrors(['locked' => $msg]);
        }

        return $next($request);
    }
}
