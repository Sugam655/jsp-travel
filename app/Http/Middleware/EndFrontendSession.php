<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Treat the public site as a guest-only area.
 *
 * Browsing the frontend signs the visitor out, so the public pages are reached
 * the same way whether or not somebody was signed in to the AdminLTE panel
 * first: an administrator who follows a link to the marketing site, or types
 * "/", lands on a guest session and a navbar that offers Login instead of the
 * dashboard and Logout. The AdminLTE routes keep their own middleware, so an
 * administrator stays signed in while working in the panel.
 *
 * The session is invalidated and the CSRF token regenerated rather than just
 * clearing the auth guard, so the previous session identifier cannot be reused
 * to get back into the backend.
 */
class EndFrontendSession
{
    /**
     * End the authenticated session, if there is one, before the page renders.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $next($request);
    }
}
