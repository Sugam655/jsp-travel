<?php

namespace Modules\Hotels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Home\Models\HomeDestination;

class Hotel extends Model
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
        'address',
        'rating',
        'price',
        'short_description',
        'description',
        'image',
        'phone',
        'email',
        'website',
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
        'rating' => 'integer',
        'price' => 'decimal:2',
        'featured' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Default hotels promoted on the frontend listing when no active
     * record exists yet.
     *
     * @var array<int, array<string, mixed>>
     */
    public const DEFAULTS = [
        [
            'title' => 'Silver Oak Resort',
            'slug' => 'silver-oak-resort',
            'location' => 'Dhangadhi',
            'address' => 'Mahendranagar Highway, Dhangadhi',
            'rating' => 5,
            'price' => 7500,
            'short_description' => 'A premium lakeside-style resort offering spacious rooms, a swimming pool and fine dining in the heart of Dhangadhi.',
            'description' => 'Silver Oak Resort blends contemporary comfort with Far Western hospitality. Each room is thoughtfully furnished with air conditioning, satellite television and complimentary Wi-Fi, while the in-house restaurant serves both Nepali and continental favourites. Guests can unwind by the swimming pool, host events in the banquet hall, or simply relax in the landscaped garden.',
            'image' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&q=80',
            'phone' => '091-523455',
            'email' => 'reservations@silveroak.com.np',
            'featured' => true,
            'sort_order' => 1,
        ],
        [
            'title' => 'Hotel Batika',
            'slug' => 'hotel-batika',
            'location' => 'Dhangadhi',
            'address' => 'Hasuliya Chowk, Dhangadhi',
            'rating' => 4,
            'price' => 4200,
            'short_description' => 'One of the oldest and most trusted hotels in Far Western Nepal, known for warm hospitality and a central location.',
            'description' => 'A landmark of Dhangadhi hospitality for decades, Hotel Batika offers clean, air-conditioned rooms, a popular in-house restaurant and dependable service. Its central location puts shops, banks and transport hubs within easy reach, making it a comfortable base for business and leisure travellers alike.',
            'image' => 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=600&q=80',
            'phone' => '091-521432',
            'email' => 'info@hotelbatika.com.np',
            'featured' => true,
            'sort_order' => 2,
        ],
        [
            'title' => 'Hotel Shreepur',
            'slug' => 'hotel-shreepur',
            'location' => 'Krishnapur',
            'address' => 'Krishnapur Chowk, Kanchanpur',
            'rating' => 3,
            'price' => 3200,
            'short_description' => 'Comfortable rooms and a rooftop restaurant, ideal for business travellers crossing the Far West.',
            'description' => 'Hotel Shreepur is a reliable roadside stop with bright, modern rooms, hot showers and free parking. The rooftop restaurant serves freshly cooked Nepali meals with views over Krishnapur, and the friendly staff happily help with onward travel arrangements.',
            'image' => 'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?w=600&q=80',
            'phone' => '099-523456',
            'featured' => false,
            'sort_order' => 3,
        ],
        [
            'title' => 'Dolphin Hotel & Resort',
            'slug' => 'dolphin-hotel-and-resort',
            'location' => 'Dhangadhi',
            'address' => 'Jhula Road, Dhangadhi',
            'rating' => 4,
            'price' => 3900,
            'short_description' => 'A well-appointed hotel and restaurant with banquet facilities and cosy family rooms.',
            'description' => 'Dolphin Hotel & Resort combines comfortable accommodation with a lively restaurant and banquet facilities for weddings and functions. Family rooms, a warm welcome and attentive room service make it a favourite for both group stays and overnight travellers.',
            'image' => 'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=600&q=80',
            'phone' => '091-525777',
            'featured' => false,
            'sort_order' => 4,
        ],
        [
            'title' => 'Hotel Sophie',
            'slug' => 'hotel-sophie',
            'location' => 'Attariya',
            'address' => 'Attariya Chowk, Kailali',
            'rating' => 3,
            'price' => 2500,
            'short_description' => 'A clean, budget-friendly hotel on the East–West Highway, perfect for a comfortable overnight stop.',
            'description' => 'Hotel Sophie offers simple, spotless rooms at a friendly price, right on the busy East–West Highway in Attariya. Expect comfortable beds, hot water, a small restaurant and a secure place to park while you rest before continuing your journey across Far West Nepal.',
            'image' => 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?w=600&q=80',
            'phone' => '091-405050',
            'featured' => false,
            'sort_order' => 5,
        ],
        [
            'title' => 'Shuklaphanta Eco Lodge',
            'slug' => 'shuklaphanta-eco-lodge',
            'location' => 'Shuklaphanta',
            'address' => 'Shuklaphanta National Park, Kanchanpur',
            'rating' => 4,
            'price' => 5500,
            'short_description' => 'Eco-friendly lodge at the gate of Shuklaphanta National Park with guided jungle safaris and local Tharu cuisine.',
            'description' => 'Set right at the entrance to Shuklaphanta National Park, this eco-lodge is the perfect base for jungle adventures. Wake to birdsong, enjoy wild grass and tall grasslands on a guided safari, and end the day with authentic Tharu dishes cooked by local families.',
            'image' => 'https://images.unsplash.com/photo-1545241047-6083a3684587?w=600&q=80',
            'phone' => '099-540123',
            'email' => 'stay@shuklaphantaeco.com',
            'website' => 'https://shuklaphantaeco.com',
            'featured' => true,
            'sort_order' => 6,
        ],
    ];

    /**
     * Query scope that only includes active hotels.
     */
    public function scopeActive($query): void
    {
        $query->where('is_active', true);
    }

    /**
     * The destination this hotel is located in (existing Home destination).
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(HomeDestination::class);
    }

    /**
     * Resolve the public URL for the hotel image.
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
     * The human label shown for the hotel location: the related destination
     * name when linked, otherwise the free-text location.
     */
    public function getLocationLabelAttribute(): ?string
    {
        return $this->destination?->name ?? $this->location;
    }
}
