<?php

namespace Modules\Bookings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Refund extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_id',
        'amount',
        'method',
        'reference',
        'status',
        'processed_by',
        'processed_at',
        'note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'booking_id' => 'integer',
        'amount' => 'decimal:2',
        'processed_by' => 'integer',
        'processed_at' => 'datetime',
    ];

    /**
     * The supported refund statuses.
     *
     * @var array<int, string>
     */
    public const STATUSES = ['pending', 'processed'];

    /**
     * Human labels for the refund statuses.
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        'pending' => 'Pending',
        'processed' => 'Processed',
    ];

    /**
     * The booking this refund belongs to.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * The human friendly label for the refund status.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? Str::ucfirst($this->status);
    }

    /**
     * Bootstrap badge color for the refund status.
     */
    public function getStatusColorAttribute(): string
    {
        return [
            'pending' => 'text-bg-warning',
            'processed' => 'text-bg-success',
        ][$this->status] ?? 'text-bg-secondary';
    }

    /**
     * The formatted amount of this refund.
     */
    public function getAmountDisplayAttribute(): string
    {
        return ($this->booking?->currency ?? 'NPR').' '.number_format((float) $this->amount);
    }
}
