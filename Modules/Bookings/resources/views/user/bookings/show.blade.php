{{-- A customer's own booking lives in the signed-in AdminLTE area, alongside the
     rest of their account. These routes are behind auth and ownership is proven
     before the controller reaches the view, so there is no public page here and
     no reason to drop back into the public frontend shell. --}}
@extends('adminlte::page')

@section('title', 'Booking '.$booking->booking_reference)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Booking {{ $booking->booking_reference }}</h1>
        <a href="{{ route('bookings.my') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> My Bookings
        </a>
    </div>
@stop

@section('content')
    @include('bookings::user.partials.show-content')
@stop
