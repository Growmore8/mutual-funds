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

        if ($user && ! $user->isAdmin()) {
            $msg = null;

            if ($user->status === 'locked') {
                $msg = 'Your account is restricted (view-only). Please contact support.';
            } elseif ($request->routeIs('spot.*')) {
                // Spot trading is governed by the dedicated spot lock (independent of MF accounts).
                if (! $user->spot_active) {
                    $msg = 'Your spot trading account is deactivated. Please contact support.';
                } elseif ($user->spot_locked) {
                    $msg = 'Your spot trading account is locked (view-only). Please contact support.';
                }
            } else {
                // Mutual-fund actions follow the selected fund account's own lock/active flags.
                $acc = $user->currentAccount();
                if ($acc && ! $acc->active) {
                    $msg = 'This account is deactivated. Switch to another account or contact support.';
                } elseif ($acc && $acc->locked) {
                    $msg = 'This account is locked (view-only). Please contact support.';
                }
            }

            if ($msg) {
                if ($request->expectsJson()) {
                    abort(403, $msg);
                }

                return redirect()->back()->withErrors(['locked' => $msg]);
            }
        }

        return $next($request);
    }
}
