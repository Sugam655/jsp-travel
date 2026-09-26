@extends('adminlte::page')

@section('title', __('My Profile'))

@section('content_header')
    <h1>{{ __('My Profile') }}</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                @include('profile.partials.update-profile-information-form')
                @include('profile.partials.update-password-form')
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
@stop
