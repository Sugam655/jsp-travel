<?php

use App\Http\Middleware\EndFrontendSession;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Modules\Bookings\Http\Middleware\PreserveBookingDraft;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'guest' => RedirectIfAuthenticated::class,
            'booking.draft' => PreserveBookingDraft::class,
            'frontend.guest' => EndFrontendSession::class,
        ]);

        // Middleware that is not in the priority list keeps the position it was
        // declared in, and auth sorts ahead of it. A guest submitting the booking
        // form is redirected to login by auth, so booking.draft has to run first
        // to keep their answers, and after the session has started to be able to
        // store them. The priority list is ordered, so placing it directly before
        // the auth contract satisfies both.
        $middleware->prependToPriorityList(
            AuthenticatesRequests::class,
            PreserveBookingDraft::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A guest submitting the booking form is sent to login with their answers
        // already saved by PreserveBookingDraft. bookings.store has no GET
        // counterpart, so resuming the request that was bounced is impossible -
        // without this the guest lands on the homepage and has to find the form
        // again. Pointing the intended URL back at the confirmation page resumes
        // the flow with every field already restored.
        //
        // The saved draft carries the exact service the guest clicked, so the
        // resume URL names it instead of dropping them on an empty confirmation
        // page: without it they would have to pick the service a second time.
        //
        // The one-time token in the URL is what says the guest had already read
        // the confirmation popup and pressed Confirm Booking, so signing in
        // finishes that booking rather than handing back a form they have to
        // submit all over again. It travels through the login redirect because
        // this is the only place that knows the submission was interrupted; the
        // token is spent on arrival, so a refresh cannot repeat it.
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->routeIs('bookings.store')) {
                return null;
            }

            $response = redirect()->guest(route('login'));

            $draft = $request->session()->get(PreserveBookingDraft::SESSION_KEY);
            $draft = is_array($draft) ? $draft : [];

            $request->session()->put('url.intended', route('bookings.create', array_filter([
                'type' => $draft['booking_type'] ?? null,
                'service_id' => $draft['service_id'] ?? null,
                'resume' => $request->session()->get(PreserveBookingDraft::SESSION_INTENT_KEY),
            ])));

            return $response;
        });
    })->create();
