@extends('adminlte::page')

@section('title', 'Add Destination')

@section('content_header')
    <h1>Popular Destinations</h1>
@stop

@section('content')
    @include('home::admin.includes.errors-alert')

    @include('home::admin.destinations._form', [
        'destination' => null,
        'formAction' => route('admin.home.destinations.store'),
        'formMethod' => 'POST',
    ])
@stop