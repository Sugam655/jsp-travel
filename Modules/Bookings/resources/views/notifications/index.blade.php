{{-- The notification centre. Administrators and customers both live in the
     AdminLTE panel, so there is a single shell here; the list itself is a shared
     partial and NotificationsController only ever loads the signed-in user's
     own rows. --}}
@extends('adminlte::page')

@section('title', 'Notifications')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Notifications</h1>
        @if ($unread > 0)
            <a href="{{ route('notifications.read-all') }}" class="btn btn-sm btn-outline-primary">
                <i class="fa-solid fa-check-double me-1"></i> Mark all as read
            </a>
        @endif
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @include('bookings::notifications.partials.list')
@stop