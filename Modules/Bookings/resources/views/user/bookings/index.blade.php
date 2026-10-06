{{-- The customer booking history inside the shared AdminLTE panel. The query
     behind this page is always scoped to the signed-in account or to references
     proven in this session, so another customer's booking is never listed. --}}
@extends('adminlte::page')

@section('title', 'My Bookings')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>My Bookings</h1>
        <a href="{{ route('booking.search') }}" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Book Now
        </a>
    </div>
@stop

@section('content')
    {{-- Shown as the panel's toast rather than as an alert in this content: an alert
         sits here until it is clicked away, which reads as a permanent message. --}}
    @include('home::admin.includes.flash-toast')

    @if ($bookings->isNotEmpty())
        <div class="row mb-3">
            @foreach ([
                ['label' => 'Total bookings', 'value' => $counts['total'], 'icon' => 'fa-list-check'],
                ['label' => 'Pending', 'value' => $counts['pending'], 'icon' => 'fa-clock'],
                ['label' => 'Confirmed', 'value' => $counts['confirmed'], 'icon' => 'fa-circle-check'],
                ['label' => 'Payment pending', 'value' => $counts['payment_pending'], 'icon' => 'fa-credit-card'],
                ['label' => 'Completed', 'value' => $counts['completed'], 'icon' => 'fa-flag-checkered'],
                ['label' => 'Cancelled / rejected', 'value' => $counts['cancelled'], 'icon' => 'fa-ban'],
            ] as $card)
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card h-100 shadow-sm text-center">
                        <div class="card-body py-3">
                            <div class="text-muted mb-1"><i class="fa-solid {{ $card['icon'] }}"></i></div>
                            <div class="fs-4 fw-semibold">{{ $card['value'] }}</div>
                            <div class="small text-muted">{{ $card['label'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="alert alert-info d-flex align-items-center gap-2 shadow-sm">
        <i class="fa-solid fa-circle-info"></i>
        <span>You are signed in — every booking you make while signed in (or link below) stays on your account and will be here after you log out and back in.</span>
    </div>

    <div class="card shadow-sm mb-4" style="max-width: 520px;">
        <div class="card-body">
            <h5 class="mb-3">Find my booking</h5>
            <form method="POST" action="{{ route('bookings.lookup') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" style="color: #6b7280;">Booking reference</label>
                    <input type="text" name="booking_reference" class="form-control"
                        placeholder="BK-2026-00012" value="{{ old('booking_reference') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="color: #6b7280;">Email used for booking</label>
                    <input type="email" name="email" class="form-control"
                        placeholder="you@example.com" value="{{ old('email') }}" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-search me-1"></i> Find my booking
                </button>
            </form>
        </div>
    </div>

    @if ($bookings->isNotEmpty())
        <div class="row g-3">
            @foreach ([
                'hotel' => ['title' => 'Hotel Bookings', 'icon' => 'fa-hotel'],
                'vehicle' => ['title' => 'Car Rentals', 'icon' => 'fa-car'],
                'tour' => ['title' => 'Tours', 'icon' => 'fa-route'],
            ] as $type => $section)
                @php($typeBookings = $bookings->where('booking_type', $type))
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex align-items-center gap-2">
                            <i class="fa-solid {{ $section['icon'] }} text-primary"></i>
                            <h5 class="mb-0">{{ $section['title'] }}</h5>
                            <span class="badge bg-secondary ms-auto">{{ $typeBookings->count() }}</span>
                        </div>
                        <div class="card-body p-0">
                            @if ($typeBookings->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Reference</th>
                                                <th>Service</th>
                                                <th>
                                                    @if ($type === 'hotel')
                                                        Check-in
                                                    @elseif ($type === 'vehicle')
                                                        Pickup date
                                                    @else
                                                        Travel date
                                                    @endif
                                                </th>
                                                @if ($type === 'vehicle')
                                                    <th>Return date</th>
                                                @endif
                                                @if ($type === 'hotel')
                                                    <th>Check-out</th>
                                                    <th>Guests</th>
                                                @else
                                                    <th>
                                                        @if ($type === 'vehicle')
                                                            Passengers
                                                        @else
                                                            Travelers
                                                        @endif
                                                    </th>
                                                @endif
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($typeBookings as $booking)
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('bookings.show', $booking->booking_reference) }}"
                                                            class="text-primary fw-semibold text-decoration-none">{{ $booking->booking_reference }}</a>
                                                    </td>
                                                    <td>{{ $booking->service_title }}</td>
                                                    <td>{{ $booking->start_date?->format('M d, Y') }}</td>
                                                    @if ($type === 'vehicle')
                                                        <td>{{ $booking->end_date?->format('M d, Y') }}</td>
                                                        <td>{{ $booking->travelers }}</td>
                                                    @elseif ($type === 'hotel')
                                                        <td>{{ $booking->end_date?->format('M d, Y') }}</td>
                                                        <td>{{ $booking->travelers }}</td>
                                                    @else
                                                        <td>{{ $booking->travelers }}</td>
                                                    @endif
                                                    <td>{!! $booking->amount_display !!}</td>
                                                    <td>
                                                        <span class="badge {{ $booking->status_color }}">{{ $booking->status_label }}</span>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('bookings.show', $booking->booking_reference) }}"
                                                            class="btn btn-sm btn-outline-primary">Details</a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-muted p-4">
                                    No {{ strtolower($section['title']) }} yet.
                                    <a href="{{ $type === 'hotel' ? route('hotel') : ($type === 'vehicle' ? route('transport') : route('destinations.index')) }}"
                                        class="text-primary">Browse {{ strtolower($section['title']) }} &rarr;</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-light border text-muted">
            No bookings are linked to this session yet. Use the finder below, or browse our
            <a href="{{ route('booking.search') }}" class="text-primary">book now</a> page to make your first booking.
        </div>
    @endif
@stop