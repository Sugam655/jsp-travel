<?php

namespace Modules\Bookings\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keep a guest's booking form answers across the sign-in step.
 *
 * The booking form is public but creating a booking is not, so a guest who
 * submits it is bounced to the login page by the auth middleware. Without this
 * the answers are gone and the customer has to retype the whole request, which
 * is the single most common way people give up on a booking form.
 *
 * Only the keys the form itself submits are kept, and each value is truncated,
 * so the session can never be used as a general purpose store for whatever a
 * client chooses to POST at this endpoint.
 *
 * The draft also records that the guest had already finished reviewing the
 * confirmation popup and pressed Confirm Booking, rather than merely looking at
 * the form. Resuming that is what stops the customer having to press the button a
 * second time once they are signed in; the one-time token in
 * SESSION_INTENT_KEY is what makes the resumption happen exactly once, so a
 * refresh or a bookmarked link can never press it on their behalf again.
 *
 * Resuming the form after sign-in is handled in bootstrap/app.php instead, because
 * the framework writes the intended URL itself when it renders the authentication
 * exception, which is after this middleware has run.
 */
class PreserveBookingDraft
{
    /**
     * Form fields worth carrying across authentication.
     *
     * @var list<string>
     */
    private const DRAFTABLE = [
        'booking_type',
        'service_id',
        'name',
        'email',
        'phone',
        'address',
        'travelers',
        'start_date',
        'end_date',
        'message',
        'policy_accepted',
        // The token the interrupted submission was carrying. Replaying the very
        // same submission is what keeps the resumed booking from being treated as
        // a second attempt at the same click.
        'submission_token',
    ];

    /**
     * Session key holding the in-progress request.
     */
    public const SESSION_KEY = 'booking_draft';

    /**
     * Session key holding the one-time token that lets a signed-in customer pick
     * up the confirmation they were interrupted on the way to.
     */
    public const SESSION_INTENT_KEY = 'booking_confirm_intent';

    /**
     * Upper bound per value, mirroring the store() validation limits closely
     * enough that a restored draft can still be submitted unchanged.
     */
    private const MAX_LENGTH = 5000;

    /**
     * Cap on the number of fields so a padded payload cannot bloat the session.
     */
    private const MAX_FIELDS = 14;

    /**
     * The one-time token that travels with the intended URL through sign-in.
     */
    public function intent(): string
    {
        return Str::random(40);
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldPreserve($request)) {
            Session::put(self::SESSION_KEY, $this->draftFrom($request));
            Session::put(self::SESSION_INTENT_KEY, $this->intent());
        }

        return $next($request);
    }

    private function shouldPreserve(Request $request): bool
    {
        if ($request->expectsJson() || ! $request->isMethod('POST')) {
            return false;
        }

        if (! $request->routeIs('bookings.store')) {
            return false;
        }

        return ! $request->user();
    }

    /**
     * @return array<string, scalar|null>
     */
    private function draftFrom(Request $request): array
    {
        $draft = [];

        foreach (self::DRAFTABLE as $field) {
            if (count($draft) >= self::MAX_FIELDS) {
                break;
            }

            if (! $request->has($field)) {
                continue;
            }

            $value = $request->input($field);

            if (is_bool($value) || is_numeric($value) || $value === null) {
                $draft[$field] = $value;

                continue;
            }

            if (is_string($value)) {
                $draft[$field] = mb_substr($value, 0, self::MAX_LENGTH);
            }
        }

        return $draft;
    }
}
