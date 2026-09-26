<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Allow the request through only for administrator accounts.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->isAdmin(),
            403,
            'This area is restricted to administrators.'
        );

        return $next($request);
    }
}
