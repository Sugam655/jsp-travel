{{-- Cancellation is irreversible, so the quoted consequences stay visible until the
     customer confirms. CancellationPolicyService owns these numbers; this view only
     presents them and posts the confirmation. --}}
<div class="card card-outline card-danger mx-auto" style="max-width: 560px;">
    <div class="card-header">
        <h3 class="card-title">Confirm cancellation</h3>
    </div>
    <div class="card-body">
        @if ($quote['eligible'] ?? false)
            <div class="alert alert-info">
                <i class="fa-solid fa-circle-info me-2"></i>
                Cancelling <strong>{{ $quote['days_before'] }} day(s)</strong> before your service
                ({{ $booking->start_date->format('M d, Y') }}).
            </div>

            <table class="table table-sm mb-3">
                <tbody>
                    <tr>
                        <td class="text-muted">Booking total</td>
                        <td class="text-end">{!! $booking->amount_display !!}</td>
                    </tr>
                    <tr>
                        <td class="text-muted text-danger">Cancellation fee ({{ $quote['fee_pct'] }}%)</td>
                        <td class="text-end text-danger">-{{ $booking->currency }} {{ number_format((float) $quote['fee_amount'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Amount refunded</td>
                        <td class="text-end text-success fw-bold">{{ $booking->currency }} {{ number_format((float) $quote['refund_amount'], 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <form method="POST" action="{{ route('bookings.cancel', $booking->booking_reference) }}">
                @csrf
                <div class="mb-3">
                    <label for="cancel-reason" class="form-label">Reason (optional)</label>
                    <textarea name="reason" id="cancel-reason" class="form-control" rows="3" maxlength="1000"></textarea>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('bookings.show', $booking->booking_reference) }}" class="btn btn-secondary">
                        Keep booking
                    </a>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-ban me-1"></i> Confirm cancellation
                    </button>
                </div>
            </form>
        @else
            <div class="alert alert-warning mb-4">
                <i class="fa-solid fa-circle-exclamation me-2"></i>{{ $quote['reason'] ?? 'Cancellation is not available for this booking.' }}
            </div>
            <a href="{{ route('bookings.show', $booking->booking_reference) }}" class="btn btn-secondary">
                Back to booking
            </a>
        @endif
    </div>
</div>
