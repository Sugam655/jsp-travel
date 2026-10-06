<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated as BaseRedirectIfAuthenticated;
use Illuminate\Http\Request;

class RedirectIfAuthenticated extends BaseRedirectIfAuthenticated
{
    /**
     * Send an already-authenticated user to their own area: the admin
     * dashboard for administrators, the customer dashboard otherwise.
     * This is what keeps an authenticated customer who opens /login (or
     * /register) out of the admin-only /admin/dashboard route.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->user()?->isAdmin()
            ? route('admin.dashboard', absolute: false)
            : route('user.dashboard', absolute: false);
    }
}
