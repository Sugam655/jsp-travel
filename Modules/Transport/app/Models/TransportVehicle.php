<?php

namespace Modules\Transport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Home\Models\HomeDestination;

class TransportVehicle extends Model
{
    /**
     * The attribute names allowed for mass assignment.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'brand',
        'model',
        'vehicle_type',
        'destination_id',
        'location',
        'seating_capacity',
        'price',
        'price_unit',
        'year',
        'transmission',
        'short_description',
        'description',
        'features',
        'image',
        'availability',
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
        'seating_capacity' => 'integer',
        'price' => 'decimal:2',
        'year' => 'integer',
        'availability' => 'boolean',
        'featured' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * The supported vehicle types.
     *
     * @var array<int, string>
     */
    public const TYPES = ['car', 'jeep', 'van', 'bus', 'bike', 'suv', 'other'];

    /**
     * Human labels for the vehicle types.
     *
     * @var array<string, string>
     */
    public const TYPE_LABELS = [
        'car' => 'Car',
        'jeep' => 'Jeep',
        'van' => 'Van',
        'bus' => 'Bus',
        'bike' => 'Bike',
        'suv' => 'SUV',
        'other' => 'Other',
    ];

    /**
     * The supported pricing units.
     *
     * @var array<int, string>
     */
    public const PRICE_UNITS = ['per_day', 'per_trip', 'per_hour', 'contact'];

    /**
     * Human labels for the pricing units.
     *
     * @var array<string, string>
     */
    public const PRICE_UNIT_LABELS = [
        'per_day' => 'Per Day',
        'per_trip' => 'Per Trip',
        'per_hour' => 'Per Hour',
        'contact' => 'Contact for Price',
    ];

    /**
     * Default vehicles promoted on the public transport page when no active
     * record exists yet.
     *
     * @var array<int, array<string, mixed>>
     */
    public const DEFAULTS = [
        [
            'name' => 'Suzuki Ertiga',
            'slug' => 'suzuki-ertiga',
            'brand' => 'Suzuki',
            'model' => 'Ertiga',
            'vehicle_type' => 'car',
            'location' => 'Dhangadhi',
            'seating_capacity' => 7,
            'price' => 7500,
            'price_unit' => 'per_day',
            'year' => 2023,
            'transmission' => 'Manual',
            'short_description' => 'A compact seven-seater MPV, ideal for families and small groups exploring the Far West.',
            'description' => 'The Suzuki Ertiga is our trusted everyday rental for families and small groups. With seven comfortable seats, generous boot space and smooth highway manners, it is perfect for town errands, pilgrimages and shorter out-of-station trips in and around Dhangadhi.',
            'features' => "Air conditioning\n7 seats\nFree driver available\nFull-day rental",
            'image' => 'https://images.unsplash.com/photo-1542362567-b07e54358753?w=600&q=80',
            'availability' => true,
            'featured' => true,
            'sort_order' => 1,
        ],
        [
            'name' => 'Mahindra Scorpio',
            'slug' => 'mahindra-scorpio',
            'brand' => 'Mahindra',
            'model' => 'Scorpio',
            'vehicle_type' => 'suv',
            'location' => 'Dhangadhi',
            'seating_capacity' => 7,
            'price' => 9500,
            'price_unit' => 'per_day',
            'year' => 2023,
            'transmission' => 'Manual',
            'short_description' => 'A rugged, spacious SUV for family trips and highland roads across Kailali and Kanchanpur.',
            'description' => 'The Mahindra Scorpio delivers commanding presence and comfort on every kind of road. With seven seats, strong AC cooling and a proven diesel engine, it handles the long east-west highway stretches, hill climbs and off-road tracks to Khaptad with total ease.',
            'features' => "4x2 drive\n7 seats\nAir conditioning\nExperienced local driver",
            'image' => 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=600&q=80',
            'availability' => true,
            'featured' => true,
            'sort_order' => 2,
        ],
        [
            'name' => 'Toyota Land Cruiser',
            'slug' => 'toyota-land-cruiser',
            'brand' => 'Toyota',
            'model' => 'Land Cruiser',
            'vehicle_type' => 'jeep',
            'location' => 'Dhangadhi',
            'seating_capacity' => 9,
            'price' => 18000,
            'price_unit' => 'per_day',
            'year' => 2022,
            'transmission' => 'Automatic',
            'short_description' => 'The legendary 4WD workhorse for Khaptad, reservoir trips and true off-road adventures.',
            'description' => 'The Toyota Land Cruiser is the ultimate excursion vehicle for Far West Nepal. Its permanent four-wheel drive, high ground clearance and legendary reliability make it the first choice for mountain road trips, jungle safaris to Shuklaphanta and remote highland destinations.',
            'features' => "4WD permanent\n9 seats\nSafari-ready\nDriver-guide available",
            'image' => 'https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?w=600&q=80',
            'availability' => true,
            'featured' => true,
            'sort_order' => 3,
        ],
        [
            'name' => 'Force Traveller',
            'slug' => 'force-traveller',
            'brand' => 'Force',
            'model' => 'Traveller',
            'vehicle_type' => 'van',
            'location' => 'Mahendranagar',
            'seating_capacity' => 12,
            'price' => 12000,
            'price_unit' => 'per_day',
            'year' => 2022,
            'transmission' => 'Manual',
            'short_description' => 'A spacious 12-seater van for group tours, family gatherings and event outings.',
            'description' => 'The Force Traveller seats twelve travellers in comfort and is our go-to vehicle for group tours, wedding parties and family picnics. High van-style seating gives everyone a great view, and there is ample room for luggage in the rear.',
            'features' => "12 seats\nLuggage space\nAir conditioned cabin\nSuitable for groups",
            'image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&q=80',
            'availability' => true,
            'featured' => false,
            'sort_order' => 4,
        ],
        [
            'name' => 'Ashok Leyland Falcon',
            'slug' => 'ashok-leyland-falcon',
            'brand' => 'Ashok Leyland',
            'model' => 'Falcon',
            'vehicle_type' => 'bus',
            'location' => 'Mahendranagar',
            'seating_capacity' => 32,
            'price' => 28000,
            'price_unit' => 'per_trip',
            'year' => 2021,
            'transmission' => 'Manual',
            'short_description' => 'A full-size 32-seater coach for pilgrimages, school trips and large group charters.',
            'description' => 'The Ashok Leyland Falcon is a comfortable 32-seater coach built for long group journeys. Air-conditioned seating, generous legroom and a trained driver make it ideal for pilgrimages, student excursions and corporate charters across Nepal.',
            'features' => "32 seats\nAir conditioning\nOn-board entertainment\nCustom charters welcome",
            'image' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=600&q=80',
            'availability' => true,
            'featured' => false,
            'sort_order' => 5,
        ],
        [
            'name' => 'Royal Enfield Himalayan',
            'slug' => 'royal-enfield-himalayan',
            'brand' => 'Royal Enfield',
            'model' => 'Himalayan',
            'vehicle_type' => 'bike',
            'location' => 'Dhangadhi',
            'seating_capacity' => 2,
            'price' => 2800,
            'price_unit' => 'per_day',
            'year' => 2023,
            'transmission' => 'Manual',
            'short_description' => 'The classic adventure motorcycle for solo riders who want to discover Nepal at their own pace.',
            'description' => 'Ride the Royal Enfield Himalayan for an authentic two-wheeled adventure through the back roads of the Far West. Comfortable upright riding position, serious off-road capability and a sturdy build make it perfect for both city cruising and mountain exploration.',
            'features' => "Helmet included\nTwo riding jackets available\nDedicated parking\nMap & route advice",
            'image' => 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?w=600&q=80',
            'availability' => true,
            'featured' => true,
            'sort_order' => 6,
        ],
        [
            'name' => 'Mahindra Thar',
            'slug' => 'mahindra-thar',
            'brand' => 'Mahindra',
            'model' => 'Thar',
            'vehicle_type' => 'jeep',
            'location' => 'Attariya',
            'seating_capacity' => 6,
            'price' => 10000,
            'price_unit' => 'per_day',
            'year' => 2023,
            'transmission' => 'Manual',
            'short_description' => 'A stylish open-top 4WD for photo trips, safaris and weekend getaways.',
            'description' => 'The Mahindra Thar brings rugged 4WD style with modern comfort. With a removable top, six seats and all the go-anywhere attitude you expect, it is the favourite for photo tours, jungle drives and adventurous weekend escapes in the Terai.',
            'features' => "4WD\nConvertible top\n6 seats\nPhotography-friendly",
            'image' => 'https://images.unsplash.com/photo-1595802103214-57f45090be3c?w=600&q=80',
            'availability' => true,
            'featured' => false,
            'sort_order' => 7,
        ],
        [
            'name' => 'Toyota Hiace',
            'slug' => 'toyota-hiace',
            'brand' => 'Toyota',
            'model' => 'Hiace',
            'vehicle_type' => 'van',
            'location' => 'Bhimdatta',
            'seating_capacity' => 14,
            'price' => 13500,
            'price_unit' => 'per_day',
            'year' => 2022,
            'transmission' => 'Manual',
            'short_description' => 'A premium 14-seater van for larger groups wanting comfort and space on every trip.',
            'description' => 'The Toyota Hiace offers generous seating for up to fourteen travellers with the smoothness of a passenger van. Wide doors, air conditioning and a comfortable cabin make it the perfect upgrade for family reunions, group village visits and airport transfers.',
            'features' => "14 seats\nAir conditioning\nSmooth highway ride\nDedicated driver",
            'image' => 'https://images.unsplash.com/photo-1565043666747-69f6646db940?w=600&q=80',
            'availability' => true,
            'featured' => false,
            'sort_order' => 8,
        ],
    ];

    /**
     * Query scope that only includes active vehicles.
     */
    public function scopeActive($query): void
    {
        $query->where('is_active', true);
    }

    /**
     * The destination this vehicle serves (existing Home destination).
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(HomeDestination::class);
    }

    /**
     * Human-readable vehicle type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->vehicle_type] ?? Str::ucfirst($this->vehicle_type);
    }

    /**
     * Human-readable pricing unit label.
     */
    public function getPriceUnitLabelAttribute(): string
    {
        return self::PRICE_UNIT_LABELS[$this->price_unit] ?? Str::ucfirst(str_replace('_', ' ', $this->price_unit));
    }

    /**
     * The price shown on cards: rupees with unit, or "Contact for price".
     */
    public function getPriceDisplayAttribute(): string
    {
        if ($this->price_unit === 'contact') {
            return 'Contact for price';
        }

        return 'Rs. '.number_format((float) $this->price);
    }

    /**
     * The human label shown for the vehicle location: the related destination
     * name when linked, otherwise the free-text location.
     */
    public function getLocationLabelAttribute(): ?string
    {
        return $this->destination?->name ?? $this->location;
    }

    /**
     * Resolve the public URL for the vehicle image.
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
}
