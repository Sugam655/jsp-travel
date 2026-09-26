@include('frontend.layouts.header')


@include('frontend.layouts.mobile-nav')

    <!-- Hero Section -->
    <section class="amc-hero transport" id="amcHero">
        <div class="amc-hero-wrap">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="amc-hero-label">JSP Travel. Dependable Fleet. Exceptional Service.</div>
                        <h1 class="amc-hero-title">DRIVE YOUR <em>DREAM</em> TODAY</h1>
                        <p class="amc-hero-desc">Explore our dependable fleet of cars, jeeps, vans, buses and bikes and
                            find the perfect vehicle for your journey across Nepal.</p>
                        <div class="amc-hero-actions">
                            <a href="#amcInventory" class="amc-btn-primary"><i class="bi bi-grid"></i>
                                Inventory</a>
                            <a href="#amcFeatures" class="amc-btn-outline"><i class="bi bi-hand-thumbs-up"></i> Why
                                Choose Us</a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="amc-hero-car">
                            <img src="uploads/splashpage-removebg-preview.png" alt="Premium SUV">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="amc-searchbar">
            <div class="container">
                <div class="amc-searchbox">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 mb-2">
                            <span class="text-black fw-bold" style="font-size: 0.9rem;"><i
                                    class="bi bi-search me-2 text-black"></i>FIND YOUR NEXT VEHICLE</span>
                        </div>
                        <div class="col-6 col-lg">
                            <label class="amc-search-label">Brand</label>
                            <select class="amc-searchfield" id="amcFilterMake">
                                <option value="">Any Brand</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand }}">{{ $brand }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-lg">
                            <label class="amc-search-label">Model</label>
                            <select class="amc-searchfield" id="amcFilterModel">
                                <option value="">Any Model</option>
                                @foreach ($models as $model)
                                    <option value="{{ $model }}">{{ $model }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-lg">
                            <label class="amc-search-label">Vehicle Type</label>
                            <select class="amc-searchfield" id="amcFilterBody">
                                <option value="">Any Type</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type }}">{{ $typeLabels[$type] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-lg">
                            <label class="amc-search-label">Max Price</label>
                            <select class="amc-searchfield" id="amcFilterPrice">
                                <option value="">Any Price</option>
                                @foreach ($priceOptions as $option)
                                    <option value="{{ $option }}">Under Rs.{{ number_format($option, 0, '.', ',') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-lg-auto">
                            <button class="amc-searchbtn" id="amcSearchBtn"><i
                                    class="bi bi-search me-2"></i>SEARCH</button>
                        </div>
                        <div class="col-12 text-end">
                            <a href="#amcInventory" class="amc-advanced">Advanced Search <i
                                    class="bi bi-chevron-down"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Rent Vehicles Section -->
    <section class="amc-inventory" id="amcInventory">
        <div class="container">
            <div class="amc-sectionhead amc-fadeup">
                <h2 class="amc-sectiontitle text-black">RENT VEHICLES</h2>
                <ul class="amc-filterlist">
                    <li><button class="active" data-amc-filter="all">All Vehicles</button></li>
                    @foreach ($types as $type)
                        <li><button data-amc-filter="{{ $type }}">{{ $typeLabels[$type] }}s</button></li>
                    @endforeach
                </ul>
            </div>

            <div class="row g-4 amc-vehiclegrid" id="amcVehicleGrid">
                @forelse ($vehicles as $vehicle)
                    @include('frontend.partials.vehicle-card', ['vehicle' => $vehicle])
                @empty
                    <div class="col-12 text-center text-muted py-4">
                        Vehicles are coming soon.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Featured Vehicles Section -->
    <section class="amc-inventory" id="amcFeaturedVehicles">
        <div class="container">
            <div class="amc-sectionhead amc-fadeup">
                <h2 class="amc-sectiontitle text-black">FEATURED VEHICLES</h2>
            </div>

            <div class="row g-4 amc-vehiclegrid" id="amcFeaturedGrid">
                @forelse ($featured as $vehicle)
                    @include('frontend.partials.vehicle-card', ['vehicle' => $vehicle])
                @empty
                    <div class="col-12 text-center text-muted py-4">
                        Featured vehicles will appear here once selected by our team.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="amc-features" id="amcFeatures">
        <div class="container">

            <!-- Section Title -->
            <div class="amc-sectionheading text-center amc-fadeup">
                <span class="amc-sectionsubtitle">WHY CHOOSE US</span>
                <h2 class="amc-sectiontitle">A Better Vehicle Rental Experience</h2>
                <p class="amc-sectiondescription">
                    Quality vehicles, fair prices, and expert support—all in one place.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-6 col-lg-3 amc-fadeup">
                    <div class="amc-featurebox">
                        <div class="amc-featureicon">
                            <i class="bi bi-shield-check"></i>
                        </div>

                        <h4 class="amc-featuretitle">Quality You Can Trust</h4>

                        <p class="amc-featuredesc">
                            Every vehicle is inspected, insured and road-ready before joining the fleet.
                        </p>
                    </div>
                </div>

                <div class="col-6 col-lg-3 amc-fadeup">
                    <div class="amc-featurebox">
                        <div class="amc-featureicon">
                            <i class="bi bi-tag-fill"></i>
                        </div>

                        <h4 class="amc-featuretitle">Best Price Guarantee</h4>

                        <p class="amc-featuredesc">
                            Honest, transparent daily, trip and hourly rates with no hidden charges.
                        </p>
                    </div>
                </div>

                <div class="col-6 col-lg-3 amc-fadeup">
                    <div class="amc-featurebox">
                        <div class="amc-featureicon">
                            <i class="bi bi-percent"></i>
                        </div>

                        <h4 class="amc-featuretitle">Flexible Rentals</h4>

                        <p class="amc-featuredesc">
                            Rent with or without a driver, for a few hours or several days at a time.
                        </p>
                    </div>
                </div>

                <div class="col-6 col-lg-3 amc-fadeup">
                    <div class="amc-featurebox">
                        <div class="amc-featureicon">
                            <i class="bi bi-headset"></i>
                        </div>

                        <h4 class="amc-featuretitle">Expert Support</h4>

                        <p class="amc-featuredesc">
                            Local driver-guides and a support team available around the clock.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Promo Banners -->
    <section class="amc-promos" id="amcPromos">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-6 amc-fadeup">
                    <div class="amc-promocard">
                        <img src="https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=800&q=80"
                            alt="Vehicle Interior">
                        <div class="amc-promocontent">
                            <h3 class="amc-promotitle">Your Journey, Your Vehicle</h3>
                            <p class="amc-promodesc">Rent a car, jeep, van or bike and explore the Far West exactly the
                                way you want to travel.</p>
                            <a href="#amcInventory" class="amc-promobtn">Browse The Fleet</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 amc-fadeup">
                    <div class="amc-promocard">
                        <img src="https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=800&q=80"
                            alt="SUV on the Road">
                        <div class="amc-promocontent">
                            <h3 class="amc-promotitle">Combine Rides With Tours & Stays</h3>
                            <p class="amc-promodesc">Pair your vehicle with our curated tours and handpicked hotels for
                                a completely seamless trip.</p>
                            <a href="{{ route('tours.index') }}" class="amc-promobtn">Explore Tours</a>
                        </div>
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