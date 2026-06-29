<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServerTiming
{
    /** Exposes server render time as "Server-Timing: app;dur=<ms>" (visible in DevTools → Network). */
    public function handle(Request $request, Closure $next): Response
    {
        $start = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $response = $next($request);
        $ms = round((microtime(true) - $start) * 1000, 1);
        $response->headers->set('Server-Timing', 'app;dur=' . $ms);

        return $response;
    }
}
