@if(! ($inAdminLte ?? false))
<section class="contacts reach-contact">
    <div class="container">
        <div class="reach-topbar">
            <div>
                <span class="reach-label">Payment</span>
                <h2 class="reach-heading">Pay for {{ $booking->booking_reference }}</h2>
            </div>
            <p class="reach-subtext">Review your booking payment plan, then submit transaction evidence for manual verification.</p>
        </div>
@endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i>Please correct the highlighted payment details.
            </div>
        @endif

        @php
            $plan = $summary ?? (new \Modules\Bookings\Services\PaymentCalculationService)->summaryFor($booking);
            // While the advance is still outstanding it is the floor. Once it is
            // satisfied any positive amount up to the balance is accepted, which
            // is what allows a partial payment against the remaining balance.
            $minimumPayment = $plan['advance_remaining'] > 0.005
                ? (float) $plan['advance_remaining']
                : 0.01;
            $maximumPayment = max(0.01, (float) $plan['due']);
        @endphp

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="border rounded p-3 h-100 bg-white">
                    <div class="text-muted small">Total</div>
                    <div class="fs-5 fw-bold">{{ $booking->currency }} {{ number_format($plan['total'], 2) }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="border rounded p-3 h-100 bg-white">
                    <div class="text-muted small">Required now</div>
                    <div class="fs-5 fw-bold">{{ $booking->currency }} {{ number_format($plan['required_now'], 2) }}</div>
                    <div class="small text-muted">Advance: {{ $booking->currency }} {{ number_format($plan['advance_required'], 2) }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="border rounded p-3 h-100 bg-white">
                    <div class="text-muted small">Verified paid</div>
                    <div class="fs-5 fw-bold text-success">{{ $booking->currency }} {{ number_format($plan['paid'], 2) }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="border rounded p-3 h-100 bg-white">
                    <div class="text-muted small">Remaining <span class="fw-normal">(Amount due)</span></div>
                    <div class="fs-5 fw-bold text-primary">{{ $booking->currency }} {{ number_format($plan['remaining'], 2) }}</div>
                    @if ($plan['due_date'])
                        <div class="small text-muted">due by {{ \Illuminate\Support\Carbon::parse($plan['due_date'])->format('M d, Y') }}</div>
                    @endif
                </div>
            </div>
        </div>

        @if (in_array($booking->status, ['confirmed', 'payment_pending'], true) && $pendingPayment)
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="alert alert-warning" role="alert">
                        <h5 class="alert-heading">Payment evidence awaiting verification</h5>
                        <p class="mb-2">
                            We received {{ $pendingPayment->amount_display }} reported via {{ str_replace('_', ' ', $pendingPayment->method) }}.
                            The booking balance will change only after agency verification.
                        </p>
                        <a href="{{ route('payments.show', $pendingPayment) }}" class="btn btn-sm btn-outline-dark">View payment details</a>
                    </div>
                </div>
            </div>
        @elseif (in_array($booking->status, ['confirmed', 'payment_pending'], true) && count($methods) > 0)
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Available payment methods</h5>
                                <span class="fs-4 fw-bold text-success">{{ $booking->currency }} {{ number_format($plan['due'], 2) }}</span>
                            </div>

                            @foreach ($methods as $method)
                                <label class="border rounded p-3 mb-2 d-block" style="cursor: pointer;">
                                    <div class="form-check">
                                        <input type="radio" name="method_choice" value="{{ $method['method'] }}"
                                            class="form-check-input method-radio"
                                            data-details="{{ $method['details'] }}"
                                            {{ old('method', $methods[0]['method'] ?? '') === $method['method'] ? 'checked' : '' }}>
                                        <div class="form-check-label">
                                            <strong>{{ $method['label'] }}</strong>
                                            <div class="text-muted small">{{ $method['details'] }}</div>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="mb-3">Submit payment evidence</h5>
                            <p class="text-muted small" id="methodDetails">{{ $methods[0]['details'] ?? '' }}</p>

                            <form method="POST" action="{{ route('bookings.payment.notify', $booking->booking_reference) }}" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="method" id="f-method" value="{{ old('method', $methods[0]['method'] ?? '') }}">
                                <div class="mb-3">
                                    <label class="form-label" for="payment-amount" style="color: #6b7280;">Amount paid {{ $booking->currency }}</label>
                                    <input type="number" step="0.01" min="{{ number_format($minimumPayment, 2, '.', '') }}"
                                        max="{{ number_format($maximumPayment, 2, '.', '') }}"
                                        name="amount" id="payment-amount" class="form-control @error('amount') is-invalid @enderror"
                                         value="{{ old('amount', number_format($plan['required_now'], 2, '.', '')) }}" required>
                                    <div class="form-text small">
                                        Minimum {{ $booking->currency }} {{ number_format($minimumPayment, 2) }}.
                                         Payments above {{ $booking->currency }} {{ number_format($plan['due'], 2) }} cannot be submitted.
                                    </div>                                    @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="payment-reference" style="color: #6b7280;">Transaction reference / voucher number</label>
                                         <input type="text" name="reference" id="payment-reference" class="form-control @error('reference') is-invalid @enderror"
                                             value="{{ old('reference') }}" maxlength="255">
                                    @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="payment-note" style="color: #6b7280;">Note (optional)</label>
                                    <textarea name="message" id="payment-note" class="form-control @error('message') is-invalid @enderror" rows="3" maxlength="1000">{{ old('message') }}</textarea>
                                    @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="payment-receipt" style="color: #6b7280;">Receipt (optional)</label>
                                    <input type="file" name="receipt" id="payment-receipt" class="form-control @error('receipt') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf">
                                    <div class="form-text small">JPG, PNG, or PDF up to 5 MB. Receipts are stored privately.</div>
                                    @error('receipt')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fa-solid fa-check me-1"></i> Submit for verification
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @elseif (in_array($booking->status, ['confirmed', 'payment_pending'], true))
            <div class="alert alert-info" role="alert">No customer payment methods are currently available. Please contact the agency.</div>
        @elseif ($plan['remaining'] <= 0.005)
            <div class="alert alert-success" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>
                <strong>Fully Paid</strong> &mdash; no payment remaining for this booking.
            </div>
        @else
            <div class="alert alert-info" role="alert">
                <i class="fa-solid fa-circle-info me-2"></i>Waiting for admin confirmation. This payment form opens after the agency confirms your booking.
            </div>
        @endif

@if(! ($inAdminLte ?? false))
    </div>
</section>
@endif

<script>
    const initialMethod = document.querySelector('.method-radio:checked');
    const initialReference = document.getElementById('payment-reference');
    if (initialMethod && initialReference) {
        initialReference.required = initialMethod.value !== 'cash';
    }

    document.querySelectorAll('.method-radio').forEach(radio => {
        radio.addEventListener('change', function () {
            const details = document.getElementById('methodDetails');
            const method = document.getElementById('f-method');

            if (details) {
                details.textContent = this.dataset.details;
            }

            if (method) {
                method.value = this.value;
            }

            const reference = document.getElementById('payment-reference');
            if (reference) {
                reference.required = this.value !== 'cash';
            }
        });
    });
</script>
