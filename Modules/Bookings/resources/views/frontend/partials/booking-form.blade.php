@php
    $preselectedServiceId = $service?->id;
    $preselectedType = $serviceType;

    $inAdminLte = $inAdminLte ?? false;
    $wrapClass = $wrapClass ?? 'reach-form-wrap';
    $fieldClass = $fieldClass ?? 'reach-field';
    $inputClass = $inputClass ?? 'reach-input';
    $textareaClass = $textareaClass ?? 'reach-textarea';
    $btnClass = $btnClass ?? 'reach-btn';
@endphp
<!-- ========== BOOKING SECTION ========== -->
@if(! $inAdminLte)
<section class="contacts reach-contact">
    <div class="container">
        <div class="reach-topbar">
            <div>
                <span class="reach-label">Reserve Your Trip</span>
                <h2 class="reach-heading">Book Now</h2>
            </div>
            <p class="reach-subtext">Select a tour, hotel or vehicle, choose your dates and number of travelers, and
                review the full price before you send your booking request.</p>
        </div>
@endif

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

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="{{ $wrapClass }}">
                    <form method="POST" action="{{ route('bookings.store') }}" id="bookingForm">
                        @csrf
                        <input type="hidden" name="booking_type" id="f-booking_type"
                            value="{{ $preselectedType ?: old('booking_type', 'tour') }}">
                        <input type="hidden" name="service_id" id="f-service_id"
                            value="{{ $preselectedServiceId ?: old('service_id') }}">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="{{ $fieldClass }}">
                                    <label for="bk-type">What do you want to book?</label>
                                    <select id="bk-type" class="{{ $inputClass }}"
                                        onchange="selectType(this.value)">
                                        @foreach ($types as $key => $label)
                                            <option value="{{ $key }}"
                                                {{ ($preselectedType ?: old('booking_type', 'tour')) === $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="{{ $fieldClass }}">
                                    <label for="bk-service">Select Service</label>
                                    <select id="bk-service" class="{{ $inputClass }}"
                                        onchange="selectService(this.value)">
                                        <option value="">&mdash; Choose a service &mdash;</option>
                                        @foreach ($tours as $tourItem)
                                            <option value="{{ $tourItem->id }}" data-type="tour"
                                                data-price="{{ $tourItem->duration }}"
                                                {{ $preselectedType === 'tour' && $preselectedServiceId === $tourItem->id ? 'selected' : '' }}>
                                                {{ $tourItem->title }} ({{ $tourItem->duration }})
                                            </option>
                                        @endforeach
                                        @foreach ($hotels as $hotelItem)
                                            <option value="{{ $hotelItem->id }}" data-type="hotel"
                                                data-price="{{ $hotelItem->price }}"
                                                {{ $preselectedType === 'hotel' && $preselectedServiceId === $hotelItem->id ? 'selected' : '' }}>
                                                {{ $hotelItem->title }} &mdash; Rs.{{ number_format((float) $hotelItem->price) }}/night
                                            </option>
                                        @endforeach
                                        @foreach ($vehicles as $vehicleItem)
                                            <option value="{{ $vehicleItem->id }}" data-type="vehicle"
                                                data-price="{{ $vehicleItem->price }}"
                                                {{ $preselectedType === 'vehicle' && $preselectedServiceId === $vehicleItem->id ? 'selected' : '' }}>
                                                {{ $vehicleItem->name }} &mdash; Rs.{{ number_format((float) $vehicleItem->price) }} ({{ str_replace('_', ' ', $vehicleItem->price_unit ?? 'per_day') }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="{{ $fieldClass }}">
                                    <label for="bk-travelers">Travelers</label>
                                    <input type="number" id="bk-travelers" name="travelers" min="1"
                                        class="{{ $inputClass }}" placeholder="2"
                                        value="{{ old('travelers', 2) }}" oninput="scheduleQuote()">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="{{ $fieldClass }}">
                                    <label for="bk-start">Start Date</label>
                                    <input type="date" id="bk-start" name="start_date"
                                        class="{{ $inputClass }}" value="{{ old('start_date') }}" onchange="scheduleQuote()">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="{{ $fieldClass }}">
                                    <label for="bk-end">End Date <small class="text-muted">(optional for tours)</small></label>
                                    <input type="date" id="bk-end" name="end_date"
                                        class="{{ $inputClass }}" value="{{ old('end_date') }}" onchange="scheduleQuote()">
                                </div>
                            </div>

<div class="col-md-4">
                                    <div class="{{ $fieldClass }}">
                                        <label for="bk-name">Full Name</label>
                                        <input type="text" id="bk-name" name="name"
                                            class="{{ $inputClass }} @error('name') is-invalid @enderror"
                                            placeholder="Your full name" value="{{ old('name', auth()->user()?->name ?? '') }}" required>
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="{{ $fieldClass }}">
                                        <label for="bk-email">Email</label>
                                        <input type="email" id="bk-email" name="email"
                                            class="{{ $inputClass }} @error('email') is-invalid @enderror"
                                            placeholder="you@example.com" value="{{ old('email', auth()->user()?->email ?? '') }}" required>
                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="{{ $fieldClass }}">
                                        <label for="bk-phone">Phone Number</label>
                                        <input type="tel" id="bk-phone" name="phone"
                                            class="{{ $inputClass }} @error('phone') is-invalid @enderror"
                                            placeholder="+977 98xx-xxxxxx" value="{{ old('phone', auth()->user()?->phone ?? '') }}" required>
                                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="{{ $fieldClass }}">
                                        <label for="bk-address">Address</label>
                                        <input type="text" id="bk-address" name="address"
                                            class="{{ $inputClass }} @error('address') is-invalid @enderror"
                                            placeholder="City / town" value="{{ old('address', auth()->user()?->address ?? '') }}">
                                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="{{ $fieldClass }}">
                                        <label for="bk-message">Message / Requirements</label>
                                        <textarea id="bk-message" name="message" rows="4"
                                            class="{{ $textareaClass }} @error('message') is-invalid @enderror"
                                            placeholder="Any special requests or requirements">{{ old('message') }}</textarea>
                                        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input type="checkbox" name="policy_accepted" id="policy_accepted"
                                            class="form-check-input {{ $errors->has('policy_accepted') ? 'is-invalid' : '' }}"
                                            value="1" {{ old('policy_accepted') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="policy_accepted" style="color: #6b7280;">
                                            I have read and agree to the <a href="{{ route('bookings.create') }}" onclick="alert('The cancellation policy and booking terms are shown in the price summary above and in your booking confirmation.')">booking terms and cancellation policy</a>, including the refund schedule shown for my booking.
                                        </label>
                                        @error('policy_accepted')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="{{ $btnClass }}" id="submitBtn" disabled>
                                Request Booking
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>
                            <p class="text-muted small mt-3 mb-0">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                This is a booking request. We verify availability, then request payment. Your service
                                is held for the confirmation deadline shown on your reservation.
                            </p>
                        </form>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="{{ $wrapClass }} p-4" id="quoteCard">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Price Summary</h5>
                            <span class="badge bg-secondary" id="quoteState">Select a service</span>
                        </div>

                        <div id="quoteBody">
                            <p class="text-muted mb-0">
                                Choose a service and your dates to see live availability and the full price breakdown.
                            </p>
                        </div>
                    </div>

                    <div class="{{ $wrapClass }} mt-4 p-4">
                        <h5>Good to know</h5>
                        <ul class="text-muted small ps-3 mb-0">
                            <li class="mb-2">Prices include taxes; the total is fixed before you submit.</li>
                            <li class="mb-2">Your reservation reference is shown after confirming.</li>
                            <li class="mb-2">Cancellation refunds follow the policy shown on your reservation.</li>
                            <li>We confirm manually to guarantee availability.</li>
                        </ul>
</div>
            </div>
            </div>
    @if(! $inAdminLte)
        </div>
    </section>
    @endif

    <script>
        window.bookingType = "{{ $preselectedType ?: 'tour' }}";

        function selectType(type) {
            window.bookingType = type;
            document.getElementById('f-booking_type').value = type;
            const service = document.getElementById('bk-service');
            const opts = service.querySelectorAll('option[data-type]');
            opts.forEach(o => (o.hidden = o.dataset.type !== type));
            const current = service.value;
            service.value = '';
            opts.forEach(o => {
                if (o.dataset.type === type && o.value === current) service.value = current;
            });
            if (!service.value) selectService('');
            else selectService(service.value);
            scheduleQuote();
        }

        function selectService(value) {
            document.getElementById('f-service_id').value = value || '';
            scheduleQuote();
        }

        let quoteTimer;
        function scheduleQuote() {
            clearTimeout(quoteTimer);
            quoteTimer = setTimeout(loadQuote, 350);
        }

        async function loadQuote() {
            const body = {
                booking_type: window.bookingType,
                service_id: parseInt(document.getElementById('f-service_id').value || '0', 10),
                start_date: document.getElementById('bk-start').value || null,
                end_date: document.getElementById('bk-end').value || null,
                travelers: parseInt(document.getElementById('bk-travelers').value || '1', 10) || 1,
            };

            if (!body.service_id) {
                setQuoteState('secondary', 'Select a service');
                document.getElementById('quoteBody').innerHTML =
                    '<p class="text-muted mb-0">Choose a service and your dates to see the price.</p>';
                setSubmit(false);
                return;
            }

            setQuoteState('secondary', 'Checking...');
            try {
                const res = await fetch('{{ route('bookings.quote') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify(body),
                });
                const data = await res.json();

                if (!data.available || !data.quote) {
                    setQuoteState('danger', 'Not available');
                    document.getElementById('quoteBody').innerHTML =
                        '<div class="alert alert-danger mb-0"><i class="fa-solid fa-circle-exclamation me-2"></i>' +
                        (data.reason || 'This service is not available for the selected period.') + '</div>';
                    setSubmit(false);
                    return;
                }

                setQuoteState('success', 'Available');
                renderQuote(data.quote);
                setSubmit(true);
            } catch (e) {
                setQuoteState('danger', 'Error');
                document.getElementById('quoteBody').innerHTML =
                    '<div class="alert alert-danger mb-0">Could not check availability right now. Please try again.</div>';
                setSubmit(false);
            }
        }

        function renderQuote(q) {
            const money = v => v === null || v === undefined ? '0.00' : Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('quoteBody').innerHTML = `
                <table class="table table-sm mb-2">
                    <tr><td class="text-muted">Duration</td><td class="text-end"><strong>${q.duration_label || '&mdash;'}</strong></td></tr>
                    <tr><td class="text-muted">Subtotal</td><td class="text-end">${q.currency} ${money(q.subtotal)}</td></tr>
                    <tr><td class="text-muted">Tax (${q.tax_rate}%)</td><td class="text-end">${q.currency} ${money(q.tax_amount)}</td></tr>
                    <tr><td class="text-muted">Service charge (${q.service_charge_rate}%)</td><td class="text-end">${q.currency} ${money(q.service_charge)}</td></tr>
                    <tr class="table-active"><td><strong>Total</strong></td><td class="text-end"><strong>${q.currency} ${money(q.total)}</strong></td></tr>
                </table>
                <div class="text-muted small">${q.recalc_note || ''}</div>
            `;
        }

        function setQuoteState(color, text) {
            const badge = document.getElementById('quoteState');
            badge.className = 'badge bg-' + color;
            badge.textContent = text;
        }

        function setSubmit(enabled) {
            document.getElementById('submitBtn').disabled = !enabled;
        }

        document.addEventListener('DOMContentLoaded', function () {
            selectType(window.bookingType);
            if (document.getElementById('bk-start').value) scheduleQuote();
        });
    </script>