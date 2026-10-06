@php
    // The booking search. Every tab is one GET form against this same page, so the
    // customer's answer stays in the URL and nothing is invented client-side. The
    // results below are rendered from the real query the controller ran.
    $tabs = [
        'hotel' => ['label' => 'Hotel', 'icon' => 'bi-building'],
        'vehicle' => ['label' => 'Rent a Car', 'icon' => 'bi-car-front'],
        'tour' => ['label' => 'Tour', 'icon' => 'bi-map'],
    ];

    $panelClass = ['hotel' => 'fh-hotel-form', 'vehicle' => 'fh-car-form', 'tour' => 'fh-tour-form'];

    $headings = [
        'hotel' => 'Hotels available to book',
        'vehicle' => 'Cars available to rent',
        'tour' => 'Tour packages available to book',
    ];

    $icons = ['hotel' => 'bi-building', 'vehicle' => 'bi-car-front', 'tour' => 'bi-map'];

    // Every result action opens the same popup, so the wording of "nothing
    // matched" is per type while the popup itself stays shared.
    $plural = ['hotel' => 'hotels', 'vehicle' => 'cars', 'tour' => 'tours'];

    $today = now()->toDateString();

    // What the server will say about a period whose end date is not after its
    // start, per tab. The field is labelled with this so the message raised on
    // the page and the message raised by the submission are one sentence.
    $rules = new \Modules\Bookings\Services\BookingRulesService;

    $endDateOrderMessages = [
        'hotel' => $rules->endDateOrderMessage('hotel'),
        'vehicle' => $rules->endDateOrderMessage('vehicle'),
        'tour' => $rules->endDateOrderMessage('tour'),
    ];

    // The period labels a traveller expects, per booking type. A car is collected
    // and returned and a stay is begun and ended, so the same pair of words serves
    // both; a tour only ever has the one day it leaves on.
    $dateLabels = [
        'hotel' => ['start' => 'CHECK IN', 'end' => 'CHECK OUT'],
        'vehicle' => ['start' => 'DEPARTURE', 'end' => 'RETURN'],
        'tour' => ['start' => 'DEPARTURE', 'end' => 'RETURN'],
    ];

    // The day as a traveller reads it - "Fri, 22 Mar" - rather than the raw
    // 2026-03-22 a date input holds. Rendered here so the card is already right
    // on first paint; the booking script keeps it in step once dates are picked.
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

    // A guest cannot have a booking created from the popup, so the note under the
    // form says what actually happens next: sign in, then come straight back to
    // this booking. The button itself always says what it does.
    $isGuest = ! auth()->check();

    // Named for what it reads from the filters, and deliberately not a bare
    // $value: loop keys elsewhere on this page would otherwise shadow the closure
    // and turn every later call into a call on a string.
    $filterValue = fn ($key) => old($key, $filters[$key] ?? '');
    $selected = fn ($key, $option) => (string) $filterValue($key) === (string) $option ? ' selected' : '';

    // The party size the customer last searched with, or a derived starting point
    // per service. It is never a fixed 2: a 4-seater car cannot be offered to a
    // party of six.
    $guests = (int) $filterValue('travelers');
    $guests = max(1, min($guests > 0 ? $guests : 2, $maxVehiclePassengers));

    // The dates the customer already searched with travel into the result links,
    // so clicking Book Now does not make them choose the same dates twice. The
    // rental length and package length they picked go with them too, because the
    // confirmation form derives its end date from them.
    $searchContext = array_filter([
        'start_date' => $filters['start_date'] ?? null,
        'end_date' => $filters['end_date'] ?? null,
        'travelers' => $filters['travelers'] ?? null,
        'duration_days' => $filters['duration_days'] ?? null,
    ], fn ($value) => filled($value));

    $bookNowUrl = fn ($result) => route('bookings.create', array_merge(
        ['type' => $type, 'slug' => $result->slug],
        $searchContext
    ));

    // The popup is filled in from this payload rather than from values built in the
    // browser. Every character that could break out of the script element is
    // escaped, so a service title can never become markup here.
    $bookingSummariesJson = json_encode(
        $summaries,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
@endphp

@include('frontend.layouts.header')

@include('frontend.layouts.mobile-nav')

<style>
    /* The booking widget is a fixed four-column grid on the homepage hero, which
       cannot hold the field counts these tabs need. */
    .fh-search-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 1rem;
        align-items: end;
    }

    .fh-search-form .fh-search-btn {
        align-self: end;
    }

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

    /* The tab row is a set of links here, because each tab is a separate search
       that the server renders. */
    .fh-booking-tabs .fh-booking-tab {
        text-decoration: none;
    }

    .fh-counter-input {
        width: 56px;
        padding: 0.3rem 0.4rem;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        text-align: center;
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

    .fh-result-meta {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .jsp-empty-state {
        background: #f8f9fa;
        border: 1px dashed #ced4da;
        border-radius: 0.9rem;
        padding: 2rem 1.25rem;
        text-align: center;
        color: #6c757d;
    }

    /* The confirmation popup reuses the price breakdown look of the booking
       confirmation page, so a summary reads the same in both places. */
    .fh-quote-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.35rem 0;
        font-size: 0.92rem;
    }

    .fh-quote-total {
        font-weight: 600;
        font-size: 1.05rem;
        border-top: 1px solid rgba(0, 0, 0, 0.1);
        margin-top: 0.5rem;
        padding-top: 0.75rem;
    }

    .fh-summary-card {
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 0.9rem;
        padding: 1.5rem;
    }
</style>

<section class="fh-hero-section fh">
    <div class="fh-hero-bg"></div>
    <div class="container">
        <div class="fh-hero-content">
            <h1 class="fh-hero-title">
                Where would you like to
                <span>stay, drive or explore?</span>
            </h1>
            @if ($searched)
                <a href="#fh-resultsSection" class="fh-explore-link">
                    See results <i class="bi bi-arrow-right"></i>
                </a>
            @endif

            <div class="fh-booking-widget">
                <div class="fh-booking-tabs">
                    @foreach ($tabs as $key => $tab)
                        <a class="fh-booking-tab{{ $type === $key ? ' active' : '' }}"
                            href="{{ route('booking.search', ['type' => $key]) }}">
                            <i class="bi {{ $tab['icon'] }}"></i> {{ $tab['label'] }}
                        </a>
                    @endforeach
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger m-3 mb-0" role="alert">
                        <strong>Please fix the following before searching:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="fh-booking-card">
                    {{-- Hotel: where, when and for how many people. --}}
                    <div class="{{ $panelClass['hotel'] }}{{ $type === 'hotel' ? ' active' : '' }}" id="fh-hotelForm"
                        data-booking-type="hotel"
                        data-end-date-order-error="{{ $endDateOrderMessages['hotel'] }}">
                        <form class="fh-search-form" method="GET" action="{{ route('booking.search') }}">
                            <input type="hidden" name="type" value="hotel">

                            <div class="fh-form-group">
                                <label for="searchHotelLocation">Destination</label>
                                <select class="fh-control" name="location" id="searchHotelLocation">
                                    <option value="">Any destination</option>
                                    @foreach ($hotelLocations as $location)
                                        <option value="{{ $location }}"{{ $selected('location', $location) }}>
                                            {{ $location }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="fh-form-group">
                                <div class="fh-date-field" data-date-field>
                                    <label class="fh-date-label" for="searchHotelStart">{{ $dateLabels['hotel']['start'] }}</label>
                                    <div class="fh-date-box">
                                        <span class="fh-date-value" data-date-display data-date-placeholder="Select date">{{ $dateDisplay($filterValue('start_date')) }}</span>
                                        <i class="bi bi-calendar3 fh-date-icon" aria-hidden="true"></i>
                                    </div>
                                    {{-- The real control sits invisibly over the whole card, so the label,
                                         the formatted day and the glyph all open the same calendar. --}}
                                    <input type="date" class="fh-date-input" name="start_date" id="searchHotelStart"
                                        min="{{ $today }}" value="{{ $filterValue('start_date') }}"
                                        data-period-start>
                                </div>
                                <p class="fh-field-error" data-period-error="start_date"></p>
                            </div>

                            <div class="fh-form-group">
                                <div class="fh-date-field" data-date-field>
                                    <label class="fh-date-label" for="searchHotelEnd">{{ $dateLabels['hotel']['end'] }}</label>
                                    <div class="fh-date-box">
                                        <span class="fh-date-value" data-date-display data-date-placeholder="Select date">{{ $dateDisplay($filterValue('end_date')) }}</span>
                                        <i class="bi bi-calendar3 fh-date-icon" aria-hidden="true"></i>
                                    </div>
                                    <input type="date" class="fh-date-input" name="end_date" id="searchHotelEnd"
                                        min="{{ $today }}" value="{{ $filterValue('end_date') }}" data-period-end>
                                </div>
                                <p class="fh-field-error" data-period-error="end_date"></p>
                            </div>

                            <div class="fh-form-group">
                                <label for="searchHotelGuests">Guests</label>
                                <input type="number" class="fh-control" name="travelers" id="searchHotelGuests"
                                    min="1" max="{{ config('booking.max_travelers') }}"
                                    value="{{ $guests }}" data-party-input>
                            </div>

                            <div class="fh-form-group">
                                <label for="searchHotelRating">Rating</label>
                                <select class="fh-control" name="rating" id="searchHotelRating">
                                    <option value="">Any rating</option>
                                    @foreach (range(5, 1) as $rating)
                                        <option value="{{ $rating }}"{{ $selected('rating', $rating) }}>
                                            {{ $rating }} star{{ $rating > 1 ? 's' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="fh-form-group">
                                <label for="searchHotelMaxPrice">Max price per night</label>
                                <select class="fh-control" name="max_price" id="searchHotelMaxPrice">
                                    <option value="">Any price</option>
                                    @foreach ($hotelPriceOptions as $step)
                                        <option value="{{ $step }}"{{ $selected('max_price', $step) }}>
                                            Up to NPR {{ number_format($step) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="fh-search-btn">
                                Search Hotels <i class="bi bi-arrow-right"></i>
                            </button>
                        </form>
                    </div>

                    {{-- Car: which car, when it is collected and returned, for how
                         long, and how many people are in the party. --}}
                    <div class="{{ $panelClass['vehicle'] }}{{ $type === 'vehicle' ? ' active' : '' }}" id="fh-carForm"
                        data-booking-type="vehicle"
                        data-end-date-order-error="{{ $endDateOrderMessages['vehicle'] }}">
                        <form class="fh-search-form" method="GET" action="{{ route('booking.search') }}">
                            <input type="hidden" name="type" value="vehicle">

                            <div class="fh-form-group">
                                <label for="searchVehicleType">Car type</label>
                                <select class="fh-control" name="vehicle_type" id="searchVehicleType">
                                    <option value="">Any type</option>
                                    @foreach ($vehicleTypeLabels as $vehicleType => $label)
                                        <option value="{{ $vehicleType }}"{{ $selected('vehicle_type', $vehicleType) }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="fh-form-group">
                                <label for="searchVehicleBrand">Brand</label>
                                <select class="fh-control" name="brand" id="searchVehicleBrand">
                                    <option value="">Any brand</option>
                                    @foreach ($vehicleBrands as $brand)
                                        <option value="{{ $brand }}"{{ $selected('brand', $brand) }}>
                                            {{ $brand }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="fh-form-group">
                                <div class="fh-date-field" data-date-field>
                                    <label class="fh-date-label" for="searchVehicleStart">{{ $dateLabels['vehicle']['start'] }}</label>
                                    <div class="fh-date-box">
                                        <span class="fh-date-value" data-date-display data-date-placeholder="Select date">{{ $dateDisplay($filterValue('start_date')) }}</span>
                                        <i class="bi bi-calendar3 fh-date-icon" aria-hidden="true"></i>
                                    </div>
                                    <input type="date" class="fh-date-input" name="start_date" id="searchVehicleStart"
                                        min="{{ $today }}" value="{{ $filterValue('start_date') }}"
                                        data-period-start>
                                </div>
                                <p class="fh-field-error" data-period-error="start_date"></p>
                            </div>

                            <div class="fh-form-group">
                                <div class="fh-date-field" data-date-field>
                                    <label class="fh-date-label" for="searchVehicleEnd">{{ $dateLabels['vehicle']['end'] }}</label>
                                    <div class="fh-date-box">
                                        <span class="fh-date-value" data-date-display data-date-placeholder="Select date">{{ $dateDisplay($filterValue('end_date')) }}</span>
                                        <i class="bi bi-calendar3 fh-date-icon" aria-hidden="true"></i>
                                    </div>
                                    <input type="date" class="fh-date-input" name="end_date" id="searchVehicleEnd"
                                        min="{{ $today }}" value="{{ $filterValue('end_date') }}" data-period-end>
                                </div>
                                <p class="fh-field-error" data-period-error="end_date"></p>
                            </div>

                            <div class="fh-form-group">
                                <label for="searchVehicleDuration">Rental duration</label>
                                {{-- "Any duration" imposes no restriction; a chosen
                                     length is what the pick-up/drop-off pair has to
                                     add up to. --}}
                                <select class="fh-control" name="duration_days" id="searchVehicleDuration"
                                    data-rental-duration>
                                    <option value="">Any duration</option>
                                    @foreach ($rentalDurations as $days)
                                        <option value="{{ $days }}"{{ $selected('duration_days', $days) }}>
                                            {{ $days }} day{{ $days > 1 ? 's' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="fh-form-group">
                                <label for="searchVehiclePassengers">Passengers</label>
                                <input type="number" class="fh-control" name="travelers" id="searchVehiclePassengers"
                                    min="1" max="{{ $maxVehiclePassengers }}" value="{{ $guests }}"
                                    data-party-input>
                            </div>

                            <div class="fh-form-group">
                                <label for="searchVehicleMaxPrice">Max price</label>
                                <select class="fh-control" name="max_price" id="searchVehicleMaxPrice">
                                    <option value="">Any price</option>
                                    @foreach ($vehiclePriceOptions as $step)
                                        <option value="{{ $step }}"{{ $selected('max_price', $step) }}>
                                            Up to NPR {{ number_format($step) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="fh-search-btn">
                                Search Cars <i class="bi bi-arrow-right"></i>
                            </button>
                        </form>
                    </div>

                    {{-- Tour: which destination, when it runs, how long it lasts and
                         how many people are going. --}}
                    <div class="{{ $panelClass['tour'] }}{{ $type === 'tour' ? ' active' : '' }}" id="fh-tourForm"
                        data-booking-type="tour"
                        data-end-date-order-error="{{ $endDateOrderMessages['tour'] }}">
                        <form class="fh-search-form" method="GET" action="{{ route('booking.search') }}">
                            <input type="hidden" name="type" value="tour">

                            <div class="fh-form-group">
                                <label for="searchTourLocation">Destination</label>
                                <select class="fh-control" name="location" id="searchTourLocation">
                                    <option value="">Any destination</option>
                                    @foreach ($tourLocations as $location)
                                        <option value="{{ $location }}"{{ $selected('location', $location) }}>
                                            {{ $location }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="fh-form-group">
                                <div class="fh-date-field" data-date-field>
                                    <label class="fh-date-label" for="searchTourStart">{{ $dateLabels['tour']['start'] }}</label>
                                    <div class="fh-date-box">
                                        <span class="fh-date-value" data-date-display data-date-placeholder="Select date">{{ $dateDisplay($filterValue('start_date')) }}</span>
                                        <i class="bi bi-calendar3 fh-date-icon" aria-hidden="true"></i>
                                    </div>
                                    <input type="date" class="fh-date-input" name="start_date" id="searchTourStart"
                                        min="{{ $today }}" value="{{ $filterValue('start_date') }}"
                                        data-period-start>
                                </div>
                                <p class="fh-field-error" data-period-error="start_date"></p>
                            </div>

                            <div class="fh-form-group">
                                <label for="searchTourDuration">Duration</label>
                                <select class="fh-control" name="duration_days" id="searchTourDuration">
                                    <option value="">Any duration</option>
                                    @foreach ($tourDurations as $days)
                                        <option value="{{ $days }}"{{ $selected('duration_days', $days) }}>
                                            {{ $days }} day{{ $days > 1 ? 's' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="fh-form-group">
                                <label for="searchTourTravelers">Travellers</label>
                                <input type="number" class="fh-control" name="travelers" id="searchTourTravelers"
                                    min="1" max="{{ $maxTourTravelers }}" value="{{ min($guests, $maxTourTravelers) }}"
                                    data-party-input>
                            </div>

                            <div class="fh-form-group">
                                <label for="searchTourMaxPrice">Max price</label>
                                <select class="fh-control" name="max_price" id="searchTourMaxPrice">
                                    <option value="">Any price</option>
                                    @foreach ($tourPriceOptions as $step)
                                        <option value="{{ $step }}"{{ $selected('max_price', $step) }}>
                                            Up to NPR {{ number_format($step) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="fh-search-btn">
                                Search Tours <i class="bi bi-arrow-right"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@if ($searched)
    <section class="fh-results-section" id="fh-resultsSection">
        <div class="container">
            <h2 class="fh-section-title mb-4">
                {{ $headings[$type] }}
                <small class="text-muted fs-6">{{ $results->count() }} found</small>
            </h2>

            @if ($results->isEmpty())
                <div class="jsp-empty-state">
                    No matching {{ $plural[$type] }} found. Please try different dates, destination or filters.
                </div>
            @else
                <div class="d-flex flex-column gap-3">
                    @foreach ($results as $result)
                        @php($summary = $summaries[$result->id] ?? null)
                        <div class="fh-flight-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="fh-airline-logo">
                                    <i class="bi {{ $icons[$type] }}"></i>
                                </div>
                                <div>
                                    <div class="fw-bold">{{ $type === 'vehicle' ? $result->name : $result->title }}</div>
                                    <div class="fh-result-meta">
                                        {{ $result->location_label ?? '—' }}
                                        @if ($type === 'tour')
                                            · {{ $result->duration }}
                                        @elseif ($type === 'vehicle')
                                            · {{ $result->type_label }}
                                            @if ($result->seating_capacity)
                                                · {{ $result->seating_capacity }} seats
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div>
                                @if ($type === 'hotel')
                                    <div class="text-warning">
                                        @for ($star = 1; $star <= 5; $star++)
                                            <i
                                                class="bi {{ $star <= (int) $result->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                                        @endfor
                                    </div>
                                @elseif ($type === 'tour' && $result->discount_percent)
                                    <span class="badge bg-success">{{ $result->discount_percent }}% off</span>
                                @endif
                            </div>

                            <div class="text-end">
                                <div class="fh-flight-price">
                                    @if ($type === 'vehicle')
                                        {{ $result->price_display }}
                                    @elseif ($type === 'tour' && $result->old_price)
                                        <small class="text-muted text-decoration-line-through">
                                            Rs. {{ number_format((float) $result->old_price, 0) }}
                                        </small>
                                        Rs. {{ number_format((float) $result->price, 0) }}
                                    @else
                                        Rs. {{ number_format((float) $result->price, 0) }}
                                        <small>/{{ $type === 'hotel' ? 'night' : 'package' }}</small>
                                    @endif
                                </div>
                                {{-- The link opens the shared popup and still points at the confirmation page, so
                                     the popup is an addition to the booking path rather than a replacement for it. --}}
                                <a href="{{ $bookNowUrl($result) }}" class="fh-book-now-btn mt-2 d-inline-block"
                                    data-bs-toggle="modal" data-bs-target="#fh-bookingSummaryModal"
                                    data-booking-summary="{{ $type }}-{{ $result->id }}">
                                    {{ $type === 'vehicle' ? 'Rent' : 'Book Now' }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <p class="mt-4 mb-0">
                    <a href="{{ route('booking.search', ['type' => $type]) }}">Clear this search</a>
                </p>
            @endif
        </div>
    </section>
@endif

{{-- One popup serves every result on the page. Its content comes from this JSON payload, which
     holds the server's own summary of each result: the popup is filled in from it, so no price,
     date or availability answer is ever decided in the browser. --}}
@if ($searched)
    <script type="application/json" id="fh-bookingSummaries">{{ $bookingSummariesJson }}</script>

    <div class="modal fade" id="fh-bookingSummaryModal" tabindex="-1" aria-labelledby="fh-bookingSummaryTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title fs-4" id="fh-bookingSummaryTitle" data-summary-heading>Booking Summary
                        </h2>
                        <p class="text-muted small mb-0" data-summary-service></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="fh-bookingSummaryForm" method="POST" action="{{ route('bookings.store') }}">
                    @csrf

                    {{-- The service comes from the result that was clicked, never from a picker, and every
                         value in here is the server's own summary of that result. --}}
                    <input type="hidden" name="booking_type" data-summary-field="bookingType">
                    <input type="hidden" name="service_id" data-summary-field="serviceId">
                    <input type="hidden" name="start_date" data-summary-field="startDate">
                    <input type="hidden" name="end_date" data-summary-field="endDate">
                    <input type="hidden" name="travelers" data-summary-field="travelers">
                    <input type="hidden" name="submission_token" data-summary-field="submissionToken">

                    <div class="modal-body">
                        <div data-summary-rows></div>

                        <div class="fh-summary-card mt-3">
                            <div class="fh-quote-row">
                                <span data-summary-unit></span>
                                <span data-summary-subtotal></span>
                            </div>
                            <p class="text-muted small mb-0" data-summary-note></p>
                            <div class="fh-quote-row d-none" data-summary-discount-row>
                                <span class="text-danger" data-summary-discount-label></span>
                                <span class="fh-quote-figure text-danger" data-summary-discount></span>
                            </div>
                            <div class="fh-quote-row" data-summary-tax-row>
                                <span data-summary-tax-label></span>
                                <span data-summary-tax></span>
                            </div>
                            <div class="fh-quote-row" data-summary-charge-row>
                                <span data-summary-charge-label></span>
                                <span data-summary-charge></span>
                            </div>
                            <div class="fh-quote-row fh-quote-total">
                                <span>Total</span>
                                <span data-summary-total></span>
                            </div>
                        </div>

                        <div class="alert alert-warning mt-3 mb-0 d-none" data-summary-notice role="alert"></div>

                        @auth
                            <div class="row g-3 mt-1">
                                <div class="col-12 col-md-6">
                                    <div class="fh-form-group">
                                        <label for="fh-summaryName">FULL NAME</label>
                                        <input type="text" class="fh-control" name="name" id="fh-summaryName"
                                            value="{{ old('name', auth()->user()->name) }}" required>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="fh-form-group">
                                        <label for="fh-summaryEmail">EMAIL</label>
                                        <input type="email" class="fh-control" name="email" id="fh-summaryEmail"
                                            value="{{ old('email', auth()->user()->email) }}" required>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="fh-form-group">
                                        <label for="fh-summaryPhone">PHONE</label>
                                        <input type="tel" class="fh-control" name="phone" id="fh-summaryPhone"
                                            value="{{ old('phone', auth()->user()->phone) }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-check mt-3 mb-0">
                                <input class="form-check-input" type="checkbox" name="policy_accepted" value="1"
                                    id="fh-summaryPolicy" @checked(old('policy_accepted')) required>
                                <label class="form-check-label small" for="fh-summaryPolicy">
                                    I have read and accept the booking and cancellation policy.
                                </label>
                            </div>
                        @endauth

                        <p class="text-muted small mb-0 mt-3">
                            @if ($isGuest)
                                You will be asked to sign in first, then brought straight back here to finish this
                                booking.
                            @else
                                Your booking is created straight away with these details.
                            @endif
                        </p>
                    </div>

                    <div class="modal-footer d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>

                        <button type="submit" class="fh-search-btn" data-summary-submit>
                            <span data-summary-submit-label data-default-label="Confirm Booking">Confirm Booking</span>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@include('frontend.layouts.footer')
