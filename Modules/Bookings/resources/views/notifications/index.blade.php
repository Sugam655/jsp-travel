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

    <div class="mb-3">
        <div class="btn-group btn-group-sm" role="group">
            <a href="{{ route('notifications.index') }}"
                class="btn {{ $status === 'all' ? 'btn-primary' : 'btn-default' }}">All</a>
            <a href="{{ route('notifications.index', ['status' => 'unread']) }}"
                class="btn {{ $status === 'unread' ? 'btn-primary' : 'btn-default' }}">
                Unread @if ($unread > 0)
                    <span class="badge text-bg-danger ms-1">{{ $unread }}</span>
                @endif
            </a>
            <a href="{{ route('notifications.index', ['status' => 'read']) }}"
                class="btn {{ $status === 'read' ? 'btn-primary' : 'btn-default' }}">Read</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <ul class="list-unstyled mb-0">
                @forelse ($notifications as $notification)
                    @php
                        $data = is_array($notification->data) ? $notification->data : [];
                        $url = $data['url'] ?? null;
                        $href = $notification->read_at === null
                            ? route('notifications.read', $notification->getKey())
                            : ($url ?: '#');
                    @endphp
                    <li class="d-flex align-items-start gap-3 px-3 py-3 border-bottom {{ $notification->read_at === null ? 'bg-body-tertiary' : '' }}">
                        <div class="fs-5 mt-1">
                            <i class="fa-solid {{ $notification->read_at === null ? 'fa-circle text-primary' : 'fa-circle text-body-tertiary' }}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <a href="{{ $href }}" class="fw-semibold text-reset text-decoration-none">
                                {{ $data['title'] ?? 'Notification' }}
                            </a>
                            <div class="text-muted small">{{ $data['message'] ?? '' }}</div>
                            @if (($data['reference'] ?? null) !== null)
                                <span class="badge text-bg-light border mt-1">{{ $data['reference'] }}</span>
                            @endif
                        </div>
                        <div class="text-muted small text-end" style="min-width: 90px;">
                            {{ $notification->created_at->diffForHumans() }}
                        </div>
                    </li>
                @empty
                    <li class="text-center text-muted py-5">
                        <i class="fa-regular fa-bell-slash fa-2x mb-2 d-block"></i>
                        No notifications found.
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
@stop