@extends('adminlte::page')

@section('title', 'Cancel Booking')
@section('content_header', 'Cancel '.$booking->booking_reference)

@section('content')
    @include('bookings::frontend.partials.cancel-content', ['inAdminLte' => true])
@endsection