@include('frontend.layouts.header')

@include('frontend.layouts.mobile-nav')

<section class="about bg-white">

    <div class="trvl-breadcrumb-wrap" style="
        background-image:
        linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)),
        url('{{ $tour->image_url }}');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    ">

        <div class="container py-5">
            <div class="row align-items-center">

                <div class="col-md-6 text-center text-md-start">
                    <h1 class="trvl-page-title text-white mb-0">
                        {{ $tour->title }}
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
                            <a href="{{ route('tours.index') }}" class="text-white text-decoration-none">
                                Tour Packages
                            </a>
                        </li>

                        <li class="active text-white-50">
                            {{ $tour->title }}
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
                        <img src="{{ $tour->image_url }}" alt="{{ $tour->title }}" class="w-100"
                            style="max-height: 480px; object-fit: cover;">
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="pkg-bd p-4">
                        <div class="pkg-nm fs-4 mb-3">{{ $tour->title }}</div>

                        @if ($tour->destination || $tour->location)
                            <div class="mb-2">
                                <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                {{ $tour->location_label }}
                            </div>
                        @endif

                        <div class="mb-3">
                            <i class="far fa-clock me-2 text-primary"></i>
                            {{ $tour->duration }}
                        </div>

                        <div class="pkg-pr mb-4">
                            <span class="cur-pr fs-3">Rs.{{ number_format((float) $tour->price) }}</span>
                            @if ($tour->old_price)
                                <span class="old-pr ms-2">Rs.{{ number_format((float) $tour->old_price) }}</span>
                            @endif
                        </div>

                        @if ($tour->discount_percent)
                            <span class="disc d-inline-block mb-4">{{ $tour->discount_percent }}% OFF</span>
                        @endif

                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <a href="{{ route('bookings.create', ['type' => 'tour', 'slug' => $tour->slug]) }}" class="btn btn-primary btn-lg">
                                Book Now
                                <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i>
                            </a>
                            <a href="{{ route('tours.index') }}" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-arrow-left me-1"></i> View All Packages
                            </a>
                        </div>
                    </div>
                </div>

                @if ($tour->description)
                    <div class="col-12">
                        <div class="trvl-content-box">
                            <span class="trvl-label">Package Details</span>
                            <div class="trvl-desc">{!! nl2br(e($tour->description)) !!}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
</section>

@include('frontend.layouts.footer')