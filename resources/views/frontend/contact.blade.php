<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JSP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- Navbar -->
    <nav class="main-navbar" id="mainNavbar">
        <div class="navbar-inner">

            <!-- Logo -->
            <a href="{{ route('home') }}" class="nav-logo" aria-label="JSP Travel Home">
                <img src="uploads/ChatGPT Image Sep 2, 2026, 03_55_54 PM.png" alt="JSP Travel Logo">
            </a>

            <!-- Desktop Navigation -->
            <ul class="nav-links">
                <li><a href="{{ route('home') }}">Home</a></li>
                <li><a href="{{ route('about') }}">About</a></li>
                <li><a href="{{ route('destinations.index') }}">Tours</a></li>
                <li><a href="{{ route('hotel') }}">Hotels</a></li>
                <li><a href="{{ route('transport') }}">Car Rental</a></li>
                <li><a href="{{ route('contact.index') }}">Contact</a></li>
            </ul>

            <!-- Desktop Button -->
            <a href="{{ route('booking.search') }}" class="nav-cta desktop">
                <span>Book Now</span>
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>

            <!-- Mobile Toggle -->
            <button type="button" class="nav-toggle" id="navToggle" aria-label="Toggle navigation menu"
                aria-controls="mobileNav" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>

    <!-- Mobile Navigation -->
    <div class="mobile-nav" id="mobileNav">
        <a href="{{ route('home') }}" onclick="closeMobileNav()">Home</a>
        <a href="{{ route('about') }}" onclick="closeMobileNav()">About</a>
        <a href="{{ route('destinations.index') }}" onclick="closeMobileNav()">Tours</a>
        <a href="{{ route('hotel') }}" onclick="closeMobileNav()">Hotels</a>
        <a href="{{ route('transport') }}" onclick="closeMobileNav()">Car Rental</a>
        <a href="{{ route('contact.index') }}" onclick="closeMobileNav()">Contact</a>

        <a href="{{ route('booking.search') }}" class="nav-cta" onclick="closeMobileNav()">
            <span>Book Now</span>
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </a>
    </div>

    <!-- ========== CONTACT SECTION ========== -->
    <section class="contacts reach-contact">
        <div class="container">
            <!-- Top Header -->
            <div class="reach-topbar">
                <div>
                    <span class="reach-label">Plan Trip</span>
                    <h2 class="reach-heading">Contact Us</h2>
                </div>
                <p class="reach-subtext">Tell us when and where you'd like to go and we'll confirm availability within
                    24 hours.</p>
            </div>

            <!-- Form + Image Row -->
            <div class="row g-4">
                <!-- Form Column -->
                <div class="col-lg-7">
                    <div class="reach-form-wrap">
                        <form id="reach-form" onsubmit="event.preventDefault();">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="reach-field">
                                        <label for="reach-name">Name</label>
                                        <input type="text" id="reach-name" class="reach-input"
                                            placeholder="Your full name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="reach-field">
                                        <label for="reach-email">Email</label>
                                        <input type="email" id="reach-email" class="reach-input"
                                            placeholder="you@example.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="reach-field">
                                        <label for="reach-phone">Phone Number</label>
                                        <input type="tel" id="reach-phone" class="reach-input"
                                            placeholder="+966 55 123 4567">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="reach-field">
                                        <label for="reach-tour">Select Your Tour</label>
                                        <select id="reach-tour" class="reach-select">
                                            <option value="" disabled selected>Choose your tour...</option>
                                            <option value="dolomites">Dolomites Adventure</option>
                                            <option value="greek">Greek Islands</option>
                                            <option value="thailand">Thailand Explorer</option>
                                            <option value="swiss">Swiss Alps</option>
                                            <option value="japan">Japan Discovery</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="reach-field">
                                        <label for="reach-date">Preferred Date</label>
                                        <input type="text" id="reach-date" class="reach-input" placeholder="dd/mm/yyyy"
                                            onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="reach-field">
                                        <label for="reach-travelers">Number of Travelers</label>
                                        <input type="text" id="reach-travelers" class="reach-input"
                                            placeholder="2 adults, 1 child">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="reach-field">
                                        <label for="reach-message">Message / Special Requests</label>
                                        <textarea id="reach-message" class="reach-textarea"
                                            placeholder="Anything else we should know?"></textarea>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="reach-btn">
                                Reserve Your Spot
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Image Column -->
                <div class="col-lg-5">
                    <div class="reach-visual">
                        <span class="reach-badge">Your Journey</span>
                        <img src="https://www.dntt.com.np/uploads/ecategory/86862100.jpg"
                            alt="Desert road journey">
                    </div>
                </div>
            </div>

            <!-- Info Cards -->
            <div class="row reach-infobar">
                <div class="col-md-4">
                    <div class="reach-infoitem">
                        <div class="reach-infoicon">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <h6 class="reach-infotitle">Call & WhatsApp</h6>
                        <p class="reach-infodetail">+977 9822-773259<br>+977 9868-442393</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="reach-infoitem">
                        <div class="reach-infoicon">
                            <i class="fa-regular fa-clock"></i>
                        </div>
                        <h6 class="reach-infotitle">Working Hours</h6>
                        <p class="reach-infodetail">24/7<br>services</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="reach-infoitem">
                        <div class="reach-infoicon">
                            <i class="fa-regular fa-envelope"></i>
                        </div>
                        <h6 class="reach-infotitle">Write to Us</h6>
                        <p class="reach-infodetail">Jsptravel291@gmail.com<br></p>
                    </div>
                </div>
            </div>

            <!-- Bottom Promo -->
            <div class="reach-promo">
                <div class="reach-promo-inner">
                    <div class="reach-promo-text">
                        <span class="reach-promo-label">Start now</span>
                        <h3 class="reach-promo-heading">
                            <i class="fa-solid fa-arrow-up-right-from-square"
                                style="font-size:0.7em; margin-right:6px;"></i>
                            Discover your next <a href="{{ route('destinations.index') }}">Visit site</a> escape
                        </h3>
                        <p class="reach-promo-desc">Plan your trip in seconds and enjoy every moment of your adventure.
                        </p>
                    </div>
                    <div class="reach-promo-imgs">
                        <div class="reach-promo-img">
                            <img src="https://english.onlinekhabar.com/wp-content/uploads/2016/08/A-view-from-the-Badimalika-Temple-Region-Bajura-District-of-Far-western-Nepal..jpg"
                                alt="Desert landscape">
                        </div>
                        <div class="reach-promo-img">
                            <img src="https://english.onlinekhabar.com/wp-content/uploads/2020/10/Ghodaghodi_Lake_Kailali.jpg"
                                alt="Rock formations">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section>

        <!-- ===== FOOTER ===== -->
        <footer id="jsp-footer-main">

            <!-- Top Section -->
            <div id="jsp-footer-top">
                <div class="container">
                    <div class="row g-4">

                        <!-- Column 1: Brand & About -->
                        <div class="col-lg-4 col-md-6">
                            <div class="mb-3">
                                <span class="fs-2">🚗</span>
                            </div>
                            <h4 id="jsp-footer-brand-text" class="text-white mb-1">
                                <span class="text-danger">JSP</span> TRAVEL
                            </h4>
                            <p id="jsp-footer-slogan" class="mb-3">"Wonder freely, Travel safely With JSP"</p>

                            <p class="text-secondary" style="font-size: 0.9rem; line-height: 1.7;">
                                Jay Shiv Parvati Travel & Tour is your trusted travel partner in Attariya, Kailali.
                                We provide 24/7 travel services including vehicle rentals, hotel bookings,
                                bus tickets, and airport transfers.
                            </p>

                            <div class="mt-3">
                                <a href="#" class="jsp-social-btn" title="Facebook"><i class="bi bi-facebook"></i></a>
                                <a href="#" class="jsp-social-btn" title="Instagram"><i class="bi bi-instagram"></i></a>
                                <a href="#" class="jsp-social-btn" title="YouTube"><i class="bi bi-youtube"></i></a>
                                <a href="#" class="jsp-social-btn" title="Twitter"><i class="bi bi-twitter-x"></i></a>
                            </div>

                            <div class="jsp-24-badge-footer">
                                <i class="bi bi-clock-fill"></i>
                                <span>24/7 SERVICE AVAILABLE</span>
                            </div>
                        </div>

                        <!-- Column 2: Quick Links -->
                        <div class="col-lg-2 col-md-6">
                            <h5 class="jsp-footer-heading">Quick Links</h5>
                            <ul class="list-unstyled">
                                <li><a href="{{ route('home') }}" class="jsp-footer-link" id="jsp-ftr-link-home"><i
                                            class="bi bi-chevron-right"></i> Home</a></li>
                                <li><a href="{{ route('about') }}" class="jsp-footer-link" id="jsp-ftr-link-about"><i
                                            class="bi bi-chevron-right"></i> About Us</a></li>
                                <li><a href="{{ route('destinations.index') }}" class="jsp-footer-link" id="jsp-ftr-link-services"><i
                                            class="bi bi-chevron-right"></i> Our Services</a></li>
                                <li><a href="{{ route('transport') }}" class="jsp-footer-link" id="jsp-ftr-link-vehicles"><i
                                            class="bi bi-chevron-right"></i> Vehicles</a></li>
                                <li><a href="#" class="jsp-footer-link" id="jsp-ftr-link-gallery"><i
                                            class="bi bi-chevron-right"></i> Gallery</a></li>
                                <li><a href="{{ route('contact.index') }}" class="jsp-footer-link" id="jsp-ftr-link-contact"><i
                                            class="bi bi-chevron-right"></i> Contact Us</a></li>
                            </ul>
                        </div>

                        <!-- Column 3: Our Services -->
                        <div class="col-lg-3 col-md-6">
                            <h5 class="jsp-footer-heading">Our Services</h5>
                            <div>
                                <a href="{{ route('tours.index') }}" class="jsp-service-tag" id="jsp-ftr-srv-package">📦 Package Tour</a>
                                <a href="{{ route('hotel') }}" class="jsp-service-tag" id="jsp-ftr-srv-hotel">🏨 Hotel Booking</a>
                                <a href="{{ route('booking.search') }}" class="jsp-service-tag" id="jsp-ftr-srv-bus">🚌 Bus Ticket</a>
                                <a href="{{ route('transport') }}" class="jsp-service-tag" id="jsp-ftr-srv-vehicle">🚙 Vehicle Rent</a>
                                <a href="{{ route('transport') }}" class="jsp-service-tag" id="jsp-ftr-srv-courier">✈️ Airport Pickup</a>
                                <a href="{{ route('transport') }}" class="jsp-service-tag" id="jsp-ftr-srv-wedding">💒 Wedding Car</a>
                                {{-- There is no flight model, table or booking type (Booking::TYPES is
                                     tour/hotel/vehicle), so this tag must not promise an air-ticket flow.
                                     Send the customer to the booking form, matching layouts/footer.blade.php. --}}
                                <a href="{{ route('booking.search') }}" class="jsp-service-tag" id="jsp-ftr-srv-airticket">🎫 Air Ticket</a>
                            </div>

                            <h5 class="jsp-footer-heading mt-4">Location</h5>
                            <div class="jsp-contact-row">
                                <div class="jsp-contact-icon"><i class="bi bi-geo-alt-fill"></i></div>
                                <div>
                                    <span class="jsp-contact-label">Address</span>
                                    <span id="jsp-footer-location">Attariya-01, Kailali, Dhangadhi Road</span>
                                </div>
                            </div>
                        </div>

                        <!-- Column 4: Contact Info -->
                        <div class="col-lg-3 col-md-6">
                            <h5 class="jsp-footer-heading">Contact Us</h5>

                            <!-- Manager -->
                            <div class="jsp-contact-row" id="jsp-ftr-contact-manager">
                                <div class="jsp-contact-icon"><i class="bi bi-person-fill"></i></div>
                                <div>
                                    <span class="jsp-contact-label">Manager</span>
                                    <span class="jsp-contact-value">Keshav Bhatta</span><br>
                                    <a href="tel:9822773259" class="jsp-contact-value"><i
                                            class="bi bi-telephone-fill me-1"
                                            style="font-size: 0.7rem;"></i>9822-773259</a>
                                </div>
                            </div>

                            <!-- Managing Director -->
                            <div class="jsp-contact-row" id="jsp-ftr-contact-director">
                                <div class="jsp-contact-icon"><i class="bi bi-person-badge-fill"></i></div>
                                <div>
                                    <span class="jsp-contact-label">Managing Director</span>
                                    <span class="jsp-contact-value">Renu Bhatta</span><br>
                                    <a href="tel:9868442393" class="jsp-contact-value"><i
                                            class="bi bi-telephone-fill me-1"
                                            style="font-size: 0.7rem;"></i>9868-442393</a>
                                </div>
                            </div>

                            <!-- Landline -->
                            <div class="jsp-contact-row" id="jsp-ftr-contact-landline">
                                <div class="jsp-contact-icon"><i class="bi bi-telephone-fill"></i></div>
                                <div>
                                    <span class="jsp-contact-label">Landline</span>
                                    <a href="tel:091550614" class="jsp-contact-value">091-550614</a>
                                </div>
                            </div>

                            <!-- WhatsApp -->
                            <div class="jsp-contact-row" id="jsp-ftr-contact-whatsapp">
                                <div class="jsp-contact-icon"><i class="bi bi-whatsapp"></i></div>
                                <div>
                                    <span class="jsp-contact-label">WhatsApp</span>
                                    <a href="https://wa.me/9779866521122" class="jsp-contact-value">9866-521122</a>
                                </div>
                            </div>

                            <!-- Viber -->
                            <div class="jsp-contact-row" id="jsp-ftr-contact-viber">
                                <div class="jsp-contact-icon"><i class="bi bi-phone-fill"></i></div>
                                <div>
                                    <span class="jsp-contact-label">Viber</span>
                                    <a href="tel:9858483291" class="jsp-contact-value">9858-483291</a>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="jsp-contact-row" id="jsp-ftr-contact-email">
                                <div class="jsp-contact-icon"><i class="bi bi-envelope-fill"></i></div>
                                <div>
                                    <span class="jsp-contact-label">Email</span>
                                    <a href="mailto:Jsptravel291@gmail.com"
                                        class="jsp-contact-value">Jsptravel291@gmail.com</a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Bottom Section -->
            <div id="jsp-footer-bottom">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                            <p class="jsp-copyright-text mb-0">
                                &copy; 2026 <a href="#" class="jsp-copyright-link">Jay Shiv Parvati Travel & Tour</a>.
                                All Rights Reserved.
                            </p>
                        </div>
                        <div class="col-md-6 text-center text-md-end">
                            <p class="jsp-copyright-text mb-0">
                                <i class="bi bi-heart-fill text-danger" style="font-size: 0.7rem;"></i>
                                Designed with care for travelers
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </footer>

        <!-- WhatsApp Floating Button -->
        <a href="https://wa.me/9779866521122" class="jsp-whatsapp-float" title="Chat on WhatsApp"
            id="jsp-ftr-whatsapp-float">
            <i class="bi bi-whatsapp"></i>
        </a>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>

</html>