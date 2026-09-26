@extends('adminlte::page')

@section('title', 'Edit Hotel')

@section('content_header')
    <h1>All Hotels</h1>
@stop

@section('content')
    @include('home::admin.includes.errors-alert')

    @include('hotels::admin.hotels._form', [
        'hotel' => $hotel,
        'destinations' => $destinations,
        'formAction' => route('admin.hotels.update', $hotel),
        'formMethod' => 'PUT',
    ])
@stop