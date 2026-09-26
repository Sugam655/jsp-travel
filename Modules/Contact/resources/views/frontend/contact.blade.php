@include('frontend.layouts.header')

@include('frontend.layouts.mobile-nav')

    <!-- ========== CONTACT SECTION ========== -->
    <section class="contacts reach-contact">
        <div class="container">
            <!-- Top Header -->
            <div class="reach-topbar">
                <div>
                    <span class="reach-label">Plan Trip</span>
                    <h2 class="reach-heading">{{ $contact['page_title'] }}</h2>
                </div>
                <p class="reach-subtext">{{ $contact['page_subtitle'] }}</p>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><i class="fa-solid fa-circle-exclamation me-1"></i> Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Form + Image Row -->
            <div class="row g-4">
                <!-- Form Column -->
                <div class="col-lg-7">
                    <div class="reach-form-wrap">
                        <form id="reach-form" method="POST" action="{{ route('contact.store') }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="reach-field">
                                        <label for="reach-name">Name</label>
                                        <input type="text" id="reach-name" name="name"
                                            class="reach-input @error('name') is-invalid @enderror"
                                            placeholder="Your full name" value="{{ old('name') }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="reach-field">
                                        <label for="reach-email">Email</label>
                                        <input type="email" id="reach-email" name="email"
                                            class="reach-input @error('email') is-invalid @enderror"
                                            placeholder="you@example.com" value="{{ old('email') }}" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="reach-field">
                                        <label for="reach-phone">Phone Number</label>
                                        <input type="tel" id="reach-phone" name="phone"
                                            class="reach-input @error('phone') is-invalid @enderror"
                                            placeholder="+977 98xx-xxxxxx" value="{{ old('phone') }}">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="reach-field">
                                        <label for="reach-subject">Subject</label>
                                        <input type="text" id="reach-subject" name="subject"
                                            class="reach-input @error('subject') is-invalid @enderror"
                                            placeholder="How can we help?" value="{{ old('subject') }}" required>
                                        @error('subject')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="reach-field">
                                        <label for="reach-message">Message</label>
                                        <textarea id="reach-message" name="message" rows="5"
                                            class="reach-textarea @error('message') is-invalid @enderror"
                                            placeholder="Tell us about your trip plans or any questions you have." required>{{ old('message') }}</textarea>
                                        @error('message')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="reach-btn">
                                Send Message
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Image Column -->
                <div class="col-lg-5">
                    <div class="reach-visual">
                        <span class="reach-badge">Your Journey</span>
                        <img src="{{ $contact['contact_image_url'] }}" alt="Contact {{ $contact['company_name'] }}">
                    </div>
                </div>
            </div>

            <!-- Social Links -->
            @php
                $socials = [
                    'facebook_url' => ['fa-brands fa-facebook-f', 'Facebook'],
                    'instagram_url' => ['fa-brands fa-instagram', 'Instagram'],
                    'youtube_url' => ['fa-brands fa-youtube', 'YouTube'],
                    'twitter_url' => ['fa-brands fa-x-twitter', 'Twitter / X'],
                ];
                $hasSocials = collect($socials)->filter(fn ($meta, $key) => filled($contact[$key] ?? null))->isNotEmpty();
            @endphp
            @if ($hasSocials || filled($contact['whatsapp_number'] ?? null))
                <div class="reach-socials">
                    @foreach ($socials as $key => $meta)
                        @if (filled($contact[$key] ?? null))
                            <a href="{{ $contact[$key] }}" class="reach-social" target="_blank" rel="noopener"
                                title="{{ $meta[1] }}">
                                <i class="{{ $meta[0] }}"></i>
                            </a>
                        @endif
                    @endforeach
                    @if (filled($contact['whatsapp_number'] ?? null))
                        <a href="https://wa.me/{{ $contact['whatsapp_number'] }}" class="reach-social" target="_blank"
                            rel="noopener" title="WhatsApp">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                    @endif
                </div>
            @endif

            <!-- Info Cards -->
            <div class="row reach-infobar">
                <div class="col-md-4">
                    <div class="reach-infoitem">
                        <div class="reach-infoicon">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <h6 class="reach-infotitle">Call & WhatsApp</h6>
                        <p class="reach-infodetail">
                            {{ $contact['phone'] }}<br>
                            {{ $contact['alternate_phone'] }}
                            @if ($contact['landline'])
                                <br>{{ $contact['landline'] }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="reach-infoitem">
                        <div class="reach-infoicon">
                            <i class="fa-regular fa-clock"></i>
                        </div>
                        <h6 class="reach-infotitle">Working Hours</h6>
                        <p class="reach-infodetail">{{ $contact['opening_hours'] }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="reach-infoitem">
                        <div class="reach-infoicon">
                            <i class="fa-regular fa-envelope"></i>
                        </div>
                        <h6 class="reach-infotitle">Write to Us</h6>
                        <p class="reach-infodetail">
                            {{ $contact['email'] }}
                            @if ($contact['alternate_email'])
                                <br>{{ $contact['alternate_email'] }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            @if ($contact['address'])
                <div class="reach-address text-center mb-4">
                    <i class="fa-solid fa-location-dot me-2 text-danger"></i>
                    <span>{{ $contact['address'] }}</span>
                </div>
            @endif

            @if ($contact['map_url'])
                <div class="reach-map mb-4">
                    <iframe src="{{ $contact['map_url'] }}" class="w-100" style="border:0; min-height:360px;"
                        allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            @endif

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
                                alt="Far West Nepal landscape">
                        </div>
                        <div class="reach-promo-img">
                            <img src="https://english.onlinekhabar.com/wp-content/uploads/2020/10/Ghodaghodi_Lake_Kailali.jpg"
                                alt="Ghodaghodi Lake">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section>
@include('frontend.layouts.footer')