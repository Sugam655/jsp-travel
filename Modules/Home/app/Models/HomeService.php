<?php

namespace Modules\Home\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Home\Models\Concerns\ResolvesLegacyLink;

class HomeService extends Model
{
    use ResolvesLegacyLink;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'button_text',
        'button_url',
        'sort_order',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Default services promoted on the homepage when no active record exists.
     *
     * @var array<int, array<string, string|int|null>>
     */
    public const DEFAULTS = [
        [
            'title' => 'Nepal Countryside Tour',
            'subtitle' => 'with Dashboardstay',
            'image' => 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=1000&q=85',
            'button_text' => 'Learn More',
            'button_url' => 'destinations.html',
        ],
        [
            'title' => 'Discover exclusive luxury homes and sophisticated.',
            'subtitle' => null,
            'image' => 'https://www.hotellinksolutions.com/images/blog/cac-nguon-booking-khach-san.jpg',
            'button_text' => 'Learn More',
            'button_url' => 'hotel.html',
        ],
        [
            'title' => 'find the perfect match for your lifestyle.',
            'subtitle' => null,
            'image' => 'https://www.enterprise.com/en/exotic-car-rental/_jcr_content/root/container/container/container_1060086341/teaser.coreimg.jpeg/1775657159057/explore-our-vehicles-1920x1080-vehicles.jpeg',
            'button_text' => 'Learn More',
            'button_url' => 'transport.html',
        ],
        [
            'title' => 'We take you safely and comfortably to your destination.',
            'subtitle' => null,
            'image' => 'https://saigondaytrip.com/wp-content/uploads/saigon-airport-pickup.jpg',
            'button_text' => 'Learn More',
            'button_url' => 'transport.html',
        ],
        [
            'title' => 'Stylish and reliable cars for your special day.',
            'subtitle' => null,
            'image' => 'https://www.brides.com/thmb/KrT_2EjL7X2wXZyhee2z1uYtD4I=/1500x0/filters:no_upscale():max_bytes(150000):strip_icc()/_Weddingcardecoration-recirc_DavyWhitener-291af3a822104bd98dc1f6bfd851d9eb.jpg',
            'button_text' => 'Learn More',
            'button_url' => 'transport.html',
        ],
        [
            'title' => 'Quick and easy air ticket booking.',
            'subtitle' => null,
            'image' => 'https://img.magnific.com/free-photo/air-ticket-flight-booking-concept_53876-132674.jpg?semt=ais_hybrid&w=740&q=80',
            'button_text' => 'Learn More',
            'button_url' => 'booking.html',
        ],
    ];

    /**
     * Resolve the public URL for the service image.
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
     * The resolved call-to-action URL, translating legacy static links.
     */
    public function getButtonUrlAttribute(): string
    {
        return static::resolveLink($this->attributes['button_url'] ?? null);
    }
}
