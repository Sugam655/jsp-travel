@include('frontend.layouts.header')

@include('frontend.layouts.mobile-nav')

    <section class="vista-showcase" aria-label="Featured destinations">
        <div class="vista-backdrop">
            <img id="vista-bg-img" src="https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?w=1920&q=85"
                alt="Beautiful Pokhara">
        </div>

        <div class="vista-wrap">
            <div class="container-fluid px-0">
                <div class="row align-items-end gy-4">
                    <div class="col-lg-6">
                        <div class="vista-main">
                            <div id="vista-loc" class="vista-loc">Pokhara - Nepal</div>
                            <h1 id="vista-headline" class="vista-headline">Beautiful<br>Pokhara</h1>
                            <p id="vista-summary" class="vista-summary">Discover peaceful lakes, magnificent mountains
                                and unforgettable adventure experiences in Pokhara.</p>
                            <a id="vista-link" href="#pokhara" class="vista-pill">
                                <i class="fa-solid fa-location-dot"></i>
                                Discover Location
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="vista-deck" aria-label="Scroll destinations horizontally">
                            <article class="vista-card active" tabindex="0" role="button" aria-label="Select Pokhara"
                                aria-selected="true" data-location="Pokhara - Nepal" data-title="Beautiful|Pokhara"
                                data-description="Discover peaceful lakes, magnificent mountains and unforgettable adventure experiences in Pokhara."
                                data-background="https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?w=1920&q=85"
                                data-link="#pokhara">
                                <img src="https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?w=500&q=80"
                                    alt="Beautiful Pokhara">
                            </article>

                            <article class="vista-card" tabindex="0" role="button" aria-label="Select Kathmandu"
                                aria-selected="false" data-location="Kathmandu - Nepal" data-title="Historic|Kathmandu"
                                data-description="Explore ancient temples, traditional architecture and the rich cultural heritage of Kathmandu Valley."
                                data-background="https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=1920&q=85"
                                data-link="#kathmandu">
                                <img src="https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=500&q=80"
                                    alt="Historic Kathmandu">
                            </article>

                            <article class="vista-card" tabindex="0" role="button" aria-label="Select Chitwan"
                                aria-selected="false" data-location="Chitwan - Nepal" data-title="Wild|Chitwan"
                                data-description="Experience jungle safaris, incredible wildlife, Tharu culture and the natural beauty of Chitwan National Park."
                                data-background="https://images.unsplash.com/photo-1518002054494-3a6f94352e9d?w=1920&q=85"
                                data-link="#chitwan">
                                <img src="https://images.unsplash.com/photo-1518002054494-3a6f94352e9d?w=500&q=80"
                                    alt="Wild Chitwan">
                            </article>

                            <article class="vista-card" tabindex="0" role="button" aria-label="Select Mustang"
                                aria-selected="false" data-location="Mustang - Nepal" data-title="Mystical|Mustang"
                                data-description="Travel through dramatic mountain deserts, ancient monasteries and the timeless villages of Mustang."
                                data-background="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=1920&q=85"
                                data-link="#mustang">
                                <img src="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=500&q=80"
                                    alt="Mystical Mustang">
                            </article>

                            <article class="vista-card" tabindex="0" role="button" aria-label="Select Everest"
                                aria-selected="false" data-location="Everest - Nepal" data-title="Majestic|Everest"
                                data-description="Walk among the world's highest mountains and experience the legendary trails of the Everest region."
                                data-background="https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?w=1920&q=85"
                                data-link="#everest">
                                <img src="https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?w=500&q=80"
                                    alt="Majestic Everest">
                            </article>

                            <article class="vista-card" tabindex="0" role="button" aria-label="Select Lumbini"
                                aria-selected="false" data-location="Lumbini - Nepal" data-title="Peaceful|Lumbini"
                                data-description="Visit the birthplace of Lord Buddha and discover sacred monasteries, peaceful gardens and spiritual heritage."
                                data-background="https://images.unsplash.com/photo-1605640840605-14ac1855827b?w=1920&q=85"
                                data-link="#lumbini">
                                <img src="https://images.unsplash.com/photo-1605640840605-14ac1855827b?w=500&q=80"
                                    alt="Peaceful Lumbini">
                            </article>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="vista-footer">
            <div class="vista-track">
                <div id="vista-track-fill" class="vista-track-fill" style="width: 16.6667%;"></div>
            </div>
            <div id="vista-count" class="vista-count">01 / 06</div>
        </div>
    </section>

    <section class="Packages">
        <div class="container">
            <div class="sec-hd">
                <h2 class="sec-ttl">
                    Popular Tour Packages
                    <span class="wv-ln"></span>
                </h2>
                <a href="{{ route('tours.index') }}" class="btn-all">
                    View all packages
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="row g-4">
                @forelse ($tours as $tour)
                    <div class="col-lg-4 col-md-6">
                        @include('frontend.partials.tour-card', ['tour' => $tour])
                    </div>
                @empty
                    <div class="col-12 text-center text-muted">
                        Tour packages are coming soon.
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
                <div class="jsp-slider-item jsp-active">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <article class="jsp-card"><i class="bi bi-quote jsp-quote-icon"></i>
                                <div class="jsp-stars">★★★★★</div>
                                <p class="jsp-review">“Everything was perfectly organized—from our airport pickup to the
                                    mountain lodge. We simply relaxed and enjoyed every magical moment.”</p>
                                <div class="d-flex align-items-center"><img class="jsp-avatar"
                                        src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=160&h=160&fit=crop"
                                        alt="Sophia Carter">
                                    <div class="ms-3">
                                        <h3 class="jsp-name">Sophia Carter</h3>
                                        <p class="jsp-trip">Everest Base Camp, Nepal</p>
                                    </div><i class="bi bi-patch-check-fill jsp-verified" title="Verified traveler"></i>
                                </div>
                            </article>
                        </div>
                        <div class="col-lg-4">
                            <article class="jsp-card"><i class="bi bi-quote jsp-quote-icon"></i>
                                <div class="jsp-stars">★★★★★</div>
                                <p class="jsp-review">“The guides felt like old friends and showed us places we would
                                    never have discovered alone. Nepal completely stole our hearts.”</p>
                                <div class="d-flex align-items-center"><img class="jsp-avatar"
                                        src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=160&h=160&fit=crop"
                                        alt="Daniel Moore">
                                    <div class="ms-3">
                                        <h3 class="jsp-name">Daniel Moore</h3>
                                        <p class="jsp-trip">Annapurna Circuit, Nepal</p>
                                    </div><i class="bi bi-patch-check-fill jsp-verified" title="Verified traveler"></i>
                                </div>
                            </article>
                        </div>
                        <div class="col-lg-4">
                            <article class="jsp-card"><i class="bi bi-quote jsp-quote-icon"></i>
                                <div class="jsp-stars">★★★★★</div>
                                <p class="jsp-review">“Beautiful hotels, safe transport and such thoughtful service. Our
                                    honeymoon became even more special than we imagined.”</p>
                                <div class="d-flex align-items-center"><img class="jsp-avatar"
                                        src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=160&h=160&fit=crop"
                                        alt="Emma Wilson">
                                    <div class="ms-3">
                                        <h3 class="jsp-name">Emma Wilson</h3>
                                        <p class="jsp-trip">Pokhara Escape, Nepal</p>
                                    </div><i class="bi bi-patch-check-fill jsp-verified" title="Verified traveler"></i>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>

                <div class="jsp-slider-item">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <article class="jsp-card"><i class="bi bi-quote jsp-quote-icon"></i>
                                <div class="jsp-stars">★★★★★</div>
                                <p class="jsp-review">“A flawless cultural tour with wonderful food, warm people and
                                    expert planning. Every day brought a new unforgettable experience.”</p>
                                <div class="d-flex align-items-center"><img class="jsp-avatar"
                                        src="https://images.unsplash.com/photo-1531123897727-8f129e1688ce?w=160&h=160&fit=crop"
                                        alt="Olivia Brown">
                                    <div class="ms-3">
                                        <h3 class="jsp-name">Olivia Brown</h3>
                                        <p class="jsp-trip">Kathmandu Valley, Nepal</p>
                                    </div><i class="bi bi-patch-check-fill jsp-verified"></i>
                                </div>
                            </article>
                        </div>
                        <div class="col-lg-4">
                            <article class="jsp-card"><i class="bi bi-quote jsp-quote-icon"></i>
                                <div class="jsp-stars">★★★★★</div>
                                <p class="jsp-review">“The sunrise over the Himalayas was breathtaking. The entire trip
                                    was safe, comfortable and planned with genuine care.”</p>
                                <div class="d-flex align-items-center"><img class="jsp-avatar"
                                        src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=160&h=160&fit=crop"
                                        alt="James Lee">
                                    <div class="ms-3">
                                        <h3 class="jsp-name">James Lee</h3>
                                        <p class="jsp-trip">Nagarkot Sunrise, Nepal</p>
                                    </div><i class="bi bi-patch-check-fill jsp-verified"></i>
                                </div>
                            </article>
                        </div>
                        <div class="col-lg-4">
                            <article class="jsp-card"><i class="bi bi-quote jsp-quote-icon"></i>
                                <div class="jsp-stars">★★★★★</div>
                                <p class="jsp-review">“From booking to the final goodbye, communication was excellent.
                                    This team turned our family holiday into a lifelong memory.”</p>
                                <div class="d-flex align-items-center"><img class="jsp-avatar"
                                        src="https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=160&h=160&fit=crop"
                                        alt="Mia Anderson">
                                    <div class="ms-3">
                                        <h3 class="jsp-name">Mia Anderson</h3>
                                        <p class="jsp-trip">Chitwan Safari, Nepal</p>
                                    </div><i class="bi bi-patch-check-fill jsp-verified"></i>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-5">
                <div class="jsp-dots"><button class="jsp-dot jsp-active"
                        aria-label="Testimonials page 1"></button><button class="jsp-dot"
                        aria-label="Testimonials page 2"></button></div>
                <div class="d-flex gap-2"><button class="jsp-control" id="jspPrev" aria-label="Previous testimonials"><i
                            class="bi bi-arrow-left"></i></button><button class="jsp-control" id="jspNext"
                        aria-label="Next testimonials"><i class="bi bi-arrow-right"></i></button></div>
            </div>
        </div>
    </section>

    <section>

     @include('frontend.layouts.footer')