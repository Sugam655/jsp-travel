@extends('adminlte::page')

@section('title', 'Edit Vehicle')

@section('content_header')
    <h1>All Vehicles</h1>
@stop

@section('content')
    @include('home::admin.includes.errors-alert')

    @include('transport::admin.vehicles._form', [
        'vehicle' => $vehicle,
        'destinations' => $destinations,
        'formAction' => route('admin.transport.update', $vehicle),
        'formMethod' => 'PUT',
    ])
@stop