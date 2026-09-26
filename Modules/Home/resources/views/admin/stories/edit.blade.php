@extends('adminlte::page')

@section('title', 'Edit Story')

@section('content_header')
    <h1>Stories Worth Sharing</h1>
@stop

@section('content')
    @include('home::admin.includes.errors-alert')

    @include('home::admin.stories._form', [
        'story' => $story,
        'formAction' => route('admin.home.stories.update', $story),
        'formMethod' => 'PUT',
    ])
@stop