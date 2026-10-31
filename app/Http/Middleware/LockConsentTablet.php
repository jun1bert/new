<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LockConsentTablet
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('consent_tablet')) {
            if ($request->user()?->id !== $request->session()->get('consent_tablet.staff_id')) {
                $request->session()->forget('consent_tablet');
            } elseif (! $request->routeIs('studio.consents.tablet', 'studio.consents.sign', 'studio.consents.unlock', 'logout')) {
                return redirect()->route('studio.consents.tablet', [], 303)->header('Cache-Control', 'no-store, private');
            }
        }

        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        if ($request->user()) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
