@include('frontend.layouts.header')

@include('frontend.layouts.mobile-nav')

    <!-- Hero Section -->
    <section class="nest-hero hotel" id="nestHero">
        <div class="nest-hero-bg">
            <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1920&q=80" alt="Luxury Hotel">
        </div>
        <div class="nest-hero-wrap">
            <div class="container">
                <div class="row">
                    <div class="col-lg-7">
                        <div class="nest-hero-label">JSP Travel</div>
                        <h1 class="nest-hero-title">FIND YOUR<br>PERFECT<br><span>STAY</span></h1>
                        <p class="nest-hero-desc">Discover handpicked hotels and resorts across Nepal, from budget
                            comfort to premium luxury, tailored to your journey.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Search Bar -->
    <div class="nest-searchbar">
        <div class="container">
            <div class="nest-searchbox">
                <div class="row g-4 align-items-end">
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="nest-searchgroup">
                            <i class="bi bi-geo-alt"></i>
                            <label class="nest-searchlabel">Location</label>
                            <select class="nest-searchfield" id="nestFilterLoc">
                                <option value="">Select Location</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location }}">{{ $location }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="nest-searchgroup">
                            <i class="bi bi-star"></i>
                            <label class="nest-searchlabel">Star Rating</label>
                            <select class="nest-searchfield" id="nestFilterType">
                                <option value="">Any Rating</option>
                                <option value="5">5 Star</option>
                                <option value="4">4 Star</option>
                                <option value="3">3 Star</option>
                                <option value="2">2 Star</option>
                                <option value="1">1 Star</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="nest-searchgroup">
                            <i class="bi bi-currency-rupee"></i>
                            <label class="nest-searchlabel">Price Range</label>
                            <select class="nest-searchfield" id="nestFilterPrice">
                                <option value="">Any Price</option>
                                @foreach ($priceOptions as $option)
                                    <option value="{{ $option }}">Under Rs {{ number_format($option) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <button class="nest-searchbtn" id="nestSearchBtn"><i class="bi bi-search"></i> Search</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Hotels -->
    <section class="nest-properties" id="nestProperties">
        <div class="container">
            <div class="nest-sectionhead nest-fadeup">
                <span class="nest-sectionlabel">Handpicked For You</span>
                <h2 class="nest-sectiontitle">FEATURED HOTELS</h2>
            </div>

            <div class="row g-4" id="nestPropertyGrid">
                @forelse ($hotels as $hotel)
                    @include('frontend.partials.hotel-card', ['hotel' => $hotel])
                @empty
                    <div class="col-12 text-center text-muted py-4">
                        Hotels are coming soon.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="nest-whyus" id="nestWhyUs">
        <div class="container">
            <div class="nest-sectionhead nest-fadeup">
                <span class="nest-sectionlabel">Our Advantages</span>
                <h2 class="nest-sectiontitle">WHY CHOOSE US</h2>
            </div>

            <div class="row g-4 nest-whygrid">
                <div class="col-6 col-lg-3 nest-fadeup">
                    <div class="nest-whybox">
                        <div class="nest-whyicon"><i class="bi bi-house-heart"></i></div>
                        <h4 class="nest-whytitle">Curated Stays</h4>
                        <p class="nest-whydesc">Handpicked hotels and resorts verified for quality and comfort.</p>
                    </div>
                </div>
                <div class="col-6 col-lg-3 nest-fadeup">
                    <div class="nest-whybox">
                        <div class="nest-whyicon"><i class="bi bi-hand-thumbs-up"></i></div>
                        <h4 class="nest-whytitle">Best Price Promise</h4>
                        <p class="nest-whydesc">Honest, transparent nightly rates at every featured property.</p>
                    </div>
                </div>
                <div class="col-6 col-lg-3 nest-fadeup">
                    <div class="nest-whybox">
                        <div class="nest-whyicon"><i class="bi bi-geo-alt"></i></div>
                        <h4 class="nest-whytitle">Local Expertise</h4>
                        <p class="nest-whydesc">Real local knowledge to match you with the perfect stay.</p>
                    </div>
                </div>
                <div class="col-6 col-lg-3 nest-fadeup">
                    <div class="nest-whybox">
                        <div class="nest-whyicon"><i class="bi bi-shield-check"></i></div>
                        <h4 class="nest-whytitle">Secure Booking</h4>
                        <p class="nest-whydesc">Clear and secure booking handled by our travel team.</p>
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