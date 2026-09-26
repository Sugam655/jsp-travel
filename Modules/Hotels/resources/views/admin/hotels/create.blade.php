@extends('adminlte::page')

@section('title', 'Add Hotel')

@section('content_header')
    <h1>All Hotels</h1>
@stop

@section('content')
    @include('home::admin.includes.errors-alert')

    @include('hotels::admin.hotels._form', [
        'hotel' => null,
        'destinations' => $destinations,
        'formAction' => route('admin.hotels.store'),
        'formMethod' => 'POST',
    ])
@stop