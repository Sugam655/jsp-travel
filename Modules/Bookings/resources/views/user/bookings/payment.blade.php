{{-- Paying for a booking is part of managing it, so it stays inside the signed-in
     AdminLTE area instead of returning the customer to the public frontend. --}}
@extends('adminlte::page')

@section('title', 'Pay for '.$booking->booking_reference)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Pay for booking {{ $booking->booking_reference }}</h1>
        <a href="{{ route('bookings.my') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> My Bookings
        </a>
    </div>
@stop

@section('content')
    @include('bookings::user.partials.payment-content')
@stop
