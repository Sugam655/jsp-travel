@extends('adminlte::page')

@section('title', 'Edit Destination')

@section('content_header')
    <h1>Popular Destinations</h1>
@stop

@section('content')
    @include('home::admin.includes.errors-alert')

    @include('home::admin.destinations._form', [
        'destination' => $destination,
        'formAction' => route('admin.home.destinations.update', $destination),
        'formMethod' => 'PUT',
    ])
@stop