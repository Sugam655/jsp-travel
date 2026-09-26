@extends('adminlte::page')

@section('title', 'Edit Service')

@section('content_header')
    <h1>Our Services</h1>
@stop

@section('content')
    @include('home::admin.includes.errors-alert')

    @include('home::admin.services._form', [
        'service' => $service,
        'formAction' => route('admin.home.services.update', $service),
        'formMethod' => 'PUT',
    ])
@stop