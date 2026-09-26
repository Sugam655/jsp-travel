@extends('adminlte::page')

@section('title', 'Edit Tour')

@section('content_header')
    <h1>All Tours</h1>
@stop

@section('content')
    @include('home::admin.includes.errors-alert')

    @include('tours::admin.tours._form', [
        'tour' => $tour,
        'destinations' => $destinations,
        'formAction' => route('admin.tours.update', $tour),
        'formMethod' => 'PUT',
    ])
@stop