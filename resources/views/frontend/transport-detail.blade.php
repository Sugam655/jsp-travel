@include('frontend.layouts.header')

@include('frontend.layouts.mobile-nav')

<section class="about bg-white">

    <div class="trvl-breadcrumb-wrap" style="
        background-image:
        linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)),
        url('{{ $vehicle->image_url }}');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    ">

        <div class="container py-5">
            <div class="row align-items-center">

                <div class="col-md-6 text-center text-md-start">
                    <h1 class="trvl-page-title text-white mb-0">
                        {{ $vehicle->name }}
                    </h1>
                </div>

                <div class="col-md-6">
                    <ul class="trvl-breadcrumb-list
                       d-flex
                       justify-content-center
                       justify-content-md-end
                       align-items-center
                       gap-2
                       list-unstyled
                       mb-0">

                        <li>
                            <a href="{{ route('home') }}" class="text-white text-decoration-none">
                                Home
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('transport') }}" class="text-white text-decoration-none">
                                Transport
                            </a>
                        </li>

                        <li class="active text-white-50">
                            {{ $vehicle->name }}
                        </li>

                    </ul>
                </div>
            </div>
        </div>
    </div>

    <section class="trvl-section">
        <div class="container">
            <div class="row g-4 g-lg-5">

                <div class="col-lg-7">
                    <div class="rounded-4 overflow-hidden shadow-sm">
                        <img src="{{ $vehicle->image_url }}" alt="{{ $vehicle->name }}" class="w-100"
                            style="max-height: 480px; object-fit: cover;">
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="pkg-bd p-4">
                        <h2 class="pkg-nm fs-4 mb-2">{{ $vehicle->name }}</h2>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge text-bg-dark">
                                <i class="fa-solid fa-car me-1"></i> {{ $vehicle->type_label }}
                            </span>
                            @if ($vehicle->availability)
                                <span class="badge text-bg-success">Available for Rent</span>
                            @else
                                <span class="badge text-bg-secondary">Currently Unavailable</span>
                            @endif
                        </div>

                        @if ($vehicle->destination || $vehicle->location)
                            <div class="mb-2">
                                <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                {{ $vehicle->location_label }}
                            </div>
                        @endif

                        <div class="row g-2 mb-3">
                            @if ($vehicle->seating_capacity)
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center gap-2 text-muted">
                                        <i class="fas fa-users text-primary"></i>
                                        {{ $vehicle->seating_capacity }} Seats
                                    </div>
                                </div>
                            @endif
                            @if ($vehicle->transmission)
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center gap-2 text-muted">
                                        <i class="fas fa-gear text-primary"></i>
                                        {{ $vehicle->transmission }}
                                    </div>
                                </div>
                            @endif
                            @if ($vehicle->year)
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center gap-2 text-muted">
                                        <i class="fas fa-calendar text-primary"></i>
                                        Model {{ $vehicle->year }}
                                    </div>
                                </div>
                            @endif
                            @if ($vehicle->brand)
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center gap-2 text-muted">
                                        <i class="fas fa-tag text-primary"></i>
                                        {{ $vehicle->brand }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if ($vehicle->price_unit !== 'contact')
                            <div class="nest-propertyprice mb-3 mt-3">
                                <small>Starting from</small>
                                <strong>Rs.{{ number_format((float) $vehicle->price) }} / {{ strtolower($vehicle->price_unit_label) }}</strong>
                            </div>
                        @else
                            <div class="nest-propertyprice mb-3 mt-3">
                                <small>Pricing</small>
                                <strong>Contact for price</strong>
                            </div>
                        @endif

                        @if ($vehicle->short_description)
                            <p class="text-muted">{{ $vehicle->short_description }}</p>
                        @endif

                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <a href="{{ route('bookings.create', ['type' => 'vehicle', 'slug' => $vehicle->slug]) }}" class="btn btn-primary btn-lg">
                                Book Now
                                <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i>
                            </a>
                            <a href="{{ route('transport') }}" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-arrow-left me-1"></i> View All Vehicles
                            </a>
                        </div>
                    </div>
                </div>

                @if ($vehicle->description || $vehicle->features)
                    <div class="col-12">
                        <div class="row g-4">
                            @if ($vehicle->description)
                                <div class="col-lg-8">
                                    <div class="trvl-content-box">
                                        <span class="trvl-label">About This Vehicle</span>
                                        <div class="trvl-desc">{!! nl2br(e($vehicle->description)) !!}</div>
                                    </div>
                                </div>
                            @endif
                            @if ($vehicle->features)
                                <div class="col-lg-4">
                                    <div class="trvl-content-box">
                                        <span class="trvl-label">Vehicle Features</span>
                                        <ul class="list-unstyled mb-0">
                                            @foreach (preg_split('/\R/', trim((string) $vehicle->features)) as $feature)
                                                @if (trim($feature))
                                                    <li class="d-flex align-items-start gap-2 mb-2">
                                                        <i class="fa-solid fa-circle-check text-success mt-1"></i>
                                                        {{ trim($feature) }}
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
</section>

@include('frontend.layouts.footer')