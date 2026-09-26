@extends('adminlte::page')

@section('title', 'View User')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $user->name }}</h1>
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i> Back to Users
        </a>
    </div>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')

    <div class="alert alert-info d-flex align-items-start" role="alert">
        <i class="fa-solid fa-circle-info me-2 mt-1" aria-hidden="true"></i>
        <div>
            This account is <strong>read-only</strong> here. Each user maintains their own
            name, contact details and password from their own
            <em>My Profile</em> page.
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-primary card-outline mb-4">
                <div class="card-header">
                    <h3 class="card-title">Personal Information</h3>
                    <p class="card-text small text-muted mb-0 mt-1">
                        Account #{{ $user->id }} &middot; Registered
                        {{ $user->created_at?->format('M d, Y') }}
                    </p>
                </div>
                <div class="card-body">
                    <div class="mb-4 d-flex align-items-center gap-3">
                        @if ($user->profile_photo_url)
                            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}"
                                class="rounded-circle" width="64" height="64"
                                style="object-fit: cover;">
                        @else
                            <div class="bg-secondary rounded-circle text-white d-flex align-items-center justify-content-center"
                                style="width:64px;height:64px;font-size:1.5rem;">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <dl class="row mb-0">
                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8">{{ $user->name }}</dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $user->email }}</dd>

                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8">{{ $user->phone ?: '—' }}</dd>

                        <dt class="col-sm-4">Address</dt>
                        <dd class="col-sm-8">{{ $user->address ?: '—' }}</dd>

                        <dt class="col-sm-4">City</dt>
                        <dd class="col-sm-8">{{ $user->city ?: '—' }}</dd>

                        <dt class="col-sm-4">Country</dt>
                        <dd class="col-sm-8">{{ $user->country ?: '—' }}</dd>

                        <dt class="col-sm-4">Emergency Contact</dt>
                        <dd class="col-sm-8">{{ $user->emergency_contact ?: '—' }}</dd>

                        <dt class="col-sm-4">Date of Birth</dt>
                        <dd class="col-sm-8">{{ $user->date_of_birth ?: '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card card-secondary card-outline mb-4">
                <div class="card-header">
                    <h3 class="card-title">Recent Bookings</h3>
                    <p class="card-text small text-muted mb-0 mt-1">
                        {{ $bookingCount }} {{ Str::plural('booking', $bookingCount) }} in total.
                    </p>
                </div>
                <div class="card-body p-0">
                    @if ($latestBookings->isEmpty())
                        <p class="text-muted mb-0 p-3">This user has no bookings yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Service</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($latestBookings as $booking)
                                        <tr>
                                            <td>{{ $booking->booking_reference }}</td>
                                            <td>{{ $booking->service_title }}</td>
                                            <td>{{ \Illuminate\Support\Str::headline($booking->booking_type) }}</td>
                                            <td>{{ \Illuminate\Support\Str::headline($booking->status) }}</td>
                                            <td class="text-end">{{ number_format((float) $booking->total_amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-warning card-outline mb-4">
                <div class="card-header">
                    <h3 class="card-title">Account</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Role</dt>
                        <dd class="col-sm-7">
                            @if ($user->isAdmin())
                                <span class="badge text-bg-warning">
                                    <i class="fa-solid fa-user-shield fa-fw"></i> Admin
                                </span>
                            @else
                                <span class="badge text-bg-info">
                                    <i class="fa-solid fa-user fa-fw"></i> User
                                </span>
                            @endif
                        </dd>

                        <dt class="col-sm-5">Email Verified</dt>
                        <dd class="col-sm-7">
                            @if ($user->email_verified_at)
                                <span class="badge text-bg-success">Verified</span>
                                <span class="text-muted small d-block">
                                    {{ $user->email_verified_at->format('M d, Y') }}
                                </span>
                            @else
                                <span class="badge text-bg-secondary">Unverified</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5">Registered</dt>
                        <dd class="col-sm-7">{{ $user->created_at?->format('M d, Y') }}</dd>

                        <dt class="col-sm-5">Last Updated</dt>
                        <dd class="col-sm-7">{{ $user->updated_at?->format('M d, Y') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@stop
