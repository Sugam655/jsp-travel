@php
    // The confirmation step. The service is already fixed by the URL, so this page
    // never offers a picker that could widen the choice: it only confirms the
    // dates and party size, and hands the final review - what the booking costs,
    // who it is for, and the policy - to one confirmation popup opened by the
    // "Review & Confirm" button.
    $icons = ['hotel' => 'bi-building', 'vehicle' => 'bi-car-front', 'tour' => 'bi-map'];

    // A traveller books a trip, not a car rental form: the period is the
    // departure and the return whatever they are booking, apart from a stay,
    // which is begun and ended rather than left and came back from.
    $labels = match ($serviceType) {
        'hotel' => ['start' => 'CHECK IN', 'end' => 'CHECK OUT', 'party' => 'Guests'],
        'vehicle' => ['start' => 'DEPARTURE', 'end' => 'RETURN', 'party' => 'Passengers'],
        default => ['start' => 'DEPARTURE', 'end' => 'RETURN', 'party' => 'Travellers'],
    };

    // The day as it is read on the card, "Fri, 22 Mar", rather than the raw
    // 2026-03-22 the input holds. Rendered here so the card is already right on
    // first paint; the booking script keeps it in step once a date is picked.
    $dateDisplay = function (?string $value): string {
        if (! filled($value)) {
            return 'Select date';
        }

        try {
            return \Carbon\CarbonImmutable::parse($value)->format('D, d M');
        } catch (Throwable) {
            return 'Select date';
        }
    };

    $title = $serviceType === 'vehicle' ? $service->name : $service->title;

    $today = now()->toDateString();

    // A single-day tour has no end date; a stay and a rental both do.
    $endDateRequired = $serviceType !== 'tour';

    /* What the server will say if the end date is not after the start.

       The field is labelled with this rather than with a second wording written
       here, so the message beside the field and the message that arrives when
       the customer submits are the same sentence. Nothing about the rule is said
       up front: the field is simply a date, and the rule is raised when it is
       broken. */
    $endDateOrderMessage = (new \Modules\Bookings\Services\BookingRulesService)->endDateOrderMessage($serviceType);

    /* What the script asks for when a date is still missing.

        Named for what the customer is booking rather than for the field, so a
        stay, a rental and a package each get asked in their own words. The
        server keeps its own wording in BookingRulesService for the submission;
        these are only ever shown beside the control that is missing an answer. */
    $missingDateMessages = match ($serviceType) {
        'hotel' => [
            'start' => 'Please select a check-in date.',
            'end' => 'Please select a check-out date.',
        ],
        'vehicle' => [
            'start' => 'Please select a pick-up/departure date.',
            'end' => 'Please select a return/drop-off date.',
        ],
        default => [
            'start' => 'Please select a departure date.',
            'end' => 'Please select a return date.',
        ],
    };

    $facts = match ($serviceType) {
        'hotel' => array_filter([
            $service->location_label ?? null,
            $service->rating ? $service->rating.' star rating' : null,
            $service->room_type ?? null,
        ]),
        'vehicle' => array_filter([
            $service->location_label ?? null,
            $service->type_label ?? null,
            $service->seating_capacity ? $service->seating_capacity.' seats' : null,
            $service->transmission ?? null,
        ]),
        default => array_filter([
            $service->location_label ?? null,
            $service->duration ?? null,
            $service->capacity ? $service->capacity.' travellers max' : null,
        ]),
    };

    // The opening state decides whether this page can be submitted at all; the
    // booking script keeps it in step as the dates change. A page nobody has
    // entered dates on yet is not blocked: Review is how they are asked, and it
    // validates them when they press it.
    $canSubmit = $attempted ? ($rules['valid'] && $availability['available']) : true;

    // Confirm Booking additionally waits for the policy, and that wait is rendered
    // rather than left to script: a button that is live before the customer has
    // accepted the terms is a booking the customer never agreed to.
    $policyAccepted = (bool) old('policy_accepted');

    $canConfirm = $canSubmit && $policyAccepted;

    $customer = auth()->user();

    // Null-safe on purpose: a page opened with no usable dates has no quote yet,
    // and the popup still has to render its shape.
    $money = fn ($amount) => ($quote['currency'] ?? 'NPR').' '.number_format((float) $amount, 2);

    // What this booking is, named the way a traveller reads it rather than the
    // way the database stores it. Both the popup heading and its summary block use
    // this, so the type can never disagree with itself.
    $typeLabels = ['hotel' => 'Hotel', 'vehicle' => 'Vehicle', 'tour' => 'Tour'];
    $typeLabel = $typeLabels[$serviceType] ?? ucfirst($serviceType);

    // Why the popup's Confirm Booking is dead on arrival, in the customer's words.
    // The hero states the same thing, because the popup is the last place a
    // customer is still looking when they want to know why they cannot finish.
    // An untouched page has nothing to report: it is waiting to be asked, so it
    // must not pre-emptively claim the dates cannot be booked.
    $blockReason = match (true) {
        ! $attempted => null,
        ! $rules['valid'] => $rules['errors'][0] ?? 'These dates cannot be booked yet.',
        ! $availability['available'] => $availability['reason'] ?? 'This service is not available for the selected dates.',
        default => null,
    };
@endphp

@include('frontend.layouts.header')

@include('frontend.layouts.mobile-nav')

<style>
    .fh-control {
        width: 100%;
        padding: 0.65rem 0.9rem;
        border: 1px solid rgba(0, 0, 0, 0.12);
        border-radius: 0.6rem;
        background-color: #fff;
        font-size: 0.95rem;
        color: #212529;
    }

    .fh-control:focus {
        border-color: #c19a6b;
        box-shadow: 0 0 0 0.2rem rgba(193, 154, 107, 0.2);
        outline: none;
    }

    .fh-confirm-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        align-items: end;
    }

    .fh-quote-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.35rem 0;
        font-size: 0.92rem;
    }

    /* The rate and the subtotal are the two figures a customer actually compares
       against the result card, so they read as figures rather than as prose. */
    .fh-quote-figure {
        font-variant-numeric: tabular-nums;
        font-weight: 600;
        white-space: nowrap;
    }

    .fh-quote-note {
        color: #6c757d;
        font-size: 0.85rem;
    }

    /* The popup is a checkout, not a receipt: the itinerary and the price stand on
       the left, the customer's own details on the right, and each block is a panel
       so the two halves are separable without a stack of rules. */
    .fh-panel {
        background: #f8f9fa;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 0.75rem;
        padding: 1.1rem 1.15rem;
    }

    .fh-panel + .fh-panel,
    .fh-quote-total {
        margin-top: 1rem;
    }

    /* The dates are read back as the days they are, beside each other rather than
       one under the other, so a stay or a rental can be taken in at a glance. */
    .fh-period-label {
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.09em;
        text-transform: uppercase;
        color: #6c757d;
    }

    .fh-period-value {
        font-size: 1.05rem;
        font-weight: 600;
        color: #212529;
        font-variant-numeric: tabular-nums;
    }

    /* The total is the one number the popup exists to state, so it is set apart
       from the rows that produced it rather than left at the end of them. */
    .fh-quote-total {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        padding: 0.9rem 1.15rem;
        border-radius: 0.75rem;
        background: rgba(1, 50, 116, 0.06);
        border: 1px solid rgba(1, 50, 116, 0.18);
    }

    .fh-total-label {
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.09em;
        text-transform: uppercase;
        color: #6c757d;
    }

    /* The total is the one number the popup exists to state, so it is the one
       number given its own weight and colour. */
    .fh-quote-grand-total {
        font-size: 1.5rem;
        font-weight: 700;
        color: #013274;
        letter-spacing: -0.01em;
    }

    .fh-summary-card {
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 0.9rem;
        padding: 1.5rem;
    }

    .fh-field-error {
        display: none;
        margin-top: 0.35rem;
        font-size: 0.78rem;
        color: #b02a37;
    }

    .fh-field-error.is-visible {
        display: block;
    }

    .fh-popup-section-title {
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.09em;
        text-transform: uppercase;
        color: #6c757d;
        margin-bottom: 0.65rem;
    }

    .fh-service-chip {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: #013274;
        background: rgba(1, 50, 116, 0.08);
        border-radius: 999px;
        padding: 0.2rem 0.65rem;
    }
</style>

<form id="bookingForm" method="POST" action="{{ route('bookings.store') }}"
    data-quote-url="{{ route('bookings.quote') }}"
    data-dates-attempted="{{ $attempted ? 'true' : 'false' }}"
    data-missing-start="{{ $missingDateMessages['start'] }}"
    data-missing-end="{{ $missingDateMessages['end'] }}"
    data-end-date-order-error="{{ $endDateOrderMessage }}">
    @csrf

    {{-- A double-clicked Book Now, or a POST replayed from browser history, carries
         the token of a submission that already produced a booking. The controller
         returns that same booking instead of creating a second one. --}}
    <input type="hidden" name="submission_token" id="submissionToken" value="{{ $submissionToken }}">

    {{-- The service comes from the URL, not from a select: this page can only ever
         create a booking for the exact record the customer clicked. --}}
    <input type="hidden" name="booking_type" id="bookingType" value="{{ $serviceType }}">
    <input type="hidden" name="service_id" id="serviceId" value="{{ $service->id }}">

    {{-- The live quote, the server rules and bookings.store all read these three
         fields. The visible pickers are mirrored into them by the booking script. A
         failed submit wins, so a customer correcting a rejected booking does not
         silently lose their edit. --}}
    <input type="hidden" name="start_date" id="start_date" value="{{ old('start_date', $period['start_date']) }}">
    <input type="hidden" name="end_date" id="end_date" value="{{ old('end_date', $period['end_date']) }}">
    <input type="hidden" name="travelers" id="travelers" value="{{ old('travelers', $period['travelers']) }}">

    <section class="fh-hero-section fh">
        <div class="fh-hero-bg"></div>
        <div class="container">
            <div class="fh-hero-content">
                <p class="mb-2">
                    <a href="{{ route('booking.search', ['type' => $serviceType]) }}"
                        class="text-white text-decoration-none small">
                        <i class="bi bi-arrow-left"></i> Back to search
                    </a>
                </p>

                <h1 class="fh-hero-title">
                    {{ $serviceType === 'vehicle' ? 'Rent' : 'Book' }}
                    <span>{{ $title }}</span>
                </h1>

                @if ($facts)
                    <p class="fh-result-meta mb-4">{{ implode(' · ', $facts) }}</p>
                @endif

                <div class="fh-booking-widget">
                    <div class="fh-booking-card">
                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                <strong>Please fix the following before continuing:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach ($errors->all() as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @elseif ($attempted && ! $rules['valid'])
                            {{-- Only once the customer has actually engaged with the
                                 period. A first visit has no dates to have got wrong,
                                 and opening the page with "these dates cannot be
                                 booked" reads as a fault in the page rather than an
                                 answer still owed. The booking script reports the same
                                 failures inline, next to the field that caused them. --}}
                            <div class="alert alert-danger" role="alert">
                                <strong>These dates cannot be booked yet:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach ($rules['errors'] as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @elseif ($attempted && ! $availability['available'] && $availability['reason'])
                            <div class="alert alert-warning" role="alert">
                                {{ $availability['reason'] }}
                            </div>
                        @endif

                        <div class="fh-confirm-form">
                            <div class="fh-form-group">
                                <div class="fh-date-field" data-date-field>
                                    <label class="fh-date-label" for="start_date_field">{{ $labels['start'] }}</label>
                                    <div class="fh-date-box">
                                        <span class="fh-date-value" data-date-display data-date-placeholder="Select date">{{ $dateDisplay(old('start_date', $period['start_date'])) }}</span>
                                        <i class="bi bi-calendar3 fh-date-icon" aria-hidden="true"></i>
                                    </div>
                                    {{-- The real control sits invisibly over the whole card, so the label,
                                         the formatted day and the glyph all open the same calendar. Its
                                         value is mirrored into the hidden field the server reads. --}}
                                    <input type="date" class="fh-date-input" id="start_date_field"
                                        min="{{ $today }}" value="{{ old('start_date', $period['start_date']) }}"
                                        data-sync="start_date" data-period-start>
                                </div>
                                <p class="fh-field-error" data-period-error="start_date"></p>
                            </div>

                            <div class="fh-form-group">
                                <div class="fh-date-field" data-date-field>
                                    <label class="fh-date-label" for="end_date_field">{{ $labels['end'] }}</label>
                                    <div class="fh-date-box">
                                        <span class="fh-date-value" data-date-display data-date-placeholder="Select date">{{ $dateDisplay(old('end_date', $period['end_date'])) }}</span>
                                        <i class="bi bi-calendar3 fh-date-icon" aria-hidden="true"></i>
                                    </div>
                                    <input type="date" class="fh-date-input" id="end_date_field"
                                        min="{{ $today }}" value="{{ old('end_date', $period['end_date']) }}"
                                        data-sync="end_date" data-period-end @required($endDateRequired)>
                                </div>
                                <p class="fh-field-error" data-period-error="end_date"></p>
                            </div>

                            @if ($serviceType === 'vehicle')
                                <div class="fh-form-group">
                                    <label for="rental_duration_field">RENTAL DURATION</label>
                                    {{-- The rental length is expressed in days, so the
                                         drop-off date is derived from the pick-up and the
                                         two can never quietly disagree. --}}
                                    <select class="fh-control" id="rental_duration_field" data-rental-duration>
                                        <option value="">Any duration</option>
                                        @foreach ($rentalDurations as $days)
                                            <option value="{{ $days }}">{{ $days }} day{{ $days > 1 ? 's' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="fh-form-group">
                                <label for="party_field">{{ strtoupper($labels['party']) }}</label>
                                <input type="number" class="fh-control" id="party_field"
                                    min="1" max="{{ $maxTravelers }}"
                                    value="{{ old('travelers', $period['travelers']) }}" data-party-input>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- The page itself is only the choice of dates and party size. Everything
         else - what it costs, who it is for, the policy - is the final review, so
         it lives in the popup this button opens rather than on the page. --}}
    <section class="fh-results-section">
        <div class="container">
            <div class="fh-summary-card text-center">
                <p class="text-muted small mb-3">
                    <i class="bi {{ $icons[$serviceType] }} me-1"></i>
                    Check your dates, then review this booking before you confirm it.
                </p>

                <button type="button" class="fh-search-btn" id="reviewBookingButton"
                    data-bs-toggle="modal" data-bs-target="#fh-confirmBookingModal"
                    @disabled(! $canSubmit)>
                    Review &amp; Confirm <i class="bi bi-arrow-right"></i>
                </button>

                <p class="text-muted small mb-0 mt-3" id="bookingPeriodStatus" role="status"></p>
            </div>
        </div>
    </section>

    {{-- The confirmation popup: the last thing between the customer and the
         booking. It lives inside #bookingForm, so the fields it collects are the
         existing booking form's fields and Confirm Booking is that same form's
         submit - there is no second form and no second booking path here.

         Every amount, the unit rate, the duration and the availability verdict
         come from PriceCalculator and AvailabilityService, the same services
         bookings.store re-runs before it writes anything. The server renders them
         here so the popup is already correct on first paint; the booking script
         then re-asks bookings.quote whenever the dates or the party change. --}}
    <div class="modal fade" id="fh-confirmBookingModal" tabindex="-1" aria-labelledby="fh-confirmBookingTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title fs-4" id="fh-confirmBookingTitle">Confirm your booking</h2>
                        <p class="text-muted small mb-0">
                            <span class="fh-service-chip me-2">{{ $typeLabel }}</span>
                            <span id="fh-quoteService">{{ $title }}</span>
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-danger {{ $blockReason ? '' : 'd-none' }}" role="alert"
                        id="fh-confirmBlocker">
                        {{ $blockReason }}
                    </div>

                    {{-- A checkout, side by side: what is being booked and what it
                         costs on the left, who it is for and the terms on the right.
                         The two stack in that order on a narrow screen, so a phone
                         still reads itinerary, price, total, then details. --}}
                    <div class="row g-4">
                        <div class="col-12 col-lg-5">
                            {{-- The dates and the party size were chosen on the page, but the
                                 popup is where the customer actually reads them back: a price
                                 beside a date the customer no longer recognises is not a review. --}}
                            <section class="fh-panel">
                                <p class="fh-popup-section-title mb-3">Your stay / journey</p>

                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="fh-period-label">{{ $labels['start'] }}</div>
                                        <div class="fh-period-value" id="fh-periodStart">
                                            {{ $dateDisplay(old('start_date', $period['start_date'])) }}
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="fh-period-label">{{ $labels['end'] }}</div>
                                        <div class="fh-period-value" id="fh-periodEnd">
                                            {{ $dateDisplay(old('end_date', $period['end_date'])) }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 pt-3 border-top">
                                    <div class="fh-period-label">{{ $labels['party'] }}</div>
                                    <div class="fh-period-value" id="fh-periodParty">
                                        {{ old('travelers', $period['travelers']) }}
                                    </div>
                                </div>
                            </section>

                            <section class="fh-panel" id="fh-quoteSection">
                                <p class="fh-popup-section-title mb-2">Price breakdown</p>

                                <div class="fh-quote-row">
                                    <span>{{ $title }}</span>
                                    <span class="fh-quote-figure" id="fh-quoteUnit">
                                        @if ($quote && $quote['quantity'] > 0)
                                            {{ $money($quote['unit_price']) }} {{ $quote['unit_label'] }}
                                        @endif
                                    </span>
                                </div>

                                <div class="fh-quote-row">
                                    <span class="fh-quote-note" id="fh-quoteNote">
                                        @if ($quote)
                                            {{ $quote['recalc_note'] }}
                                        @else
                                            Choose your dates to see the live price.
                                        @endif
                                    </span>
                                    <span class="fh-quote-figure" id="fh-quoteSubtotal">
                                        @if ($quote && $quote['quantity'] > 0)
                                            {{ $money($quote['subtotal']) }}
                                        @endif
                                    </span>
                                </div>

                                <div class="fh-quote-row d-none" id="fh-quoteDiscountRow">
                                    <span class="text-danger" id="fh-quoteDiscountLabel">
                                        Discount
                                    </span>
                                    <span class="fh-quote-figure text-danger" id="fh-quoteDiscount">
                                        &minus;{{ $money($quote['discount'] ?? 0) }}
                                    </span>
                                </div>

                                <div class="fh-quote-row{{ ($quote['tax_rate'] ?? 0) > 0 ? '' : ' d-none' }}" id="fh-quoteTaxRow">
                                    <span id="fh-quoteTaxLabel">
                                        Tax ({{ number_format((float) ($quote['tax_rate'] ?? 0), 2) }}%)
                                    </span>
                                    <span class="fh-quote-figure" id="fh-quoteTax">
                                        {{ $money($quote['tax_amount'] ?? 0) }}
                                    </span>
                                </div>

                                <div class="fh-quote-row{{ ($quote['service_charge_rate'] ?? 0) > 0 ? '' : ' d-none' }}"
                                    id="fh-quoteChargeRow">
                                    <span id="fh-quoteChargeLabel">
                                        Service charge ({{ number_format((float) ($quote['service_charge_rate'] ?? 0), 2) }}%)
                                    </span>
                                    <span class="fh-quote-figure" id="fh-quoteCharge">
                                        {{ $money($quote['service_charge'] ?? 0) }}
                                    </span>
                                </div>

                                <div class="fh-quote-total{{ $quote ? '' : ' d-none' }}" id="fh-quoteTotalRow">
                                    <span class="fh-total-label">Total</span>
                                    <span class="fh-quote-grand-total" id="fh-quoteTotal">
                                        {{ $quote ? $money($quote['total']) : '' }}
                                    </span>
                                </div>
                            </section>

                            <p class="text-muted small mb-0 mt-3" id="fh-quoteStatus" role="status"></p>
                        </div>

                        <div class="col-12 col-lg-7">
                            <p class="fh-popup-section-title">Your details</p>

                            @auth
                                <p class="text-muted small">
                                    Signed in as {{ $customer->email }}. We pre-filled your profile &mdash; you can still
                                    change it for this booking.
                                </p>
                            @else
                                <p class="text-muted small">
                                    You can fill this in as a guest, but you will be asked to sign in before the booking
                                    is submitted.
                                </p>
                            @endauth

                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="fh-form-group">
                                        <label for="name">FULL NAME</label>
                                        <input type="text" class="fh-control" name="name" id="name"
                                            value="{{ old('name', $customer?->name) }}" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="fh-form-group">
                                        <label for="email">EMAIL</label>
                                        <input type="email" class="fh-control" name="email" id="email"
                                            value="{{ old('email', $customer?->email) }}" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="fh-form-group">
                                        <label for="phone">PHONE</label>
                                        <input type="tel" class="fh-control" name="phone" id="phone"
                                            value="{{ old('phone', $customer?->phone) }}" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="fh-form-group">
                                        <label for="address">ADDRESS</label>
                                        <input type="text" class="fh-control" name="address" id="address"
                                            value="{{ old('address', $customer?->address) }}">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="fh-form-group">
                                        <label for="message">SPECIAL REQUESTS</label>
                                        <textarea class="fh-control" name="message" id="message" rows="3"
                                            placeholder="Pickup point, dietary needs, room preferences...">{{ old('message') }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="form-check mt-3 mb-0">
                                <input class="form-check-input" type="checkbox" name="policy_accepted" value="1"
                                    id="policy_accepted" @checked(old('policy_accepted')) required>
                                <label class="form-check-label small" for="policy_accepted">
                                    I have read and accept the booking and cancellation policy.
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between align-items-center">
                    {{-- Back to the dates rather than to the results: this is the
                         step the customer came from, and it is where an edit
                         belongs. --}}
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Back / Edit
                    </button>

                    <button type="submit" class="fh-search-btn" id="bookingSubmit" @disabled(! $canConfirm)>
                        Confirm Booking <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@include('frontend.layouts.footer')
