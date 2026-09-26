@extends('adminlte::page')

@section('title', 'Book Now')
@section('content_header', 'Book Now')

@section('content')
    @include('bookings::frontend.partials.booking-form', [
        'inAdminLte' => true,
        'wrapClass' => 'card card-body shadow-sm',
        'fieldClass' => 'mb-3',
        'inputClass' => 'form-control',
        'textareaClass' => 'form-control',
        'btnClass' => 'btn btn-primary',
    ])
@endsection