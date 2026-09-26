@extends('adminlte::page')

@section('title', 'Add Tour')

@section('content_header')
    <h1>All Tours</h1>
@stop

@section('content')
    @include('home::admin.includes.errors-alert')

    @include('tours::admin.tours._form', [
        'tour' => null,
        'destinations' => $destinations,
        'formAction' => route('admin.tours.store'),
        'formMethod' => 'POST',
    ])
@stop