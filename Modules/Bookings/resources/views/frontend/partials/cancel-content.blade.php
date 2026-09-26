@if(! ($inAdminLte ?? false))
<section class="contacts reach-contact">
        <div class="container">
            <div class="reach-topbar">
                <div>
                    <span class="reach-label">Cancellation</span>
                    <h2 class="reach-heading">Cancel {{ $booking->booking_reference }}</h2>
                </div>
                <p class="reach-subtext">Review what applies before you confirm cancellation. This step cannot be undone.</p>
            </div>
@endif

            <div class="shadow-sm p-4 bg-white rounded" style="max-width: 560px;">
                @if ($quote['eligible'] ?? false)
                    <div class="alert alert-info">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        Cancelling <strong>{{ $quote['days_before'] }} day(s)</strong> before your service
                        ({{ $booking->start_date->format('M d, Y') }}).
                    </div>
                    <div class="mb-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Booking total</span>
                                    <strong>{!! $booking->amount_display !!}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-danger">Cancellation fee ({{ $quote['fee_pct'] }}%)</span>
                                    <strong class="text-danger">-{{ $booking->currency }} {{ number_format((float) $quote['fee_amount'], 2) }}</strong>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <span>Amount refunded</span>
                                    <strong class="text-success">{{ $booking->currency }} {{ number_format((float) $quote['refund_amount'], 2) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('bookings.cancel', $booking->booking_reference) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" style="color: #6b7280;">Reason (optional)</label>
                            <textarea name="reason" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('bookings.show', $booking->booking_reference) }}"
                                class="btn btn-secondary">Keep booking</a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fa-solid fa-ban me-1"></i> Confirm cancellation
                            </button>
                        </div>
                    </form>
                @else
                    <div class="alert alert-warning mb-4">
                        <i class="fa-solid fa-circle-exclamation me-2"></i>{{ $quote['reason'] ?? 'Cancellation is not available for this booking.' }}
                    </div>
                    <a href="{{ route('bookings.show', $booking->booking_reference) }}"
                        class="btn btn-secondary">Back to booking</a>
                @endif
            </div>
    @if(! ($inAdminLte ?? false))
        </div>
    </section>
    @endif