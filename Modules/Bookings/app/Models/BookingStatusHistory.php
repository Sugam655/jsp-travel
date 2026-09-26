<?php

namespace Modules\Bookings\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BookingStatusHistory extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'booking_status_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_id',
        'action',
        'from_status',
        'to_status',
        'performed_by',
        'performed_role',
        'note',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'booking_id' => 'integer',
        'performed_by' => 'integer',
        'meta' => 'array',
    ];

    /**
     * The booking this history entry belongs to.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * The user who performed the action, when performed by an authenticated user.
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * The human friendly label for the recorded action.
     */
    public function getActionLabelAttribute(): string
    {
        return [
            'created' => 'Booking requested',
            'availability_checked' => 'Availability checked',
            'quoted' => 'Price calculated',
            'confirmed' => 'Booking confirmed',
            'rejected' => 'Booking rejected',
            'reviewed' => 'Booking reviewed',
            'payment_requested' => 'Payment requested',
            'payment_received' => 'Payment received',
            'payment_pending' => 'Payment recorded (pending verification)',
            'payment_failed' => 'Payment failed',
            'completed' => 'Service completed',
            'cancelled_by_customer' => 'Cancelled by customer',
            'cancelled_by_admin' => 'Cancelled by admin',
            'cancel_requested' => 'Cancellation requested',
            'refund_calculated' => 'Refund calculated',
            'refund_pending' => 'Refund pending',
            'refund_processed' => 'Refund processed',
            'expired' => 'Booking expired',
            'change_requested' => 'Change request raised',
            'change_approved' => 'Change request approved',
            'change_rejected' => 'Change request rejected',
            'note_added' => 'Admin note added',
        ][$this->action] ?? Str::ucfirst(str_replace('_', ' ', $this->action));
    }
}
