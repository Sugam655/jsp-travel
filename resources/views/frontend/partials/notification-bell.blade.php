{{--
    The navbar notification bell for a signed-in customer.

    Reuses the existing database notifications rather than a second mechanism: the
    unread count comes from the notifications table, each item links through
    notifications.read so opening it marks that row read, and the full history
    stays on the existing notification centre. Only unread rows are listed, so
    the dropdown always matches the badge and nothing is re-announced on refresh.

    Rendered server-side on every page render. The customer also gets the same
    update on the booking confirmation page, so nothing depends on this being open.
--}}
@php
    /** @var \App\Models\User $notificationUser */
    $notificationUser = auth()->user();
    $recentNotifications = $notificationUser->unreadNotifications()->take(5)->get();
    $unreadNotificationCount = $notificationUser->unreadNotifications()->count();
@endphp

@if ($recentNotifications->isNotEmpty())
    <div class="dropdown nav-notification-dropdown">
        <button class="nav-notification-bell" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
            aria-expanded="false" aria-label="{{ $unreadNotificationCount }} unread booking updates">
            <i class="bi bi-bell" aria-hidden="true"></i>
            <span class="nav-notification-badge">{{ $unreadNotificationCount }}</span>
        </button>

        <div class="dropdown-menu dropdown-menu-end nav-notification-menu">
            <div class="nav-notification-head">
                <span>Booking updates</span>
                <a href="{{ route('notifications.read-all') }}">Mark all read</a>
            </div>

            @foreach ($recentNotifications as $notification)
                @php
                    $data = is_array($notification->data) ? $notification->data : [];
                    // Going through notifications.read marks this exact row read,
                    // then forwards to the page the notification points at.
                    $href = route('notifications.read', $notification->getKey());
                @endphp
                <a class="nav-notification-item" href="{{ $href }}">
                    <span class="nav-notification-title">{{ $data['title'] ?? 'Booking update' }}</span>
                    <span class="nav-notification-message">{{ $data['message'] ?? '' }}</span>
                    <span class="nav-notification-time">{{ $notification->created_at->diffForHumans() }}</span>
                </a>
            @endforeach

            <div class="nav-notification-foot">
                <a href="{{ route('notifications.index') }}">View all notifications</a>
            </div>
        </div>
    </div>
@endif