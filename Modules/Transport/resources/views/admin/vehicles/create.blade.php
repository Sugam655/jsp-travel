@extends('adminlte::page')

@section('title', 'Add Vehicle')

@section('content_header')
    <h1>All Vehicles</h1>
@stop

@section('content')
    @include('home::admin.includes.errors-alert')

    @include('transport::admin.vehicles._form', [
        'vehicle' => null,
        'destinations' => $destinations,
        'formAction' => route('admin.transport.store'),
        'formMethod' => 'POST',
    ])
@stop