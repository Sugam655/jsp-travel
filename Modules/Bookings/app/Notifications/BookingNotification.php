<?php

namespace Modules\Bookings\Notifications;

use Illuminate\Notifications\Notification;

/**
 * In-app booking notification delivered to a customer account.
 *
 * Guests who book without an account receive the same details on the booking
 * page and (when the app mail transport is configured) on email.
 */
class BookingNotification extends Notification
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        protected string $title,
        protected string $message,
        protected array $payload = [],
        protected ?string $url = null
    ) {}

    /**
     * The notification delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * The notification title shown in the customer area.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'payload' => $this->payload,
            'url' => $this->url,
            'reference' => $this->payload['booking_reference'] ?? null,
        ];
    }
}
