<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SingleAdminSession
{
    /**
     * Enforce one active admin session. When an admin logs in elsewhere, the
     * user's stored session_token changes; any older session no longer matches
     * and is signed out with a notice.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAdmin() && $user->session_token) {
            if ($request->session()->get('admin_session_token') !== $user->session_token) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'You were signed out because your account was opened on another device.',
                ]);
            }
        }

        return $next($request);
    }
}
