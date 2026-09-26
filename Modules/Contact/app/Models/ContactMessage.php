<?php

namespace Modules\Contact\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContactMessage extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'admin_note',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Available message status values.
     *
     * @var array<int, string>
     */
    public const STATUSES = [
        'new',
        'read',
        'replied',
        'archived',
    ];

    /**
     * Human friendly labels for the message status values.
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        'new' => 'New',
        'read' => 'Read',
        'replied' => 'Replied',
        'archived' => 'Archived',
    ];

    /**
     * Bootstrap badge color for each status value.
     *
     * @var array<string, string>
     */
    public const STATUS_COLORS = [
        'new' => 'text-bg-primary',
        'read' => 'text-bg-info',
        'replied' => 'text-bg-success',
        'archived' => 'text-bg-secondary',
    ];

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
}
