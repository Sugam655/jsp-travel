@extends('adminlte::page')

@section('title', 'Add Story')

@section('content_header')
    <h1>Stories Worth Sharing</h1>
@stop

@section('content')
    @include('home::admin.includes.errors-alert')

    @include('home::admin.stories._form', [
        'story' => null,
        'formAction' => route('admin.home.stories.store'),
        'formMethod' => 'POST',
    ])
@stop