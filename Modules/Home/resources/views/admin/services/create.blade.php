@extends('adminlte::page')

@section('title', 'Add Service')

@section('content_header')
    <h1>Our Services</h1>
@stop

@section('content')
    @include('home::admin.includes.errors-alert')

    @include('home::admin.services._form', [
        'service' => null,
        'formAction' => route('admin.home.services.store'),
        'formMethod' => 'POST',
    ])
@stop