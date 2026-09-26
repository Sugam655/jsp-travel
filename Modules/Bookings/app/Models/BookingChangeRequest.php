<?php

namespace Modules\Bookings\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BookingChangeRequest extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_id',
        'type',
        'requested',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'response_note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'booking_id' => 'integer',
        'requested' => 'array',
        'reviewed_by' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    /**
     * The supported change request types.
     *
     * @var array<int, string>
     */
    public const TYPES = [
        'dates',
        'travelers',
        'service',
        'other',
    ];

    /**
     * Human labels for the change request types.
     *
     * @var array<string, string>
     */
    public const TYPE_LABELS = [
        'dates' => 'Dates',
        'travelers' => 'Number of Guests',
        'service' => 'Service / Room / Vehicle',
        'other' => 'Other',
    ];

    /**
     * The supported review statuses.
     *
     * @var array<int, string>
     */
    public const STATUSES = ['pending', 'approved', 'rejected'];

    /**
     * Human labels for the change request statuses.
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    /**
     * The booking this change request belongs to.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * The staff member who reviewed the request.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * The human friendly label for the change request type.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? Str::ucfirst($this->type);
    }

    /**
     * The human friendly label for the review status.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? Str::ucfirst($this->status);
    }

    /**
     * Bootstrap badge color for the review status.
     */
    public function getStatusColorAttribute(): string
    {
        return [
            'pending' => 'text-bg-warning',
            'approved' => 'text-bg-success',
            'rejected' => 'text-bg-danger',
        ][$this->status] ?? 'text-bg-secondary';
    }
}
