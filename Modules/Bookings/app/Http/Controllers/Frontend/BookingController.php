<?php

namespace Modules\Bookings\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Modules\Bookings\Http\Middleware\PreserveBookingDraft;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\BookingChangeRequest;
use Modules\Bookings\Models\BookingSetting;
use Modules\Bookings\Services\AvailabilityService;
use Modules\Bookings\Services\BookingRulesService;
use Modules\Bookings\Services\BookingWorkflowService;
use Modules\Bookings\Services\CancellationPolicyService;
use Modules\Bookings\Services\PaymentCalculationService;
use Modules\Bookings\Services\PriceCalculator;
use Modules\Hotels\Models\Hotel;
use Modules\Tours\Models\Tour;
use Modules\Transport\Models\TransportVehicle;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BookingController extends Controller
{
    /**
     * Session key holding submission tokens that have already produced a booking.
     */
    private const SUBMITTED_TOKENS_SESSION_KEY = 'booking_submitted_tokens';

    /**
     * How many consumed tokens to remember, to bound the session payload.
     */
    private const MAX_REMEMBERED_SUBMISSIONS = 10;

    /**
     * Confirm and submit a booking for one exact service.
     *
     * The service arrives as ?type= plus either ?slug= (a "Book Now"/"Rent" link
     * from the search results or a listing) or ?service_id= (the resume link
     * written when a guest was sent to sign in). Both name the same real record,
     * so the form can only ever create a booking for the service the customer
     * actually clicked - there is no service picker here to widen that choice.
     *
     * Without a specific service there is nothing to confirm, so the customer is
     * sent back to booking.search to choose one rather than being shown an empty
     * form.
     *
     * This route is intentionally public: opening the form is not the same as
     * creating a booking, so a guest reaches it from any Book Now link and is
     * only asked to sign in when submitting via bookings.store.
     */
    public function create(Request $request, PriceCalculator $prices, AvailabilityService $availability): View|RedirectResponse
    {
        $type = $request->query('type');
        $slug = $request->query('slug');
        $serviceId = $request->query('service_id');

        if (blank($type) || (blank($slug) && blank($serviceId))) {
            return redirect()->route('booking.search');
        }

        abort_unless(in_array($type, Booking::TYPES, true), 404);

        $service = filled($slug)
            ? $this->resolveBySlug($type, (string) $slug)
            : $this->resolveById($type, (int) $serviceId);

        abort_unless($service !== null, 404);

        // A guest who was interrupted *after* reading the confirmation popup has
        // already answered everything the popup asks, so handing the form back for
        // a second press of the same button would be asking them to review a
        // booking twice. Their confirmation is finished here instead.
        if ($resumed = $this->resumeConfirmedBooking($request, $type, $service)) {
            return $resumed;
        }

        $this->restoreGuestBookingDraft();

        $period = $this->initialPeriod($type, $service, $request);

        $rulesService = new BookingRulesService;

        $rules = $rulesService->validate(
            $type,
            $service,
            $period['start_date'],
            $period['end_date'],
            $period['travelers']
        );

        /* Whether the customer has actually been asked about these dates yet.

           An untouched first GET is not a failed answer to a question nobody has
           asked, so it renders as a clean form: no required-date errors, no
           blocked Review button, nothing telling them off for a date they have
           not chosen yet. The moment they arrive with dates - from the query
           string, a restored draft or a failed submit - or the session carries
           errors, the period is something they have engaged with and the rules
           are reported against it.

           This is presentation only. bookings.store revalidates the same period
           with the same service on every submission. */
        $attempted = $request->session()->has('errors')
            || filled($period['start_date'])
            || filled($period['end_date']);

        // Availability is resolved for the opening state so the page states
        // plainly whether this service can actually be booked for the dates it
        // was opened with, instead of implying the form will work.
        $availabilityResult = ['available' => false, 'reason' => null];

        if ($rules['valid']) {
            $availabilityResult = $availability->check(
                $type,
                (int) $service->id,
                (string) $period['start_date'],
                $period['end_date'],
                null,
                $period['travelers']
            );
        }

        return view('frontend.booking', [
            'service' => $service,
            'serviceType' => $type,
            'types' => Booking::TYPE_LABELS,
            'period' => $period,
            'maxTravelers' => $rulesService->maxTravelers($type, $service),
            'rentalDurations' => $type === 'vehicle' ? $this->rentalDurationOptions() : [],
            'rules' => $rules,
            'availability' => $availabilityResult,
            'attempted' => $attempted,
            'quote' => $rules['valid']
                ? $prices->quote($type, $service, $period['start_date'], $period['end_date'], $period['travelers'])
                : null,
            'submissionToken' => $this->submissionToken(),
        ]);
    }

    /**
     * The rental lengths offered for a vehicle, bounded by the configured maximum
     * so the control cannot offer a booking the rules will reject.
     *
     * @return list<int>
     */
    protected function rentalDurationOptions(): array
    {
        $max = (int) BookingSetting::getWithDefault('max_rental_days');

        return collect([1, 2, 3, 5, 7, 14, 30])
            ->filter(fn (int $days) => $days <= $max)
            ->values()
            ->all();
    }

    /**
     * Issue a fresh single-use token for this rendering of the booking form.
     *
     * A new value on every render is deliberate: it is only ever compared against
     * tokens this session has already turned into a booking, so rotating it costs
     * the customer nothing while making a replayed POST identifiable.
     */
    private function submissionToken(): string
    {
        return Str::random(40);
    }

    /**
     * The dates and party size the confirmation form opens with.
     *
     * Three sources, in order of authority: the search the customer just came
     * from (the query string), a guest's saved answers restored after signing in
     * (old input), and finally sensible defaults derived from the service itself
     * so the price is visible immediately instead of after a first keystroke.
     *
     * @return array{start_date: string|null, end_date: string|null, travelers: int}
     */
    protected function initialPeriod(string $type, mixed $service, Request $request): array
    {
        $start = $this->initialValue($request, 'start_date');
        $end = $this->initialValue($request, 'end_date');
        $rulesService = new BookingRulesService;
        $travelers = (int) ($this->initialValue($request, 'travelers') ?? $rulesService->defaultTravelers($type, $service));
        $durationDays = $this->initialValue($request, 'duration_days');

        // A searched rental or package length is the customer stating how long
        // they want for, and the search page measured it from the start date, so
        // the end date is derived from it rather than lost on the way through.
        if ($end === null && $start !== null && $durationDays !== null && (int) $durationDays > 0) {
            $end = CarbonImmutable::parse($start)->addDays((int) $durationDays)->toDateString();
        }

        // A tour is sold as a fixed-length package, so its end date is the start
        // date plus the configured duration rather than an arbitrary choice.
        if ($type === 'tour' && $end === null && $start !== null && (int) ($service->duration_days ?? 0) > 0) {
            $end = CarbonImmutable::parse($start)->addDays((int) $service->duration_days)->toDateString();
        }

        if ($end === null && $start !== null && $type !== 'tour') {
            $end = CarbonImmutable::parse($start)->addDay()->toDateString();
        }

        return [
            'start_date' => $start,
            'end_date' => $end,
            'travelers' => max(1, min($travelers, $rulesService->maxTravelers($type, $service))),
        ];
    }

    /**
     * A restored guest answer wins over the query string, because it is what the
     * customer actually submitted before being asked to sign in.
     */
    protected function initialValue(Request $request, string $key): ?string
    {
        $old = old($key, $request->query($key));

        return filled($old) ? (string) $old : null;
    }

    /**
     * Quote + availability endpoint used by the booking form's live review.
     */
    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_type' => ['required', Rule::in(Booking::TYPES)],
            'service_id' => ['required', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'travelers' => ['nullable', 'integer', 'min:1'],
        ]);

        $service = $this->resolveById($validated['booking_type'], (int) $validated['service_id']);

        if ($service === null) {
            return response()->json(['available' => false, 'reason' => 'The selected service is not available.', 'quote' => null], 422);
        }

        $rules = (new BookingRulesService)->validate(
            $validated['booking_type'],
            $service,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
            isset($validated['travelers']) ? (int) $validated['travelers'] : null
        );

        if (! $rules['valid']) {
            return response()->json(['available' => false, 'reason' => $rules['errors'][0] ?? 'The requested dates are not valid.', 'quote' => null], 422);
        }

        $availability = (new AvailabilityService)->check(
            $validated['booking_type'],
            (int) $validated['service_id'],
            (string) $validated['start_date'],
            $validated['end_date'] ?? null,
            null,
            isset($validated['travelers']) ? (int) $validated['travelers'] : null
        );

        $quote = (new PriceCalculator)->quote(
            $validated['booking_type'],
            $service,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
            isset($validated['travelers']) ? (int) $validated['travelers'] : null
        );

        return response()->json([
            'available' => $availability['available'],
            'reason' => $availability['reason'],
            'quote' => $availability['available'] ? $quote : null,
        ]);
    }

    /**
     * Store a new public booking request.
     */
    public function store(Request $request): RedirectResponse
    {
        // A double-clicked Book Now, or a POST replayed from browser history,
        // carries the token of a submission that already produced a booking.
        // Replaying it must land back on that booking rather than create a
        // second one, so this runs before any validation or availability work.
        if ($replayed = $this->replayedSubmission($request->input('submission_token'))) {
            return $this->bookingConfirmation($replayed);
        }

        $validated = $request->validate([
            'booking_type' => ['required', Rule::in(Booking::TYPES)],
            'service_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:500'],
            'travelers' => ['required', 'integer', 'min:1', 'max:'.config('booking.max_travelers', 100)],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'message' => ['nullable', 'string', 'max:5000'],
            'policy_accepted' => ['required', 'accepted'],
        ]);

        $service = $this->resolveById($validated['booking_type'], (int) $validated['service_id']);

        if ($service === null) {
            return $this->bookingError('service_id', 'The selected service is not available.');
        }

        $rules = (new BookingRulesService)->validate(
            $validated['booking_type'],
            $service,
            $validated['start_date'],
            $validated['end_date'] ?? null,
            (int) $validated['travelers']
        );

        if (! $rules['valid']) {
            return $this->bookingError('booking', $rules['errors'][0] ?? 'The requested dates are not valid.');
        }

        $availability = (new AvailabilityService)->check(
            $validated['booking_type'],
            (int) $validated['service_id'],
            $validated['start_date'],
            $validated['end_date'] ?? null,
            null,
            (int) $validated['travelers']
        );

        if (! $availability['available']) {
            return $this->bookingError('booking', $availability['reason'] ?? 'This service is not available for the selected dates.');
        }

        try {
            $booking = app(BookingWorkflowService::class)->createBooking([
                'booking_type' => $validated['booking_type'],
                'service_id' => $validated['service_id'],
                'service_title' => $service->title ?? $service->name,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'] ?? null,
                'travelers' => (int) $validated['travelers'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'message' => $validated['message'] ?? null,
                'user_id' => $request->user()->id,
                'source' => 'web',
                'policy_accepted_at' => now(),
            ])['booking'];
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() === 422) {
                return $this->bookingError('booking', $exception->getMessage());
            }

            throw $exception;
        }

        Session::push('my_booking_references', $booking->booking_reference);
        Session::put('booking_email', $booking->email);
        $this->rememberSubmission($request->input('submission_token'), $booking);

        return $this->bookingConfirmation($booking);
    }

    /**
     * The booking a submission token was already turned into, if any.
     *
     * Tokens are only ever read back from this session, so the answer can only
     * ever be a booking that was created by this browser session.
     */
    private function replayedSubmission(mixed $token): ?Booking
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        $reference = Session::get(self::SUBMITTED_TOKENS_SESSION_KEY, [])[$token] ?? null;

        if (! is_string($reference)) {
            return null;
        }

        $booking = Booking::query()->where('booking_reference', $reference)->first();

        if ($booking === null) {
            // The booking was deleted after it was created. The token no longer
            // protects anything, so stop honouring it and let the request be
            // treated as a fresh submission.
            Session::forget(self::SUBMITTED_TOKENS_SESSION_KEY.'.'.$token);

            return null;
        }

        // The mapping is kept for as long as the booking exists. A third click,
        // a back-button resubmit, or a refresh of the same POST must all resolve
        // to the original booking rather than reaching store() again, so the
        // token is only dropped once its booking is gone.
        return $booking;
    }

    /**
     * Record that this token has produced a booking, so replaying it is safe.
     */
    private function rememberSubmission(mixed $token, Booking $booking): void
    {
        if (! is_string($token) || $token === '') {
            return;
        }

        $used = Session::get(self::SUBMITTED_TOKENS_SESSION_KEY, []);

        $used[$token] = $booking->booking_reference;

        // Bound the list: only a handful of recent submissions can ever be
        // replayed from history, and the session must not grow without limit.
        if (count($used) > self::MAX_REMEMBERED_SUBMISSIONS) {
            $used = array_slice($used, -self::MAX_REMEMBERED_SUBMISSIONS, null, true);
        }

        Session::put(self::SUBMITTED_TOKENS_SESSION_KEY, $used);
    }

    /**
     * Lands the customer on the AdminLTE page for the booking that was just saved.
     *
     * The reference flashed here is read back from the persisted booking row by
     * the user booking view, so it can never be a placeholder. Management lives
     * entirely in the signed-in user panel; there is no public success page.
     */
    private function bookingConfirmation(Booking $booking): RedirectResponse
    {
        return redirect()
            ->route('bookings.show', $booking->booking_reference)
            ->with('just_booked', true)
            ->with('success', "Booking submitted successfully. Your booking reference is {$booking->booking_reference}.");
    }

    /**
     * The customer area: list bookings linked to this session (by reference)
     * or to the signed-in account.
     */
    public function my(): View
    {
        $references = array_unique(Session::get('my_booking_references', []));

        // A signed-in customer sees only their own bookings. Session references
        // are a lookup convenience and can name a booking that has since been
        // claimed by someone else, so they are honoured only while the booking
        // has no owner of its own.
        $bookings = Booking::query()
            ->where(function ($query) use ($references) {
                if (auth()->check()) {
                    $query->where('user_id', auth()->id());

                    return;
                }

                $query->whereNull('user_id')->whereIn('booking_reference', $references);
            })
            ->orderByDesc('created_at')
            ->get();

        $counts = [
            'total' => $bookings->count(),
            'pending' => $bookings->where('status', 'pending')->count(),
            'confirmed' => $bookings->where('status', 'confirmed')->count(),
            'payment_pending' => $bookings->where('status', 'payment_pending')->count(),
            'completed' => $bookings->where('status', 'completed')->count(),
            'cancelled' => $bookings->whereIn('status', ['cancelled', 'rejected', 'expired'])->count(),
        ];

        return view('bookings::user.bookings.index', [
            'bookings' => $bookings,
            'counts' => $counts,
        ]);
    }

    /**
     * Look up a booking by reference + email to prove access.
     */
    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'booking_reference' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $booking = Booking::query()
            ->where('booking_reference', strtoupper((string) $validated['booking_reference']))
            ->whereRaw('LOWER(email) = ?', [strtolower((string) $validated['email'])])
            ->first();

        abort_unless($booking !== null, 422, 'No booking matches the reference and email you provided.');

        // Claim a booking only when it is genuinely unowned. Reassigning a
        // booking that already has an owner would let any account that happens to
        // share the booking's address take the reservation over, because account
        // emails are not verified at registration.
        if (auth()->check()
            && $booking->user_id === null
            && $this->emailMatchesAccount($booking->email, $request->user()?->email)) {
            $booking->user_id = $request->user()?->id;
            $booking->save();
        }

        Session::push('my_booking_references', $booking->booking_reference);
        Session::put('booking_email', $booking->email);

        return redirect()
            ->route('bookings.my')
            ->with('success', 'Booking '.$booking->booking_reference.' linked to your session.');
    }

    /**
     * Show a single booking with its timeline and payment information.
     *
     * This is the one and only customer booking details page. A customer manages
     * their own reservations inside the signed-in AdminLTE user panel, so this
     * renders in the user AdminLTE shell and never in the public frontend.
     * Admins who need the management view of the same booking use
     * admin.bookings.show.
     */
    public function show(Booking $booking): View
    {
        $this->authorizeAccess($booking);

        return view('bookings::user.bookings.show', [
            'booking' => $booking,
            'statuses' => Booking::STATUS_LABELS,
            'summary' => (new PaymentCalculationService)->summaryFor($booking),
        ]);
    }

    /**
     * The cancellation confirmation screen with the policy quote.
     *
     * The quote is calculated by CancellationPolicyService; the view only presents
     * it. Rendering stays in the user AdminLTE panel.
     */
    public function cancelPreview(Booking $booking): View
    {
        $this->authorizeAccess($booking);

        return view('bookings::user.bookings.cancel', [
            'booking' => $booking,
            'quote' => (new CancellationPolicyService)->quoteFor($booking),
        ]);
    }

    /**
     * Confirm the cancellation (customer side).
     */
    public function cancel(Request $request, Booking $booking, BookingWorkflowService $workflow): RedirectResponse
    {
        $this->authorizeAccess($booking);

        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);

        $workflow->cancel($booking, 'customer', $validated['reason'] ?? null, $request->user());

        return redirect()
            ->route('bookings.show', $booking->booking_reference)
            ->with('success', 'Your booking has been cancelled.');
    }

    /**
     * The payment page: price breakdown and available methods.
     *
     * Like the booking details page, this is customer-only and renders in the user
     * AdminLTE panel. Admin payment management lives behind
     * admin.bookings.payments.index / admin.payments.show.
     */
    public function payment(Booking $booking): View
    {
        $this->authorizeAccess($booking);

        $methods = collect(BookingSetting::getWithDefault('payment_methods'))
            ->filter(fn ($method) => is_array($method) && filled($method['method'] ?? null))
            ->values()
            ->all();

        return view('bookings::user.bookings.payment', [
            'booking' => $booking,
            'methods' => $methods,
            'summary' => (new PaymentCalculationService)->summaryFor($booking),
            'pendingPayment' => $booking->payments()->where('status', 'pending')->latest()->first(),
        ]);
    }

    /**
     * Customer reports that they paid (awaiting admin verification).
     */
    public function notifyPayment(Request $request, Booking $booking, BookingWorkflowService $workflow): RedirectResponse
    {
        $this->authorizeAccess($booking);

        abort_unless(in_array($booking->status, ['confirmed', 'payment_pending'], true), 422, 'Payment can only be reported for a confirmed booking.');

        if ($booking->payments()->where('status', 'pending')->exists()) {
            return redirect()
                ->route('bookings.payment', $booking->booking_reference)
                ->withErrors(['payment' => 'You already have a pending payment report for this booking.']);
        }

        $validated = $request->validate([
            'method' => ['required', 'string'],
            'reference' => [Rule::requiredIf(fn () => $request->input('method') !== 'cash'), 'nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'message' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'pdf'])->max('5mb')],
        ]);

        try {
            $workflow->recordCustomerPayment(
                $booking,
                $validated['method'],
                $validated['reference'] ?? null,
                (string) $validated['amount'],
                $request->user(),
                note: $validated['message'] ?? null,
                receipt: $request->file('receipt'),
            );
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() === 422) {
                $message = $exception->getMessage();
                $errorKey = str_contains($message, 'method')
                    ? 'method'
                    : (str_contains($message, 'reference') ? 'reference' : 'amount');
                $errors = [
                    $errorKey => $message,
                    'payment' => $message,
                ];

                return redirect()
                    ->route('bookings.payment', $booking->booking_reference)
                    ->withInput($request->except('receipt'))
                    ->withErrors($errors);
            }

            throw $exception;
        }

        return redirect()
            ->route('bookings.payment', $booking->booking_reference)
            ->with('success', 'Thank you! We have recorded your payment evidence and will verify it shortly.');
    }

    /**
     * Raise a change request for a booking.
     */
    public function changeRequest(Request $request, Booking $booking, BookingWorkflowService $workflow): RedirectResponse
    {
        $this->authorizeAccess($booking);

        if ($booking->changeRequests()->where('status', 'pending')->exists()) {
            return redirect()
                ->route('bookings.show', $booking->booking_reference)
                ->withErrors(['change_request' => 'You already have a pending change request for this booking.']);
        }

        $validated = $request->validate([
            'type' => ['required', Rule::in(BookingChangeRequest::TYPES)],
            'requested' => ['required', 'array'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $clean = array_filter($validated['requested'], fn ($value) => filled($value));

        $workflow->raiseChangeRequest($booking, $validated['type'], $clean, $validated['reason'] ?? null, $request->user());

        return redirect()
            ->route('bookings.show', $booking->booking_reference)
            ->with('success', 'Your change request has been submitted and will be reviewed.');
    }

    /**
     * Ensure a booking is accessible to the visitor.
     *
     * A signed-in owner always wins. The email fallback exists only for bookings
     * that genuinely have no owner, and only for visitors who are not signed in.
     * It must never override ownership: the booking email is attacker-supplied on
     * submission, so trusting it for an owned booking would let any signed-in
     * user read, cancel, or raise change requests against another user's booking
     * simply by submitting a booking with that email.
     */
    protected function authorizeAccess(Booking $booking): void
    {
        $isSignedIn = auth()->check();
        $isOwner = $isSignedIn
            && $booking->user_id !== null
            && (int) $booking->user_id === (int) auth()->id();

        if ($isOwner) {
            return;
        }

        $sessionEmail = strtolower((string) Session::get('booking_email'));

        // A signed-in user who is not the owner must not inherit the email
        // fallback: even if their session once held this booking's email, the
        // booking belongs to someone else.
        if ($isSignedIn) {
            abort(403, 'This booking belongs to another account.');
        }

        $bookingEmail = $booking->email !== null ? strtolower($booking->email) : null;

        abort_unless($bookingEmail !== null && $sessionEmail === $bookingEmail, 403, 'This booking is not linked to your session. Use "My Bookings" with your reference and email.');
    }

    /**
     * Resolve a service by slug for the requested booking type.
     */
    private function resolveBySlug(string $type, string $slug): mixed
    {
        return match ($type) {
            'tour' => Tour::query()->where('is_active', true)->where('slug', $slug)->first(),
            'hotel' => Hotel::query()->where('is_active', true)->where('slug', $slug)->first(),
            'vehicle' => TransportVehicle::query()->where('is_active', true)->where('availability', true)->where('slug', $slug)->first(),
            default => null,
        };
    }

    /**
     * Resolve a service by id for the requested booking type.
     */
    private function resolveById(string $type, int $id): mixed
    {
        return match ($type) {
            'tour' => Tour::query()->where('is_active', true)->find($id),
            'hotel' => Hotel::query()->where('is_active', true)->find($id),
            'vehicle' => TransportVehicle::query()->where('is_active', true)->where('availability', true)->find($id),
            default => null,
        };
    }

    /**
     * Finish a confirmation that authentication interrupted.
     *
     * A guest's POST to bookings.store never reaches store(): the auth middleware
     * bounces it to the login page, and the only trace that survives is the draft
     * PreserveBookingDraft saved plus the one-time token it put beside it. Once
     * they are signed in that token is spent here and the very same submission is
     * replayed through store() - the same validation, availability, pricing,
     * payment and notification path an authenticated customer takes.
     *
     * Three things keep this from ever becoming a way to book by proxy:
     *   - the token is compared against the one this session was issued, so a URL
     *     copied from elsewhere is inert;
     *   - the draft has to name the very service being opened, so a resume link
     *     cannot redirect a confirmation onto a different, dearer service;
     *   - the token is spent before the submission runs, so refreshing the page or
     *     reloading the link afterwards finds nothing left to replay.
     *
     * Returning null for any of those means the customer simply gets the form
     * back, which is exactly what happened before any of this existed.
     */
    private function resumeConfirmedBooking(Request $request, string $type, mixed $service): ?RedirectResponse
    {
        $token = $request->query('resume');

        // Nothing to resume unless this is the signed-in return leg of an
        // interrupted confirmation. An anonymous visitor is no further along than
        // they were, and is shown the form as normal.
        if (! is_string($token) || $token === '' || ! $request->user()) {
            return null;
        }

        $expected = $request->session()->get(PreserveBookingDraft::SESSION_INTENT_KEY);

        if (! is_string($expected) || ! hash_equals($expected, $token)) {
            return null;
        }

        $draft = $request->session()->pull(PreserveBookingDraft::SESSION_KEY);
        $request->session()->forget(PreserveBookingDraft::SESSION_INTENT_KEY);

        if (! is_array($draft) || $draft === []) {
            return null;
        }

        if (($draft['booking_type'] ?? null) !== $type
            || (int) ($draft['service_id'] ?? 0) !== (int) $service->id) {
            return null;
        }

        return $this->submitAsAuthenticated($request, $draft);
    }

    /**
     * Run a saved draft through store() as the signed-in customer who wrote it.
     *
     * store() reads the ambient request for the session and for back(), so the
     * submission is made current for the duration of the call and the real request
     * is put back afterwards - including when store() throws.
     */
    private function submitAsAuthenticated(Request $request, array $draft): RedirectResponse
    {
        $submission = $this->asStoreSubmission($request, $draft);
        $url = app('url');

        // The page a failed confirmation returns to. It deliberately leaves the
        // resume token out: the intent is spent by now, so a failure must be
        // corrected by hand rather than resuming itself.
        $formUrl = route('bookings.create', array_filter([
            'type' => $draft['booking_type'] ?? null,
            'service_id' => $draft['service_id'] ?? null,
        ]));

        // store() and back() both read the ambient request, from the container and
        // from the URL generator respectively, so the submission is made current
        // for the duration of the call and the real request is put back afterwards
        // - including when store() throws.
        app()->instance('request', $submission);
        $url->setRequest($submission);

        try {
            return $this->store($submission);
        } finally {
            app()->instance('request', $request);
            $url->setRequest($request);

            // A rejected submission is answered by back(), and for validation that
            // answer comes from the exception handler once this call has unwound -
            // after the real request is current again. Naming the form on the
            // request the handler will see is what sends a customer with a
            // refused booking back to their answers rather than to the login page.
            $request->headers->set('referer', $formUrl);
        }
    }

    /**
     * The interrupted request, rebuilt as a request.
     *
     * This is the same submission the guest already made, so it goes to the same
     * store() an authenticated click reaches. It carries the signed-in user and
     * the live session.
     */
    private function asStoreSubmission(Request $request, array $draft): Request
    {
        $submission = Request::create(route('bookings.store'), 'POST', $draft);

        $submission->setLaravelSession($request->session());
        $submission->setUserResolver(fn () => $request->user());

        return $submission;
    }

    /**
     * Put a guest's saved booking form answers back into old input so the form
     * renders exactly as they left it after signing in or registering.
     *
     * The draft is consumed on read: a second visit to the form starts clean
     * unless the customer submits again.
     */
    private function restoreGuestBookingDraft(): void
    {
        // Any confirmation still outstanding is spent on reaching the form, so a
        // resume link left over from an earlier visit cannot fire later against a
        // form the customer has since edited.
        Session::forget(PreserveBookingDraft::SESSION_INTENT_KEY);

        $draft = Session::pull(PreserveBookingDraft::SESSION_KEY);

        if (! is_array($draft) || $draft === []) {
            return;
        }

        $existing = Session::getOldInput();

        Session::flashInput(array_merge($draft, is_array($existing) ? $existing : []));
    }

    /**
     * Whether the booking email belongs to the given account (case-insensitive).
     */
    private function emailMatchesAccount(?string $bookingEmail, ?string $accountEmail): bool
    {
        return $bookingEmail !== null
            && $accountEmail !== null
            && strtolower($bookingEmail) === strtolower($accountEmail);
    }

    /**
     * Redirect back to the booking form with a user-friendly error instead of
     * surfacing a raw 422 page.
     */
    private function bookingError(string $key, string $message): RedirectResponse
    {
        return back()->withInput()->withErrors([$key => $message]);
    }
}
