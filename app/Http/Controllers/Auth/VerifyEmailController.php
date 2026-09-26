<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->verifiedRedirect($request);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return $this->verifiedRedirect($request);
    }

    /**
     * Send a verified user to their own area (frontend for customers,
     * the admin dashboard for administrators).
     */
    private function verifiedRedirect(EmailVerificationRequest $request): RedirectResponse
    {
        return redirect()->intended(
            $request->user()->isAdmin()
                ? route('dashboard', absolute: false).'?verified=1'
                : route('user.dashboard', absolute: false).'?verified=1'
        );
    }
}
