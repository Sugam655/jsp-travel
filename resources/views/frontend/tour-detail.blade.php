@include('frontend.layouts.header')

@include('frontend.layouts.mobile-nav')

@php
    $tourImage = $tour->image_url;
    $destinationImage = $tour->destination?->image_url;

    $galleryImages = collect([$tourImage, $destinationImage])
        ->filter()
        ->unique()
        ->values();
@endphp

<header class="td-hero"
    style="@if ($tourImage) --td-hero-image: url('{{ $tourImage }}'); @endif">
    <div class="td-hero-content">
        <div class="container-fluid td-container px-4 px-xl-5">
            <h1 class="td-hero-title">{{ $tour->title }}</h1>

            <nav aria-label="Breadcrumb">
                <ol class="td-breadcrumb">
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li class="td-breadcrumb-divider" aria-hidden="true">/</li>
                    <li><a href="{{ route('tours.index') }}">Tours</a></li>
                    <li class="td-breadcrumb-divider" aria-hidden="true">/</li>
                    <li class="td-breadcrumb-current" aria-current="page">{{ $tour->title }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="td-hero-actions">
        <a href="{{ route('bookings.create', ['type' => 'tour', 'slug' => $tour->slug]) }}"
            class="td-estimate-btn">Book Now</a>
    </div>
</header>

<main class="td-section" id="tour-details">
    <div class="container-fluid td-container px-4 px-xl-5">
        <div class="row g-4 g-xxl-5 align-items-start">

            <!-- Left Content -->
            <div class="col-lg-9">

                <!-- Tour Details -->
                <section>
                    <h1 class="td-main-title">Tour Details</h1>

                    @if (filled($tour->description))
                        <p class="td-description">{{ $tour->description }}</p>
                    @else
                        <p class="td-description">
                            No description has been added for this package yet. Please contact us for full details.
                        </p>
                    @endif

                    @if ($galleryImages->isNotEmpty())
                        <!-- Tour Gallery -->
                        <div class="row g-4 g-xxl-5">
                            @foreach ($galleryImages as $image)
                                <div class="col-sm-6 col-xl-4">
                                    <div class="td-gallery-item">
                                        <img src="{{ $image }}" alt="{{ $tour->title }}">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                <!-- Package Highlights -->
                <section class="trip-page" aria-label="Package highlights">
                    <div class="trip-container">

                        <h2 class="td-main-title">Package Highlights</h2>

                        <dl class="row g-4 td-highlight-list">
                            @if (filled($tour->duration))
                                <div class="col-sm-6">
                                    <dt class="td-highlight-label">Duration</dt>
                                    <dd class="td-highlight-value">{{ $tour->duration }}</dd>
                                </div>
                            @endif

                            @if (filled($tour->location_label))
                                <div class="col-sm-6">
                                    <dt class="td-highlight-label">Location</dt>
                                    <dd class="td-highlight-value">{{ $tour->location_label }}</dd>
                                </div>
                            @endif

                            @if ($tour->duration_days)
                                <div class="col-sm-6">
                                    <dt class="td-highlight-label">Days</dt>
                                    <dd class="td-highlight-value">{{ $tour->duration_days }}</dd>
                                </div>
                            @endif

                            @if ($tour->capacity)
                                <div class="col-sm-6">
                                    <dt class="td-highlight-label">Group Size</dt>
                                    <dd class="td-highlight-value">Up to {{ $tour->capacity }} travellers</dd>
                                </div>
                            @endif
                        </dl>

                        <div class="trip-includes">
                            <h2 class="trip-section-title">Good to Know</h2>

                            <ul class="trip-list">
                                <li class="trip-list-item">
                                    <span class="trip-list-icon">
                                        <i class="bi bi-check-lg"></i>
                                    </span>
                                    <span>
                                        A detailed day-by-day itinerary is shared with you once the booking is
                                        confirmed.
                                    </span>
                                </li>

                                <li class="trip-list-item">
                                    <span class="trip-list-icon">
                                        <i class="bi bi-check-lg"></i>
                                    </span>
                                    <span>
                                        The suggested itinerary can be further customized upon request.
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Right Sidebar -->
            <aside class="col-lg-3 td-sidebar-column">
                <div class="td-sidebar">
                    <h2 class="td-sidebar-title">Package Info</h2>

                    <ul class="td-info-list">
                        @if (filled($tour->duration))
                            <li>Duration: {{ $tour->duration }}</li>
                        @endif
                        @if (filled($tour->location_label))
                            <li>Location: {{ $tour->location_label }}</li>
                        @endif
                        @if ($tour->capacity)
                            <li>Group size: Up to {{ $tour->capacity }} travellers</li>
                        @endif
                    </ul>

                    <div class="td-price-box">
                        <span class="from-label">From</span>
                        <span class="td-price">Rs.{{ number_format((float) $tour->price) }}</span>
                        @if ($tour->discount_percent)
                            <span class="td-discount">{{ $tour->discount_percent }}% OFF</span>
                        @endif
                    </div>

                    <div class="td-help-box">
                        <h3 class="td-help-title">
                            Need help organizing your holiday plans?
                        </h3>

                        <p class="td-help-text">
                            Please note: The suggested itinerary can be further customized upon request.
                        </p>

                        <a class="td-phone" href="{{ route('contact.index') }}">
                            <i class="bi bi-telephone-fill"></i>
                            <span>Contact our team</span>
                        </a>
                    </div>
                </div>
            </aside>

        </div>
    </div>
</main>

@if ($relatedTours->isNotEmpty())
    <section class="jsp-tour-section">
        <div class="container-fluid">

            <!-- Section Heading -->
            <div class="jsp-tour-heading text-center">
                <h2>
                    RECOMMENDED TOUR PACKAGES
                </h2>

                <div class="jsp-heading-decoration">
                    <span></span>
                    <i></i>
                    <span></span>
                </div>
            </div>

            <div class="row g-4">
                @foreach ($relatedTours as $relatedTour)
                    <div class="col-lg-4 col-md-6">
                        @include('frontend.partials.tour-card', ['tour' => $relatedTour])
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@include('frontend.layouts.footer')
