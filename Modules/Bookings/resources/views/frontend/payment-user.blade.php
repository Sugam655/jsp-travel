@extends('adminlte::page')

@section('title', 'Payment')
@section('content_header', 'Payment for '.$booking->booking_reference)

@section('content')
    @include('bookings::frontend.partials.payment-content', ['inAdminLte' => true])
@endsection