@php
    /** @var \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Notifications\DatabaseNotification> $items */
@endphp

@forelse ($items as $notification)
    @php
        $data = is_array($notification->data) ? $notification->data : [];
        $url = $data['url'] ?? null;
        $unread = $notification->read_at === null;
        $href = $unread && $url
            ? route('notifications.read', $notification->getKey())
            : ($url ?: '#');
    @endphp
    <a href="{{ $href }}"
        class="dropdown-item {{ $unread ? 'bg-body-tertiary' : '' }}">
        <i class="fa-solid {{ \Modules\Bookings\Notifications\BookingNotification::iconFor($data) }} {{ $unread ? 'text-primary' : 'text-body-tertiary' }} me-2"></i>
        <strong>{{ $data['title'] ?? 'Notification' }}</strong>
        @if ($unread)
            <span class="badge text-bg-danger rounded-pill ms-1">New</span>
        @endif
        <span class="d-block text-truncate text-muted">{{ $data['message'] ?? '' }}</span>
        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
    </a>
    <div class="dropdown-divider"></div>
@empty
    <span class="dropdown-item text-muted"><i class="fa-regular fa-bell-slash me-2"></i>No new notifications</span>
@endforelse