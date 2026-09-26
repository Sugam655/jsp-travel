@include('frontend.layouts.header')

@include('frontend.layouts.mobile-nav')

<section class="about bg-white">

    <div class="trvl-breadcrumb-wrap" style="
        background-image:
        linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)),
        url('{{ $hotel->image_url }}');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    ">

        <div class="container py-5">
            <div class="row align-items-center">

                <div class="col-md-6 text-center text-md-start">
                    <h1 class="trvl-page-title text-white mb-0">
                        {{ $hotel->title }}
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
                            <a href="{{ route('hotel') }}" class="text-white text-decoration-none">
                                Hotels
                            </a>
                        </li>

                        <li class="active text-white-50">
                            {{ $hotel->title }}
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
                        <img src="{{ $hotel->image_url }}" alt="{{ $hotel->title }}" class="w-100"
                            style="max-height: 480px; object-fit: cover;">
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="pkg-bd p-4">
                        <h2 class="pkg-nm fs-4 mb-2">{{ $hotel->title }}</h2>

                        @if ($hotel->rating)
                            <div class="mb-2">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="fa-solid fa-star {{ $i <= $hotel->rating ? 'text-warning' : 'text-secondary' }}"></i>
                                @endfor
                                <span class="text-muted ms-1">{{ $hotel->rating }}/5</span>
                            </div>
                        @endif

                        @if ($hotel->destination || $hotel->location)
                            <div class="mb-2">
                                <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                {{ $hotel->location_label }}
                            </div>
                        @endif

                        @if ($hotel->address)
                            <div class="mb-2">
                                <i class="fas fa-building me-2 text-primary"></i>
                                {{ $hotel->address }}
                            </div>
                        @endif

                        <div class="nest-propertyprice mb-3 mt-3">
                            <small>Starting from</small>
                            <strong>Rs.{{ number_format((float) $hotel->price) }} / night</strong>
                        </div>

                        @if ($hotel->short_description)
                            <p class="text-muted">{{ $hotel->short_description }}</p>
                        @endif

                        <div class="row g-2 mb-4">
                            @if ($hotel->phone)
                                <div class="col-12">
                                    <div class="d-flex align-items-center gap-2 text-muted">
                                        <i class="fas fa-phone text-primary"></i>
                                        {{ $hotel->phone }}
                                    </div>
                                </div>
                            @endif
                            @if ($hotel->email)
                                <div class="col-12">
                                    <div class="d-flex align-items-center gap-2 text-muted">
                                        <i class="fas fa-envelope text-primary"></i>
                                        {{ $hotel->email }}
                                    </div>
                                </div>
                            @endif
                            @if ($hotel->website)
                                <div class="col-12">
                                    <div class="d-flex align-items-center gap-2 text-muted">
                                        <i class="fas fa-globe text-primary"></i>
                                        <a href="{{ $hotel->website }}" target="_blank" rel="noopener"
                                            class="text-decoration-none">{{ $hotel->website }}</a>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <a href="{{ route('bookings.create', ['type' => 'hotel', 'slug' => $hotel->slug]) }}" class="btn btn-primary btn-lg">
                                Book Now
                                <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i>
                            </a>
                            <a href="{{ route('hotel') }}" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-arrow-left me-1"></i> View All Hotels
                            </a>
                        </div>
                    </div>
                </div>

                @if ($hotel->description)
                    <div class="col-12">
                        <div class="trvl-content-box">
                            <span class="trvl-label">About This Hotel</span>
                            <div class="trvl-desc">{!! nl2br(e($hotel->description)) !!}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
</section>

@include('frontend.layouts.footer')