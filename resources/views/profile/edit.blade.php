{{-- The profile page is the one account view both roles share, so it lives in
     the same AdminLTE panel. Administrators additionally see the role card in
     profile.partials.update-profile-information-form, driven by isAdmin(). --}}
@extends('adminlte::page')

@section('title', __('My Profile'))

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ __('My Profile') }}</h1>
        <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('user.dashboard') }}"
            class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to Dashboard') }}
        </a>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            @include('profile.partials.update-profile-information-form')
            @include('profile.partials.update-password-form')
            @include('profile.partials.delete-user-form')
        </div>
    </div>
@stop