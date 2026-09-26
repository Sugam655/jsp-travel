<?php

namespace Modules\Tours\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Home\Models\HomeDestination;

class Tour extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'destination_id',
        'location',
        'duration',
        'duration_days',
        'capacity',
        'price',
        'old_price',
        'description',
        'image',
        'featured',
        'sort_order',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'destination_id' => 'integer',
        'duration_days' => 'integer',
        'capacity' => 'integer',
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'featured' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Default tour packages promoted on the frontend when no active record
     * exists yet. Mirrors the original hardcoded frontend content.
     *
     * @var array<int, array<string, mixed>>
     */
    public const DEFAULTS = [
        [
            'title' => 'Khaptad National Park',
            'slug' => 'khaptad-national-park-tour',
            'location' => 'Far Western Nepal',
            'duration' => '5 Days / 4 Nights',
            'price' => 18999,
            'old_price' => 24999,
            'image' => 'https://www.everesttrekkers.com/uploads/posts/Khaptad-1726553851.png',
            'featured' => true,
            'sort_order' => 1,
        ],
        [
            'title' => 'Api Base Camp',
            'slug' => 'api-base-camp-tour',
            'location' => 'Darchula',
            'duration' => '4 Days / 3 Nights',
            'price' => 12999,
            'old_price' => 16499,
            'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRmV4x8HY1bHW0QmGRYz2AjxU5EjGi2HJlH5qhVf6073GCSPCQnS-J5Ivih&s=10',
            'featured' => true,
            'sort_order' => 2,
        ],
        [
            'title' => 'Badi Malika',
            'slug' => 'badi-malika-tour',
            'location' => 'Bajura',
            'duration' => '6 Days / 5 Nights',
            'price' => 16999,
            'old_price' => 21499,
            'image' => 'https://tourisminfonepal.com/wp-content/uploads/2026/06/Badimalika-1.webp',
            'featured' => false,
            'sort_order' => 3,
        ],
        [
            'title' => 'Far Western Nepal',
            'slug' => 'far-western-nepal-tour',
            'location' => 'Far Western Nepal',
            'duration' => '4 Days / 3 Nights',
            'price' => 14999,
            'old_price' => 18000,
            'image' => 'https://www.dntt.com.np/uploads/ecategory/00252100.jpg',
            'featured' => false,
            'sort_order' => 4,
        ],
        [
            'title' => 'Rama Rosan',
            'slug' => 'rama-rosan-tour',
            'location' => 'Achham',
            'duration' => '4 Days / 3 Nights',
            'price' => 24999,
            'old_price' => 29999,
            'image' => 'https://www.wondersofnepal.com/wp-content/uploads/2020/07/DsW4uynWsAADNy0-1024x768.jpg',
            'featured' => false,
            'sort_order' => 5,
        ],
        [
            'title' => 'Shuklaphanta Wildlife Reserve',
            'slug' => 'shuklaphanta-wildlife-reserve-tour',
            'location' => 'Kanchanpur',
            'duration' => '5 Days / 4 Nights',
            'price' => 28999,
            'old_price' => 36999,
            'image' => 'https://tigerencounter.com/wp-content/uploads/2019/12/Suklaphanta-National-Park.jpg',
            'featured' => false,
            'sort_order' => 6,
        ],
    ];

    /**
     * Query scope that only includes active tours.
     */
    public function scopeActive($query): void
    {
        $query->where('is_active', true);
    }

    /**
     * The destination this tour belongs to (existing Home destination).
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(HomeDestination::class);
    }

    /**
     * Resolve the public URL for the tour image.
     *
     * Supports both external URLs (kept untouched) and files stored on the
     * public disk (relative path). Returns null when no image is set.
     */
    public function getImageUrlAttribute(): ?string
    {
        $image = $this->image;

        if (blank($image)) {
            return null;
        }

        if (Str::startsWith($image, ['http://', 'https://', '//'])) {
            return $image;
        }

        return Storage::disk('public')->url($image);
    }

    /**
     * Whether the stored image points to a locally uploaded file.
     */
    public function hasUploadedImage(): bool
    {
        return filled($this->image)
            && ! Str::startsWith($this->image, ['http://', 'https://', '//']);
    }

    /**
     * The formatted discount percentage shown on package cards.
     */
    public function getDiscountPercentAttribute(): ?int
    {
        if (
            blank($this->old_price)
            || $this->old_price <= 0
            || $this->price >= $this->old_price
        ) {
            return null;
        }

        return (int) round((($this->old_price - $this->price) / $this->old_price) * 100);
    }

    /**
     * The human label shown for the tour location: the related destination
     * name when linked, otherwise the free-text location.
     */
    public function getLocationLabelAttribute(): ?string
    {
        return $this->destination?->name ?? $this->location;
    }
}
