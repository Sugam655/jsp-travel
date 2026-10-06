{{-- The customer dashboard. It shares the AdminLTE panel with the admin area so
     both roles get the same shell, but the sidebar is narrowed to the customer
     section by App\Listeners\ConfigureAdminLteMenu. Every record below is scoped to
     the signed-in account in UserDashboardController. --}}
@extends('adminlte::page')

@section('title', 'My Dashboard')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>My Dashboard</h1>
        <a href="{{ route('booking.search') }}" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Book Now
        </a>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-lg-8">
            @if (! auth()->user()->isProfileComplete())
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                        <div>
                            <strong><i class="fa-solid fa-user-pen me-2"></i> Complete Your Profile</strong>
                            <span class="d-block small mt-1">
                                Add your phone number, address, city and country so we can reach you about your bookings.
                            </span>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-primary">
                            Complete Your Profile <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i>
                        </a>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('status') === 'profile-completed')
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i>
                    Your profile is complete. Welcome to your dashboard!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bookmark me-2"></i>Welcome, {{ $user->name }}</h3>
                </div>
                <div class="card-body">
                    {{-- Flash messages - a confirmed booking, a cancelled one, a
                         recorded payment - are shown as the panel's toast, the same
                         one the admin area uses. It appears once and closes itself,
                         so nothing is left sitting in this content. A booking update
                         the customer has not read yet lives in the notification bell
                         instead, which is persistent by design. --}}
                    @include('home::admin.includes.flash-toast')

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">My Bookings</h5>
                        <a href="{{ route('bookings.my') }}" class="btn btn-sm btn-outline-primary">View My Bookings</a>
                    </div>

                    @forelse ($bookings as $booking)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div>
                                    <a href="{{ route('bookings.show', $booking->booking_reference) }}" class="fw-semibold text-decoration-none">
                                        {{ $booking->service_title }}
                                    </a>
                                    <div class="text-muted small">
                                        {{ $booking->booking_reference }} &middot; {{ $booking->booking_type_label }}
                                    </div>
                                </div>
                                <span class="badge {{ $booking->status_color }}">{{ $booking->status_label }}</span>
                            </div>
                            <div class="row small text-muted mt-2 g-2">
                                <div class="col-sm-6">
                                    <i class="fa-solid fa-calendar-day me-1" aria-hidden="true"></i>
                                    {{ $booking->start_date?->format('M d, Y') }}
                                    @if ($booking->end_date)
                                        &rarr; {{ $booking->end_date->format('M d, Y') }}
                                    @endif
                                </div>
                                <div class="col-sm-3">
                                    <i class="fa-solid fa-users me-1" aria-hidden="true"></i>{{ $booking->travelers }} traveler(s)
                                </div>
                                <div class="col-sm-3">
                                    <i class="fa-solid fa-tag me-1" aria-hidden="true"></i>{!! $booking->amount_display !!}
                                </div>
                            </div>
                            <a href="{{ route('bookings.show', $booking->booking_reference) }}" class="btn btn-sm btn-primary mt-2">
                                View Details <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i>
                            </a>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <p class="text-muted mb-3">You don't have any bookings yet.</p>
                            <a href="{{ route('booking.search') }}" class="btn btn-primary">
                                Book Now <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i>
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="fas fa-wallet me-2"></i>My Payments</h3>
                    <a href="{{ route('payments.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Settled payments total
                        <strong>{{ number_format($paymentsTotal, 2) }}</strong>,
                        {{ $paymentsPending }} awaiting verification.
                    </p>
                    @forelse ($payments as $payment)
                        <a href="{{ route('payments.show', $payment) }}"
                            class="d-flex justify-content-between align-items-center border rounded p-2 mb-2 text-decoration-none text-reset">
                            <div>
                                <div class="fw-semibold small">#{{ $payment->id }} &middot; {{ $payment->booking->service_title }}</div>
                                <div class="text-muted small">{{ $payment->created_at->format('M d, Y') }}</div>
                            </div>
                            <div class="text-end">
                                <span class="badge {{ $payment->type_color }}">{{ $payment->type_label }}</span>
                                <div class="small mt-1">{{ $payment->amount_display }}</div>
                            </div>
                        </a>
                    @empty
                        <p class="text-muted small mb-0">No payments recorded yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="fas fa-bell me-2"></i>Notifications</h3>
                    @if ($notificationsUnread > 0)
                        <span class="badge rounded-pill text-bg-danger">{{ $notificationsUnread }} new</span>
                    @endif
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Booking and payment updates we have sent you.
                    </p>
                    <a href="{{ route('notifications.index') }}" class="btn btn-outline-primary w-100">
                        View Notifications <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user me-2"></i>My Profile</h3>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-3">
                        <li class="mb-2"><strong>{{ $user->name }}</strong></li>
                        <li class="text-muted mb-2"><i class="fa-solid fa-envelope me-2" aria-hidden="true"></i>{{ $user->email }}</li>
                        @if ($user->phone)
                            <li class="text-muted mb-2"><i class="fa-solid fa-phone me-2" aria-hidden="true"></i>{{ $user->phone }}</li>
                        @endif
                        @if ($user->address || $user->city || $user->country)
                            <li class="text-muted mb-2"><i class="fa-solid fa-location-dot me-2" aria-hidden="true"></i>{{ collect([$user->address, $user->city, $user->country])->filter()->implode(', ') }}</li>
                        @endif
                        <li class="text-muted">Member since {{ $user->created_at?->format('M d, Y') }}</li>
                    </ul>
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary w-100">
                        View Profile <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus me-2"></i>Start a booking</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Book a tour, hotel or vehicle and pay when we confirm availability.</p>
                    <a href="{{ route('booking.search') }}" class="btn btn-primary w-100">
                        Book Now <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
@stop