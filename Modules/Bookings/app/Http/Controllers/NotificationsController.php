<?php

namespace Modules\Bookings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

/**
 * The in-app notification centre shared by customers and administrators.
 *
 * Notifications are Laravel database notifications stored on the
 * notifications table and delivered through BookingNotification. The navbar
 * bell polls the data endpoint for the unread badge and a short dropdown, and
 * the index page lists the full history.
 */
class NotificationsController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');

        $notifications = $request->user()
            ->notifications()
            ->when($status === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($status === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->orderByDesc('created_at')
            ->latest()
            ->get();

        return view('bookings::notifications.index', [
            'notifications' => $notifications,
            'status' => $status,
            'unread' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * The payload consumed by the AdminLTE navbar notification widget.
     *
     * Only the notifications still unread are shown in the dropdown, so it
     * reflects exactly the badge count. Read notifications disappear from the
     * list as soon as this endpoint is polled again.
     *
     * @return array{label: int, label_color: string, dropdown: string}
     */
    public function data(Request $request): JsonResponse
    {
        $unread = $request->user()->unreadNotifications()->count();
        $recent = $request->user()->unreadNotifications()->take(10)->get();

        return response()->json([
            'label' => $unread,
            'label_color' => $unread > 0 ? 'danger' : 'light',
            'dropdown' => view('bookings::notifications.partials.dropdown', ['items' => $recent])->render(),
        ]);
    }

    /**
     * Mark a single notification as read and open its linked page.
     */
    public function read(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->find($id);

        abort_unless($notification instanceof DatabaseNotification, 404, 'Notification not found.');

        $notification->markAsRead();

        return redirect($this->resolveDestination($request->user(), $notification));
    }

    /**
     * Mark every notification of the authenticated user as read.
     */
    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->route('notifications.index');
    }

    /**
     * The exact working area a notification points to, resolved from the
     * notification type and the related record ids captured when it was
     * created. Admin routes are only resolved for administrators, so a
     * customer can never be steered to admin management pages.
     */
    private function resolveDestination(User $user, DatabaseNotification $notification): string
    {
        $data = is_array($notification->data) ? $notification->data : [];
        $payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];
        $type = $payload['type'] ?? null;

        if ($user->isAdmin()) {
            if ($type === 'change_request' && isset($payload['change_request_id'])) {
                return route('admin.bookings.change-requests.index').'#change-request-'.(int) $payload['change_request_id'];
            }

            if ($type === 'payment' && isset($payload['payment_id'])) {
                return route('admin.payments.show', ['payment' => (int) $payload['payment_id']]);
            }
        }

        return is_string($data['url'] ?? null) ? $data['url'] : route('notifications.index');
    }
}
