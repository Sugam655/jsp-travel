@extends('adminlte::page')

@section('title', 'Booking Details')
@section('content_header', 'Booking '.$booking->booking_reference)

@section('content')
    @include('bookings::frontend.partials.show-content', ['inAdminLte' => true])
@endsection