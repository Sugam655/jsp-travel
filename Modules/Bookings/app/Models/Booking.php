<?php

namespace Modules\Bookings\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Modules\Bookings\Services\DiscountResolver;

class Booking extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_reference',
        'user_id',
        'booking_type',
        'service_id',
        'service_title',
        'name',
        'email',
        'phone',
        'address',
        'travelers',
        'start_date',
        'end_date',
        'quantity',
        'amount',
        'currency',
        'base_price',
        'tax_rate',
        'service_charge_rate',
        'tax_amount',
        'service_charge',
        'discount',
        'discount_type',
        'discount_value',
        'discount_label',
        'total_amount',
        'advance_amount',
        'payment_due_date',
        'paid_amount',
        'policy_accepted_at',
        'expires_at',
        'reviewed_by',
        'reviewed_at',
        'cancelled_by',
        'cancelled_reason',
        'cancelled_at',
        'cancellation_fee',
        'refund_amount',
        'policy_snapshot',
        'message',
        'status',
        'admin_note',
        'source',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'service_id' => 'integer',
        'travelers' => 'integer',
        'quantity' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'decimal:2',
        'base_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'service_charge_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'discount' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'advance_amount' => 'decimal:2',
        'payment_due_date' => 'date',
        'paid_amount' => 'decimal:2',
        'cancellation_fee' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'policy_snapshot' => 'array',
        'policy_accepted_at' => 'datetime',
        'expires_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * The supported bookable services.
     *
     * @var array<int, string>
     */
    public const TYPES = ['tour', 'hotel', 'vehicle'];

    /**
     * Human friendly labels for the bookable service types.
     *
     * @var array<string, string>
     */
    public const TYPE_LABELS = [
        'tour' => 'Tour Package',
        'hotel' => 'Hotel Room',
        'vehicle' => 'Vehicle Rental',
    ];

    /**
     * The supported booking lifecycle statuses.
     *
     * @var array<int, string>
     */
    public const STATUSES = [
        'pending',
        'confirmed',
        'payment_pending',
        'paid',
        'completed',
        'cancelled',
        'rejected',
        'expired',
    ];

    /**
     * Human friendly labels for the booking statuses.
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'payment_pending' => 'Payment Pending',
        'paid' => 'Paid',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'rejected' => 'Rejected',
        'expired' => 'Expired',
    ];

    /**
     * Bootstrap badge color for each booking status.
     *
     * @var array<string, string>
     */
    public const STATUS_COLORS = [
        'pending' => 'text-bg-warning',
        'confirmed' => 'text-bg-info',
        'payment_pending' => 'text-bg-primary',
        'paid' => 'text-bg-success',
        'completed' => 'text-bg-dark',
        'cancelled' => 'text-bg-secondary',
        'rejected' => 'text-bg-danger',
        'expired' => 'text-bg-secondary',
    ];

    /**
     * Every status that still holds inventory (occupies the service period).
     *
     * @var array<int, string>
     */
    public const RESERVING_STATUSES = [
        'pending',
        'confirmed',
        'payment_pending',
        'paid',
    ];

    /**
     * Statuses that no longer occupy inventory.
     *
     * @var array<int, string>
     */
    public const RELEASED_STATUSES = [
        'cancelled',
        'rejected',
        'expired',
        'completed',
    ];

    /**
     * The supported booking sources.
     *
     * @var array<int, string>
     */
    public const SOURCES = ['web', 'admin'];

    /**
     * Valid transition map: status => set of statuses it may move into.
     *
     * @var array<string, array<int, string>>
     */
    public const TRANSITIONS = [
        'pending' => ['confirmed', 'rejected', 'cancelled', 'expired'],
        'confirmed' => ['payment_pending', 'paid', 'cancelled', 'completed'],
        'payment_pending' => ['paid', 'cancelled', 'expired'],
        'paid' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
        'rejected' => [],
        'expired' => [],
    ];

    /**
     * Query scope that only includes active (reserving) bookings.
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', self::RESERVING_STATUSES);
    }

    /**
     * Query scope that only includes pending bookings awaiting review.
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }

    /**
     * Query scope that only includes bookings that require payment.
     */
    public function scopePaymentPending(Builder $query): void
    {
        $query->where('status', 'payment_pending');
    }

    /**
     * Query scope that only includes paid bookings.
     */
    public function scopePaid(Builder $query): void
    {
        $query->where('status', 'paid');
    }

    /**
     * Query scope that only includes cancelled bookings.
     */
    public function scopeCancelled(Builder $query): void
    {
        $query->where('status', 'cancelled');
    }

    /**
     * The customer account that submitted the booking, when available.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The read-only lifecycle audit trail.
     */
    public function history(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class)->orderByDesc('id');
    }

    /**
     * The payment attempts for this booking.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderByDesc('id');
    }

    /**
     * The payments that actually cleared (verified) for this booking.
     */
    public function successfulPayments(): HasMany
    {
        return $this->payments()->where('status', 'paid');
    }

    /**
     * The refunds issued against this booking.
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class)->orderByDesc('id');
    }

    /**
     * The change requests raised for this booking.
     */
    public function changeRequests(): HasMany
    {
        return $this->hasMany(BookingChangeRequest::class)->orderByDesc('id');
    }

    /**
     * The staff member who reviewed the booking.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * In-app notifications delivered to the customer account.
     *
     * @return MorphMany<DatabaseNotification, $this>
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable');
    }

    /**
     * The human friendly label for the current status.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? Str::ucfirst($this->status);
    }

    /**
     * The Bootstrap badge color class for the current status.
     */
    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'text-bg-secondary';
    }

    /**
     * The human friendly label for the booked service type.
     */
    public function getBookingTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->booking_type] ?? Str::ucfirst($this->booking_type);
    }

    /**
     * The human friendly label for the booking source.
     */
    public function getSourceLabelAttribute(): string
    {
        return $this->source === 'admin' ? 'Admin' : 'Website';
    }

    /**
     * The formatted total amount shown in tables and cards.
     */
    public function getAmountDisplayAttribute(): string
    {
        if ($this->total_amount === null) {
            return '&mdash;';
        }

        return ($this->currency ?? 'NPR').' '.number_format((float) $this->total_amount);
    }

    /**
     * Whether a discount was actually taken off this booking.
     */
    public function getHasDiscountAttribute(): bool
    {
        return (float) ($this->discount ?? 0) > 0;
    }

    /**
     * How this booking's discount was described, read from the snapshot stored
     * on the booking rather than from today's settings.
     */
    public function getDiscountDisplayAttribute(): string
    {
        if (! $this->has_discount) {
            return 'Discount';
        }

        return 'Discount ('.DiscountResolver::describe(
            $this->discount_type,
            $this->discount_value,
            $this->discount_label,
            (string) ($this->currency ?? 'NPR')
        ).')';
    }

    /**
     * The formatted amount already paid by the customer.
     */
    public function getPaidDisplayAttribute(): string
    {
        return ($this->currency ?? 'NPR').' '.number_format($this->settledAmount());
    }

    /**
     * The formatted outstanding amount.
     */
    public function getDueDisplayAttribute(): string
    {
        return ($this->currency ?? 'NPR').' '.number_format($this->dueAmount());
    }

    /**
     * Composite payment status derived from the payment/refund ledger.
     */
    public function getPaymentStatusAttribute(): string
    {
        $total = (float) ($this->total_amount ?? 0);
        $committed = (float) $this->payments()->where('status', 'paid')->sum('amount');
        $refunded = (float) $this->refundedAmount();
        $net = max(0.0, $committed - $refunded);

        if ($total <= 0) {
            return $this->payments()->where('status', 'pending')->exists()
                ? 'payment_pending'
                : 'unpaid';
        }

        if (($committed > 0 || $this->status === 'cancelled')
            && $this->refunds()->where('status', 'pending')->exists()) {
            return 'refund_pending';
        }

        if ($committed > 0 && $refunded >= $committed - 0.005) {
            return 'refunded';
        }

        if ($net > $total + 0.005) {
            return 'overpaid';
        }

        if ($net >= $total - 0.005 && $net > 0) {
            return 'paid';
        }

        if ($net > 0) {
            return 'partially_paid';
        }

        if ($this->payments()->where('status', 'pending')->exists()) {
            return 'payment_pending';
        }

        return 'unpaid';
    }

    /**
     * Human friendly label for the composite payment status.
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return [
            'unpaid' => 'Unpaid',
            'payment_pending' => 'Payment Pending',
            'partially_paid' => 'Partially Paid',
            'paid' => 'Paid',
            'overpaid' => 'Overpaid',
            'refunded' => 'Refunded',
            'refund_pending' => 'Refund Pending',
        ][$this->payment_status] ?? Str::ucfirst($this->payment_status);
    }

    /**
     * Bootstrap badge color for the composite payment status.
     */
    public function getPaymentStatusColorAttribute(): string
    {
        return [
            'unpaid' => 'text-bg-light border',
            'payment_pending' => 'text-bg-warning',
            'partially_paid' => 'text-bg-info',
            'paid' => 'text-bg-success',
            'overpaid' => 'text-bg-warning',
            'refunded' => 'text-bg-secondary',
            'refund_pending' => 'text-bg-warning',
        ][$this->payment_status] ?? 'text-bg-secondary';
    }

    /**
     * The formatted advance amount required for this booking.
     */
    public function getAdvanceDisplayAttribute(): string
    {
        return ($this->currency ?? 'NPR').' '.number_format((float) ($this->advance_amount ?? 0));
    }

    /**
     * The formatted remaining-balance due date.
     */
    public function getPaymentDueDisplayAttribute(): string
    {
        return $this->payment_due_date?->format('M d, Y') ?? '&mdash;';
    }

    /**
     * The remaining amount the customer still owes.
     */
    public function dueAmount(): float
    {
        return max(0.0, round((float) ($this->total_amount ?? 0) - $this->settledAmount(), 2));
    }

    public function settledAmount(): float
    {
        $paid = (float) $this->payments()->where('status', 'paid')->sum('amount');
        $refunded = (float) $this->refundedAmount();

        return max(0.0, round($paid - $refunded, 2));
    }

    /**
     * The total amount already refunded for this booking.
     */
    public function refundedAmount(): float
    {
        return (float) $this->refunds()->where('status', 'processed')->sum('amount');
    }

    /**
     * The admin edit URL for the related service, when one exists.
     */
    public function getServiceAdminUrlAttribute(): ?string
    {
        if ($this->service_id === null) {
            return null;
        }

        return match ($this->booking_type) {
            'tour' => route('admin.tours.edit', $this->service_id),
            'hotel' => route('admin.hotels.edit', $this->service_id),
            'vehicle' => route('admin.transport.edit', $this->service_id),
            default => null,
        };
    }

    /**
     * Whether this booking currently holds inventory for its service period.
     */
    public function isReserving(): bool
    {
        return in_array($this->status, self::RESERVING_STATUSES, true);
    }

    /**
     * Whether the customer may already cancel this booking.
     */
    public function isCustomerCancellable(): bool
    {
        if (! in_array($this->status, ['pending', 'confirmed', 'payment_pending', 'paid'], true)) {
            return false;
        }

        return $this->start_date !== null && ! $this->start_date->isPast();
    }

    /**
     * Whether the admin may cancel this booking.
     */
    public function isAdminCancellable(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'payment_pending', 'paid'], true);
    }

    /**
     * Whether a change request may still be raised for this booking.
     */
    public function canBeChanged(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'payment_pending', 'paid'], true);
    }

    /**
     * Whether the given transition is allowed for this booking.
     */
    public function canTransitionTo(string $to): bool
    {
        if ($this->status === $to) {
            return false;
        }

        return in_array($to, self::TRANSITIONS[$this->status] ?? [], true);
    }

    /**
     * Whether this transition is allowed without considering the current row.
     */
    public static function allowsTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /**
     * The contextual actions an administrator can take, per status.
     *
     * @return array<int, array{action: string, label: string, icon: string, color: string}>
     */
    public function adminActions(): array
    {
        $actions = [];

        if ($this->status === 'pending') {
            $actions[] = ['action' => 'confirm', 'label' => 'Confirm', 'icon' => 'fa-solid fa-check', 'color' => 'btn-success'];
            $actions[] = ['action' => 'reject', 'label' => 'Reject', 'icon' => 'fa-solid fa-xmark', 'color' => 'btn-danger'];
        }

        if ($this->status === 'confirmed' && $this->total_amount !== null && (float) $this->total_amount > 0) {
            $actions[] = ['action' => 'request-payment', 'label' => 'Request Payment', 'icon' => 'fa-solid fa-money-bill-wave', 'color' => 'btn-primary'];
        }

        if (in_array($this->status, ['confirmed', 'payment_pending'], true)
            && $this->payments()->where('status', 'pending')->exists()) {
            $actions[] = ['action' => 'mark-paid', 'label' => 'Review Pending Payment', 'icon' => 'fa-solid fa-circle-check', 'color' => 'btn-success'];
        }

        if (in_array($this->status, ['pending', 'confirmed', 'payment_pending'], true)
            && ($this->total_amount === null || (float) $this->total_amount <= 0)) {
            $actions[] = ['action' => 'set-price', 'label' => 'Set Price', 'icon' => 'fa-solid fa-sack-dollar', 'color' => 'btn-warning'];
        }

        if ($this->status === 'paid') {
            $actions[] = ['action' => 'complete', 'label' => 'Mark Completed', 'icon' => 'fa-solid fa-flag-checkered', 'color' => 'btn-success'];
        }

        if ($this->isAdminCancellable()) {
            $actions[] = ['action' => 'cancel', 'label' => 'Cancel Booking', 'icon' => 'fa-solid fa-ban', 'color' => 'btn-danger'];
        }

        // Only the cancellation path issues refunds, so a refund is actionable
        // only once the booking is cancelled and a pending refund exists. A
        // "refund" action here had no route and rendered a dead button.
        if ($this->status === 'cancelled' && $this->refunds()->where('status', 'pending')->exists()) {
            $actions[] = ['action' => 'process-refund', 'label' => 'Process Refund', 'icon' => 'fa-solid fa-rotate-left', 'color' => 'btn-warning'];
        }

        return $actions;
    }
}
