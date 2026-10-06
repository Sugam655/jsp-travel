@include('frontend.layouts.header');

@include('frontend.layouts.mobile-nav')

    <section class="about bg-white">


        <div class="trvl-breadcrumb-wrap" style="
        background-image:
        linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)),
        url('https://booking-manager-api-hop-nepal.s3.eu-west-1.amazonaws.com/file-manager/page/image-darchula-village.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
     ">

            <div class="container py-5">
                <div class="row align-items-center">

                    <!-- Page Title -->
                    <div class="col-md-6 text-center text-md-start">
                        <h1 class="trvl-page-title text-white mb-0">
                            About Us
                        </h1>
                    </div>

                    <!-- Breadcrumb -->
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

                            <!-- <li class="text-white">
                                /
                            </li> -->

                            <li class="active text-white-50">
                                About Us
                            </li>

                        </ul>
                    </div>

                </div>
            </div>

        </div>

        <section class="trvl-section trvl-about-main bg-white">
            <div class="trvl-deco-img-wrap">
                <img src="https://english.onlinekhabar.com/wp-content/uploads/2020/11/Shuklaphanta-1-1024x557.jpg" alt="Travel Beach">
            </div>
            <div class="container position-relative" style="z-index: 2;">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="trvl-img-card-wrap">
                            <img src="https://www.dntt.com.np/uploads/ecategory/00252100.jpg"
                                alt="Travel Adventure" class="trvl-main-img">
                            <div class="trvl-quote-card">
                                <p>"Life is either a daring adventure or nothing. Travel far, travel wide, and let your
                                    soul
                                    be your guide."</p>
                                <div class="trvl-quote-author">
                                    <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&q=80"
                                        alt="Sarah Chen">
                                    <div class="trvl-quote-author-info">
                                        <h6>Sarah Chen</h6>
                                        <span>Travel Blogger</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="trvl-content-box">
                            <span class="trvl-label ">Who We Are</span>
                            <h2 class="trvl-heading">Travel is about discovery, not just destinations</h2>
                            <p class="trvl-desc">We believe that every journey tells a story. Our mission is to craft
                                unforgettable travel experiences that go beyond the ordinary. From hidden gems in
                                bustling
                                cities to serene escapes in nature's untouched corners, we curate adventures that
                                resonate
                                with your soul.</p>
                            <p class="trvl-desc">With years of expertise in personalized travel planning, we ensure
                                every
                                detail is tailored to your dreams. Whether you seek thrilling expeditions or peaceful
                                retreats, our dedicated team transforms your wanderlust into reality.</p>
                            <div class="trvl-author-box">
                                <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&q=80"
                                    alt="James Mitchell">
                                <div class="trvl-author-info">
                                    <h5>Keshav Bhatta</h5>
                                    <p>Founder & CEO, Wanderlust Travels</p>
                                </div>
                            </div>
                            <a href="{{ route('destinations.index') }}" class="trvl">Explore More</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="trvl-stats-row">
            <div class="container">
                <div class="row">

                    <div class="col-6 col-md-3">
                        <div class="trvl-stat-item">
                            <div class="trvl-stat-num" data-target="1400" data-suffix="+">
                                0
                            </div>
                            <div class="trvl-stat-label">Happy Travelers</div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="trvl-stat-item">
                            <div class="trvl-stat-num" data-target="720" data-suffix="+">
                                0
                            </div>
                            <div class="trvl-stat-label">Destinations</div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="trvl-stat-item">
                            <div class="trvl-stat-num" data-target="899" data-suffix="+">
                                0
                            </div>
                            <div class="trvl-stat-label">Expert Guides</div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="trvl-stat-item">
                            <div class="trvl-stat-num" data-target="15" data-suffix="+">
                                0
                            </div>
                            <div class="trvl-stat-label">Years Experience</div>
                        </div>
                    </div>

                </div>
            </div>
        </section>
        <section class="trvl-banner-wrap">
            <div class="trvl-banner-overlay"></div>
            <div class="trvl-banner-content">
                <span class="trvl-banner-label">Our Services</span>
                <h2 class="trvl-banner-heading">Plan your journey with our best-personalized itineraries</h2>
                <p class="trvl-banner-desc">Let us handle the details while you focus on making memories. Our custom
                    travel
                    plans are designed around your preferences, budget, and sense of adventure.</p>
                        <a href="{{ route('destinations.index') }}" class="trvl-btn-outline">Explore More</a>
            </div>
        </section>
        <section class="trvl-features-wrap">
            <div class="container">
                <div class="row g-4 pt-5" id="servicesContainer">

                    <!-- Service 1 -->
                    <div class="col-md-4 service-item">
                        <div class="trvl-feature-card">
                            <div class="trvl-feature-icon">
                                <i class="bi bi-map"></i>
                            </div>

                            <div class="trvl-feature-info">
                                <h4>Package Tour</h4>
                                <p>
                                    Explore amazing destinations with our carefully planned
                                    and affordable package tours.
                                </p>
                            </div>
                        </div>
                    </div>


                    <!-- Service 2 -->
                    <div class="col-md-4 service-item">
                        <div class="trvl-feature-card">
                            <div class="trvl-feature-icon">
                                <i class="bi bi-building"></i>
                            </div>

                            <div class="trvl-feature-info">
                                <h4>Hotel Booking</h4>
                                <p>
                                    Comfortable and convenient hotel booking services
                                    for your business and holiday trips.
                                </p>
                            </div>
                        </div>
                    </div>


                    <!-- Service 3 -->
                    <div class="col-md-4 service-item">
                        <div class="trvl-feature-card">
                            <div class="trvl-feature-icon">
                                <i class="bi bi-bus-front"></i>
                            </div>

                            <div class="trvl-feature-info">
                                <h4>Bus Ticket Booking</h4>
                                <p>
                                    Book bus tickets easily and travel safely to your
                                    preferred destination.
                                </p>
                            </div>
                        </div>
                    </div>


                    <!-- Service 4 -->
                    <div class="col-md-4 service-item more-service">
                        <div class="trvl-feature-card">
                            <div class="trvl-feature-icon">
                                <i class="bi bi-car-front"></i>
                            </div>

                            <div class="trvl-feature-info">
                                <h4>All Type Vehicle Rent</h4>
                                <p>
                                    Rent cars, buses and other vehicles for personal,
                                    family, business and group travel.
                                </p>
                            </div>
                        </div>
                    </div>


                    <!-- Service 5 -->
                    <div class="col-md-4 service-item more-service">
                        <div class="trvl-feature-card">
                            <div class="trvl-feature-icon">
                                <i class="bi bi-airplane"></i>
                            </div>

                            <div class="trvl-feature-info">
                                <h4>Airport Pickup & Drop</h4>
                                <p>
                                    Reliable airport pickup and drop services for
                                    a smooth and comfortable journey.
                                </p>
                            </div>
                        </div>
                    </div>


                    <!-- Service 6 -->
                    <div class="col-md-4 service-item more-service">
                        <div class="trvl-feature-card">
                            <div class="trvl-feature-icon">
                                <i class="bi bi-stars"></i>
                            </div>

                            <div class="trvl-feature-info">
                                <h4>Luxury Wedding Car</h4>
                                <p>
                                    Make your special day memorable with our luxury
                                    cars and sunroof wedding vehicles.
                                </p>
                            </div>
                        </div>
                    </div>


                    <!-- Service 7 -->
                    <div class="col-md-4 service-item more-service">
                        <div class="trvl-feature-card">
                            <div class="trvl-feature-icon">
                                <i class="bi bi-ticket-perforated"></i>
                            </div>

                            <div class="trvl-feature-info">
                                <h4>Air Ticket Booking</h4>
                                <p>
                                    National and international air ticket booking
                                    services at convenient prices.
                                </p>
                            </div>
                        </div>
                    </div>


                    <!-- Service 8 -->
                    <div class="col-md-4 service-item more-service">
                        <div class="trvl-feature-card">
                            <div class="trvl-feature-icon">
                                <i class="bi bi-box-seam"></i>
                            </div>

                            <div class="trvl-feature-info">
                                <h4>Courier Service</h4>
                                <p>
                                    Fast and convenient courier services for sending
                                    packages and important items.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>


                <!-- See More Button -->
                <div class="text-center mt-5">
                    <button type="button" class="trvl-see-more-btn" id="seeMoreServices">
                        See More Services
                        <i class="bi bi-arrow-down"></i>
                    </button>
                </div>
            </div>
        </section>
        <section class="trvl-team-wrap">
            <div class="container">
                <div class="trvl-section-header">
                    <span class="trvl-section-label">Meet Our Team</span>
                    <h2 class="trvl-section-title">We serve uniqueness because you are unique to us</h2>
                </div>
                <div class="row g-4">
                    <div class="col-6 col-lg-3">
                        <div class="trvl-team-card">
                            <div class="trvl-team-img-wrap">
                                <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=400&q=80"
                                    alt="Erica Stewart" class="trvl-team-img">
                            </div>
                            <h4 class="trvl-team-name">Erica Stewart</h4>
                            <p class="trvl-team-role">Travel Consultant</p>
                            <!-- <div class="trvl-team-social">
                                <a href="#"><i class="bi bi-facebook"></i></a>
                                <a href="#"><i class="bi bi-twitter-x"></i></a>
                                <a href="#"><i class="bi bi-instagram"></i></a>
                                <a href="#"><i class="bi bi-linkedin"></i></a>
                            </div> -->
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="trvl-team-card">
                            <div class="trvl-team-img-wrap">
                                <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?w=400&q=80"
                                    alt="Melissa Studer" class="trvl-team-img">
                            </div>
                            <h4 class="trvl-team-name">Melissa Studer</h4>
                            <p class="trvl-team-role">Tour Coordinator</p>
                            <!-- <div class="trvl-team-social">
                                <a href="#"><i class="bi bi-facebook"></i></a>
                                <a href="#"><i class="bi bi-twitter-x"></i></a>
                                <a href="#"><i class="bi bi-instagram"></i></a>
                                <a href="#"><i class="bi bi-linkedin"></i></a>
                            </div> -->
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="trvl-team-card">
                            <div class="trvl-team-img-wrap">
                                <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=400&q=80"
                                    alt="Claudia Stephens" class="trvl-team-img">
                            </div>
                            <h4 class="trvl-team-name">Claudia Stephens</h4>
                            <p class="trvl-team-role">Destination Expert</p>
                            <!-- <div class="trvl-team-social">
                                <a href="#"><i class="bi bi-facebook"></i></a>
                                <a href="#"><i class="bi bi-twitter-x"></i></a>
                                <a href="#"><i class="bi bi-instagram"></i></a>
                                <a href="#"><i class="bi bi-linkedin"></i></a>
                            </div> -->
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="trvl-team-card">
                            <div class="trvl-team-img-wrap">
                                <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&q=80"
                                    alt="Hector Furman" class="trvl-team-img">
                            </div>
                            <h4 class="trvl-team-name">Hector Furman</h4>
                            <p class="trvl-team-role">Adventure Specialist</p>
                            <!-- <div class="trvl-team-social">
                                <a href="#"><i class="bi bi-facebook"></i></a>
                                <a href="#"><i class="bi bi-twitter-x"></i></a>
                                <a href="#"><i class="bi bi-instagram"></i></a>
                                <a href="#"><i class="bi bi-linkedin"></i></a>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </section>

    <section>
    @include('frontend.layouts.footer');