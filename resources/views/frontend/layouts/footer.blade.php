    <!-- ===== FOOTER ===== -->
        @php
            if (! isset($contactSummary) || ! is_array($contactSummary)) {
                $contactSummary = \Modules\Contact\Models\ContactSetting::DEFAULTS;
            }
        @endphp
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
                                @php
                                    $footerSocials = [
                                        'facebook_url' => ['bi-facebook', 'Facebook'],
                                        'instagram_url' => ['bi-instagram', 'Instagram'],
                                        'youtube_url' => ['bi-youtube', 'YouTube'],
                                        'twitter_url' => ['bi-twitter-x', 'Twitter'],
                                    ];
                                @endphp
                                @foreach ($footerSocials as $key => $meta)
                                    @if (filled($contactSummary[$key] ?? null))
                                        <a href="{{ $contactSummary[$key] }}" class="jsp-social-btn" target="_blank"
                                            rel="noopener" title="{{ $meta[1] }}"><i class="bi {{ $meta[0] }}"></i></a>
                                    @endif
                                @endforeach
                            </div>

                            <div class="jsp-24-badge-footer">
                                <i class="bi bi-clock-fill"></i>
                                <span>{{ strtoupper($contactSummary['opening_hours'] ?? '24/7 services') }}</span>
                            </div>
                        </div>

                        <!-- Column 2: Quick Links -->
                        <div class="col-lg-2 col-md-6">
                            <h5 class="jsp-footer-heading">Quick Links</h5>
                            <ul class="list-unstyled">
                                <li>
                                    <a href="{{ auth()->check() ? (auth()->user()->isAdmin() ? route('admin.dashboard') : route('user.dashboard')) : route('home') }}"
                                        class="jsp-footer-link" id="jsp-ftr-link-home"><i
                                            class="bi bi-chevron-right"></i> Dashboard</a>
                                </li>
                                <li><a href="{{ route('about') }}" class="jsp-footer-link" id="jsp-ftr-link-about"><i
                                            class="bi bi-chevron-right"></i> About Us</a></li>
                                <li><a href="{{ route('destinations.index') }}" class="jsp-footer-link"
                                        id="jsp-ftr-link-services"><i
                                            class="bi bi-chevron-right"></i> Our Services</a></li>
                                <li><a href="{{ route('transport') }}" class="jsp-footer-link"
                                        id="jsp-ftr-link-vehicles"><i
                                            class="bi bi-chevron-right"></i> Vehicles</a></li>
                                <li><a href="{{ route('hotel') }}" class="jsp-footer-link" id="jsp-ftr-link-gallery"><i
                                            class="bi bi-chevron-right"></i> Hotels</a></li>
                                <li><a href="{{ route('contact.index') }}" class="jsp-footer-link" id="jsp-ftr-link-contact"><i
                                            class="bi bi-chevron-right"></i> Contact Us</a></li>
                            </ul>
                        </div>

                        <!-- Column 3: Our Services -->
                        <div class="col-lg-3 col-md-6">
                            <h5 class="jsp-footer-heading">Our Services</h5>
                            <div>
                                <a href="{{ route('tours.index') }}" class="jsp-service-tag"
                                    id="jsp-ftr-srv-package">📦 Package Tour</a>
                                <a href="{{ route('hotel') }}" class="jsp-service-tag"
                                    id="jsp-ftr-srv-hotel">🏨 Hotel Booking</a>
                                <a href="{{ route('booking.search') }}" class="jsp-service-tag"
                                    id="jsp-ftr-srv-bus">🚌 Bus Ticket</a>
                                <a href="{{ route('transport') }}" class="jsp-service-tag"
                                    id="jsp-ftr-srv-vehicle">🚙 Vehicle Rent</a>
                                <a href="{{ route('transport') }}" class="jsp-service-tag"
                                    id="jsp-ftr-srv-courier">✈️ Airport Pickup</a>
                                {{-- The booking form only accepts a type + slug pair (BookingController::create),
                                     so a slug-less vehicle link 404s. Send the customer to the vehicle
                                     listing to pick a car, matching the other service tags above. --}}
                                <a href="{{ route('transport') }}" class="jsp-service-tag"
                                    id="jsp-ftr-srv-wedding">💒 Wedding Car</a>
                                <a href="{{ route('booking.search') }}" class="jsp-service-tag"
                                    id="jsp-ftr-srv-airticket">🎫 Air Ticket</a>
                            </div>

                            <h5 class="jsp-footer-heading mt-4">Location</h5>
                            <div class="jsp-contact-row">
                                <div class="jsp-contact-icon"><i class="bi bi-geo-alt-fill"></i></div>
                                <div>
                                    <span class="jsp-contact-label">Address</span>
                                    <span id="jsp-footer-location">{{ $contactSummary['address'] }}</span>
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
                                    <span class="jsp-contact-value">{{ $contactSummary['manager_name'] }}</span><br>
                                    <a href="tel:{{ $contactSummary['phone'] }}" class="jsp-contact-value"><i
                                            class="bi bi-telephone-fill me-1"
                                            style="font-size: 0.7rem;"></i>{{ $contactSummary['phone'] }}</a>
                                </div>
                            </div>

                            <!-- Managing Director -->
                            <div class="jsp-contact-row" id="jsp-ftr-contact-director">
                                <div class="jsp-contact-icon"><i class="bi bi-person-badge-fill"></i></div>
                                <div>
                                    <span class="jsp-contact-label">Managing Director</span>
                                    <span class="jsp-contact-value">{{ $contactSummary['md_name'] }}</span><br>
                                    <a href="tel:{{ $contactSummary['alternate_phone'] }}" class="jsp-contact-value"><i
                                            class="bi bi-telephone-fill me-1"
                                            style="font-size: 0.7rem;"></i>{{ $contactSummary['alternate_phone'] }}</a>
                                </div>
                            </div>

                            <!-- Landline -->
                            <div class="jsp-contact-row" id="jsp-ftr-contact-landline">
                                <div class="jsp-contact-icon"><i class="bi bi-telephone-fill"></i></div>
                                <div>
                                    <span class="jsp-contact-label">Landline</span>
                                    <a href="tel:{{ $contactSummary['landline'] }}" class="jsp-contact-value">{{ $contactSummary['landline'] }}</a>
                                </div>
                            </div>

                            <!-- WhatsApp -->
                            <div class="jsp-contact-row" id="jsp-ftr-contact-whatsapp">
                                <div class="jsp-contact-icon"><i class="bi bi-whatsapp"></i></div>
                                <div>
                                    <span class="jsp-contact-label">WhatsApp</span>
                                    <a href="https://wa.me/{{ $contactSummary['whatsapp_number'] }}" class="jsp-contact-value">{{ $contactSummary['whatsapp_number'] }}</a>
                                </div>
                            </div>

                            <!-- Viber -->
                            <div class="jsp-contact-row" id="jsp-ftr-contact-viber">
                                <div class="jsp-contact-icon"><i class="bi bi-phone-fill"></i></div>
                                <div>
                                    <span class="jsp-contact-label">Viber</span>
                                    <a href="tel:{{ $contactSummary['viber'] }}" class="jsp-contact-value">{{ $contactSummary['viber'] }}</a>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="jsp-contact-row" id="jsp-ftr-contact-email">
                                <div class="jsp-contact-icon"><i class="bi bi-envelope-fill"></i></div>
                                <div>
                                    <span class="jsp-contact-label">Email</span>
                                    <a href="mailto:{{ $contactSummary['email'] }}"
                                        class="jsp-contact-value">{{ $contactSummary['email'] }}</a>
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
                                &copy; {{ date('Y') }} <a href="{{ route('about') }}"
                                    class="jsp-copyright-link">Jay Shiv Parvati Travel & Tour</a>.
                                All Rights Reserved.
                            </p>
                        </div>
                        <div class="col-md-6 text-center text-md-end">
                            <p class="jsp-copyright-text mb-0">
                                <i class="bi bi-heart-fill text-danger" style="font-size: 0.7rem;"></i>
                                Designed by Indra
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </footer>

        <!-- WhatsApp Floating Button -->
        <a href="https://wa.me/{{ $contactSummary['whatsapp_number'] }}" class="jsp-whatsapp-float" title="Chat on WhatsApp"
            id="jsp-ftr-whatsapp-float">
            <i class="bi bi-whatsapp"></i>
        </a>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    {{-- script.js is served straight out of public/, so its URL has to change whenever
         the file does. Left unversioned, a browser keeps running the copy it cached and a
         rebuilt popup looks like it "did nothing". --}}
    <script src="{{ asset('script.js') }}?v={{ @filemtime(public_path('script.js')) ?: 1 }}"></script>
</body>

</html>

