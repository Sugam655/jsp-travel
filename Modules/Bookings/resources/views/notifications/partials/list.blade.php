{{-- Shared notification list, rendered in the AdminLTE panel by
     notifications/index.blade.php for both administrators and customers. --}}
<div class="mb-3">
    <div class="btn-group btn-group-sm" role="group">
        <a href="{{ route('notifications.index') }}"
            class="btn {{ $status === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">All</a>
        <a href="{{ route('notifications.index', ['status' => 'unread']) }}"
            class="btn {{ $status === 'unread' ? 'btn-primary' : 'btn-outline-secondary' }}">
            Unread @if ($unread > 0)
                <span class="badge text-bg-danger ms-1">{{ $unread }}</span>
            @endif
        </a>
        <a href="{{ route('notifications.index', ['status' => 'read']) }}"
            class="btn {{ $status === 'read' ? 'btn-primary' : 'btn-outline-secondary' }}">Read</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <ul class="list-unstyled mb-0">
            @forelse ($notifications as $notification)
                @php
                    $data = is_array($notification->data) ? $notification->data : [];
                    $url = $data['url'] ?? null;
                    $unread = $notification->read_at === null;
                    $href = $unread
                        ? route('notifications.read', $notification->getKey())
                        : ($url ?: '#');
                @endphp
                <li class="d-flex align-items-start gap-3 px-3 py-3 border-bottom {{ $unread ? 'bg-body-tertiary' : '' }}">
                    <div class="fs-5 mt-1">
                        <i class="fa-solid {{ \Modules\Bookings\Notifications\BookingNotification::iconFor($data) }} {{ $unread ? 'text-primary' : 'text-body-tertiary' }}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <a href="{{ $href }}" class="fw-semibold text-reset text-decoration-none">
                            {{ $data['title'] ?? 'Notification' }}
                        </a>
                        @if ($unread)
                            <span class="badge text-bg-danger rounded-pill ms-1">New</span>
                        @endif
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
