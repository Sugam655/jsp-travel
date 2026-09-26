<?php

namespace Modules\Bookings\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_id',
        'user_id',
        'method',
        'payment_type',
        'gateway',
        'gateway_response',
        'reference',
        'amount',
        'status',
        'recorded_by',
        'verified_by',
        'paid_at',
        'verified_at',
        'note',
        'receipt_path',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'booking_id' => 'integer',
        'user_id' => 'integer',
        'amount' => 'decimal:2',
        'recorded_by' => 'integer',
        'verified_by' => 'integer',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * The supported payment statuses.
     *
     * @var array<int, string>
     */
    public const STATUSES = ['pending', 'paid', 'failed'];

    /**
     * The purpose a payment settles. "Refund" rows keep the booking ledger in
     * sync when a payment is fully reversed.
     *
     * @var array<int, string>
     */
    public const TYPES = ['advance', 'partial', 'remaining', 'full', 'overpayment', 'refund'];

    /**
     * Human friendly labels for the payment types.
     *
     * @var array<string, string>
     */
    public const TYPE_LABELS = [
        'advance' => 'Advance',
        'partial' => 'Partial',
        'remaining' => 'Remaining',
        'full' => 'Full',
        'overpayment' => 'Overpayment',
        'refund' => 'Refund',
    ];

    /**
     * Human labels for the payment statuses.
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        'pending' => 'Pending Verification',
        'paid' => 'Verified',
        'failed' => 'Rejected',
    ];

    /**
     * The booking this payment belongs to.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * The customer account linked to this payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * The human friendly label for the payment status.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? Str::ucfirst($this->status);
    }

    /**
     * Bootstrap badge color for the payment status.
     */
    public function getStatusColorAttribute(): string
    {
        return [
            'pending' => 'text-bg-warning',
            'paid' => 'text-bg-success',
            'failed' => 'text-bg-danger',
        ][$this->status] ?? 'text-bg-secondary';
    }

    /**
     * The human friendly label for the payment type.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->payment_type] ?? Str::ucfirst($this->payment_type ?? 'partial');
    }

    /**
     * Bootstrap badge color for the payment type.
     */
    public function getTypeColorAttribute(): string
    {
        return [
            'advance' => 'text-bg-info',
            'partial' => 'text-bg-secondary',
            'remaining' => 'text-bg-primary',
            'full' => 'text-bg-dark',
            'overpayment' => 'text-bg-warning',
            'refund' => 'text-bg-danger',
        ][$this->payment_type] ?? 'text-bg-secondary';
    }

    /**
     * The formatted amount of this payment.
     */
    public function getAmountDisplayAttribute(): string
    {
        return ($this->booking?->currency ?? 'NPR').' '.number_format((float) $this->amount);
    }
}
