{{-- Cancelling a booking is part of managing it, so it stays inside the
     signed-in AdminLTE area. The cancellation policy is quoted by
     CancellationPolicyService; this view only presents it. --}}
@extends('adminlte::page')

@section('title', 'Cancel '.$booking->booking_reference)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Cancel booking {{ $booking->booking_reference }}</h1>
        <a href="{{ route('bookings.show', $booking->booking_reference) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Booking details
        </a>
    </div>
@stop

@section('content')
    @include('bookings::user.partials.cancel-content')
@stop
