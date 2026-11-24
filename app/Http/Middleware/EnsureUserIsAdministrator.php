<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(in_array($request->user()?->user_type, ['owner', 'administrator'], true), 403);

        return $next($request);
    }
}
