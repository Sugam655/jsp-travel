@include('frontend.layouts.header')


@include('frontend.layouts.mobile-nav')

    @php
        $hero = $hero ?? null;
        $defaults = $defaults ?? \Modules\Home\Models\HomeHero::DEFAULTS;
        $destinations = $destinations ?? collect();
        $whyChooseUsDefaults = $whyChooseUsDefaults ?? \Modules\Home\Models\HomeWhyChooseUs::DEFAULTS;
        $whyChooseUs = $whyChooseUs ?? null;
        $stories = $stories ?? collect();
        $services = $services ?? collect();

        $heroTitle = $hero?->title ?: $defaults['title'];
        $heroDescription = $hero?->description ?: $defaults['description'];
        $heroImage = $hero?->background_image_url ?: $defaults['background_image'];
        $heroButtonText = $hero?->button_text ?: $defaults['button_text'];
        $heroButtonUrl = $hero?->button_url ?: $defaults['button_url'];

        $wcuSmallTitle = $whyChooseUs?->small_title ?: $whyChooseUsDefaults['small_title'];
        $wcuTitle = $whyChooseUs?->title ?: $whyChooseUsDefaults['title'];
        $wcuLeft1 = $whyChooseUs?->left_paragraph_1 ?: $whyChooseUsDefaults['left_paragraph_1'];
        $wcuLeft2 = $whyChooseUs?->left_paragraph_2 ?: $whyChooseUsDefaults['left_paragraph_2'];
        $wcuRight1 = $whyChooseUs?->right_paragraph_1 ?: $whyChooseUsDefaults['right_paragraph_1'];
        $wcuRight2 = $whyChooseUs?->right_paragraph_2 ?: $whyChooseUsDefaults['right_paragraph_2'];

        $storySlides = $stories->chunk(3);
    @endphp

    <section class="hero-section">
        <div class="hero-bg">
            <img id="heroBgImage"
                src="{{ $heroImage }}"
                alt="Destination">
        </div>

        <div class="hero-content">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-lg-7">
                        <div class="left-content">
                            <!-- <div class="badge-new">
                                <span>New</span>
                                <span>Travel Beyond Expectations</span>
                            </div> -->
                            <h1 class="hero-title" id="heroTitle">{{ $heroTitle }}</h1>
                            <p class="hero-desc" id="heroDescription">
                                {{ $heroDescription }}
                            </p>
                            <a href="{{ $heroButtonUrl }}" class="btn-explore">
                                {{ $heroButtonText }}
                                <i class="fas fa-arrow-up-right-from-square" style="font-size: 0.8rem;"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="thumbnails-col" id="thumbnailsContainer"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pagination-dots" id="paginationDots"></div>
    </section>
    
    <section class="nepalwhy-section" id="nepalwhySection">

        <div class="container">

            <!-- Heading -->
            <div class="nepalwhy-heading text-center">

                <h5 class="nepalwhy-small-title">
                    {{ $wcuSmallTitle }}
                </h5>

                <h2 class="nepalwhy-main-title">
                    {{ $wcuTitle }}
                </h2>

                <!-- Decorative Line -->
                <div class="nepalwhy-divider">
                    <span class="nepalwhy-line"></span>

                    <span class="nepalwhy-zigzag">
                        <i></i>
                        <i></i>
                        <i></i>
                        <i></i>
                    </span>

                    <span class="nepalwhy-line"></span>
                </div>

            </div>


            <!-- Content -->
            <div class="row nepalwhy-content g-5">

                <!-- Left Column -->
                <div class="col-lg-6">
    
                    <div class="nepalwhy-text">

                        <p>{!! $wcuLeft1 !!}</p>

                        <p>{!! $wcuLeft2 !!}</p>

                    </div>

                </div>


                <!-- Right Column -->
                <div class="col-lg-6">

                    <div class="nepalwhy-text">

                        <p>{!! $wcuRight1 !!}</p>

                        <p>{!! $wcuRight2 !!}</p>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <section class="jsp-testimonials">
        <div class="container position-relative">
            <div class="row align-items-end g-4 mb-5">
                <div class="col-lg-8">
                    <span class="jsp-eyebrow">Traveler Stories</span>
                    <h2 class="jsp-heading">Journeys remembered.<br><span>Stories worth sharing.</span></h2>
                </div>
                <div class="col-lg-4">
                    <p class="jsp-intro mb-0">Real experiences from travelers who explored beautiful destinations with
                        us.</p>
                </div>
            </div>

            <div id="jspTestimonialSlider">
                @foreach ($storySlides as $slide)
                    <div class="jsp-slider-item {{ $loop->first ? 'jsp-active' : '' }}">
                        <div class="row g-4">
                            @foreach ($slide as $story)
                                <div class="col-lg-4">
                                    <article class="jsp-card"><i class="bi bi-quote jsp-quote-icon"></i>
                                        <div class="jsp-stars">★★★★★</div>
                                        <p class="jsp-review">{{ $story->review }}</p>
                                        <div class="d-flex align-items-center"><img class="jsp-avatar"
                                                src="{{ $story->avatar_url }}"
                                                alt="{{ $story->author_name }}">
                                            <div class="ms-3">
                                                <h3 class="jsp-name">{{ $story->author_name }}</h3>
                                                <p class="jsp-trip">{{ $story->trip }}</p>
                                            </div><i class="bi bi-patch-check-fill jsp-verified" title="Verified traveler"></i>
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex align-items-center justify-content-between mt-5">
                <div class="jsp-dots">
                    @foreach ($storySlides as $slide)
                        <button class="jsp-dot {{ $loop->first ? 'jsp-active' : '' }}"
                            aria-label="Testimonials page {{ $loop->iteration }}"></button>
                    @endforeach
                </div>
                <div class="d-flex gap-2"><button class="jsp-control" id="jspPrev" aria-label="Previous testimonials"><i
                            class="bi bi-arrow-left"></i></button><button class="jsp-control" id="jspNext"
                        aria-label="Next testimonials"><i class="bi bi-arrow-right"></i></button></div>
            </div>
        </div>
    </section>

    <section class="jsp-tour-section">

        <div class="container-fluid">

            <!-- Section Heading -->
            <div class="jsp-tour-heading text-center">

                <h2>
                    OUR SERVICES
                </h2>

                <div class="jsp-heading-decoration">
                    <span></span>
                    <i></i>
                    <span></span>
                </div>

            </div>


            <!-- Tour Cards -->
            <div class="row g-4 jsp-tour-row">

                @forelse ($services as $service)

                <div class="col-lg-4 col-md-6">

                    <div class="jsp-tour-card">

                        <img src="{{ $service->image_url }}"
                            alt="{{ $service->title }}" class="jsp-tour-image">

                        <div class="jsp-tour-overlay"></div>

                        <!-- Card Content -->
                        <div class="jsp-tour-content">

                            <h3>
                                {{ $service->title }}
                                @if ($service->subtitle)
                                    <br>
                                    {{ $service->subtitle }}
                                @endif
                            </h3>

                            <a href="{{ $service->button_url }}" class="jsp-tour-btn">
                                {{ $service->button_text ?? 'Learn More' }}
                                <i class="bi bi-arrow-right"></i>
                            </a>

                        </div>

                    </div>

                </div>

                @empty
                    <div class="col-12 text-center text-muted">
                        Our services are coming soon.
                    </div>
                @endforelse

            </div>

        </div>

    </section>

    <section class="popular">
        <div class="container">
            <!-- Section Header -->
            <div class="section-header">
                <h2 class="section-title">
                    Popular Destinations
                    <i class="fas fa-plane plane-icon"></i>
                </h2>
        <a href="{{ route('destinations.index') }}" class="btn-view-all">
    View All Destinations
    <i class="fas fa-arrow-right"></i>
</a>
            </div>

            <!-- Destinations Grid -->
            <div class="row g-4">
                @forelse ($destinations as $destination)
                    <div class="col-lg-3 col-md-6">
                        <div class="dest-card">
                            <img src="{{ $destination->image_url }}" alt="{{ $destination->name }}">
                            @if ($destination->badge)
                                <span class="dest-badge">{{ $destination->badge }}</span>
                            @endif
                            <div class="dest-overlay">
                                <div class="dest-info">
                                    <div>
                                        <div class="dest-name">{{ $destination->name }}</div>
                                        <div class="dest-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            {{ $destination->location }}
                                        </div>
                                    </div>
                                    <div class="dest-price">
                                        <span class="from-label">From</span>
                                        <span class="price">{{ $destination->price }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center text-muted">
                        Destinations are coming soon.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section>

    @include("frontend.layouts.footer");

    