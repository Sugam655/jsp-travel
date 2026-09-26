<?php

namespace Modules\Home\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Hotels\Models\Hotel;
use Modules\Tours\Models\Tour;
use Modules\Transport\Models\TransportVehicle;

class HomeDestination extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'badge',
        'image',
        'location',
        'price',
        'latitude',
        'longitude',
        'sort_order',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Default destination cards promoted on the homepage when no
     * active record exists yet.
     *
     * @var array<int, array<string, string|int|null>>
     */
    public const DEFAULTS = [
        [
            'name' => 'khaptad National Park',
            'badge' => 'Popular',
            'image' => 'https://res.klook.com/images/w_1200,h_630,c_fill,q_65/w_80,x_15,y_15,g_south_west,l_Klook_water_br_trans_yhcmh3/activities/amxjvq3tdrghaye1advf/Discover%20Untamed%20Beauty%3A%20Khaptad%20National%20Park%20Trekking%20Adventure.jpg',
            'location' => 'khaptad',
            'price' => 'Rs.699',
            'latitude' => 29.2755,
            'longitude' => 81.1440,
        ],
        [
            'name' => 'Badi Malika',
            'badge' => 'Popular',
            'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRJNFWPp_-CHEVnlsNhlG_wmRb7_fA9ZCUG4sQvARTHDy0ezGID00FmyGI&s=10',
            'location' => 'Bajura',
            'price' => 'Rs.799',
            'latitude' => 29.3436,
            'longitude' => 81.4228,
        ],
        [
            'name' => 'Api Base Camp',
            'badge' => 'Trending',
            'image' => 'https://media.app.himalayanecstasynepal.com/uploads/media/api-himal-base-camp-trek/api-himal-base-camp.jpg',
            'location' => 'Darchula',
            'price' => 'Rs.599',
            'latitude' => 29.8497,
            'longitude' => 80.5331,
        ],
        [
            'name' => 'Rama Rosan',
            'badge' => 'Popular',
            'image' => 'https://nepaltraveller.com/uploads/destination/the-natural-beauty-of-ramaroshan.jpg',
            'location' => 'Achham',
            'price' => 'Rs.899',
            'latitude' => 28.8737,
            'longitude' => 81.5049,
        ],
    ];

    /**
     * Resolve the public URL for the destination image.
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
     * The tours linked to this destination.
     */
    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class, 'destination_id');
    }

    /**
     * The hotels located in this destination.
     */
    public function hotels(): HasMany
    {
        return $this->hasMany(Hotel::class, 'destination_id');
    }

    /**
     * The vehicles serving this destination.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(TransportVehicle::class, 'destination_id');
    }

    /**
     * Stored coordinates as ['lat', 'lng'], or null when not set.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function getCoordinatesAttribute(): ?array
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return ['lat' => (float) $this->latitude, 'lng' => (float) $this->longitude];
    }
}
