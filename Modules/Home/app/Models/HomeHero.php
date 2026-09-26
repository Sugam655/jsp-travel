<?php

namespace Modules\Home\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HomeHero extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'background_image',
        'button_text',
        'button_url',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Default hero content used when no active record exists.
     *
     * @var array<string, string|null>
     */
    public const DEFAULTS = [
        'title' => 'Journeys Made Memorable',
        'description' => 'Discover new destinations and create unforgettable journeys with our reliable travel services. From trip planning and transportation to personalized travel assistance, we make every journey comfortable, convenient, and enjoyable from start to finish.',
        'background_image' => 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=1920&h=1080&fit=crop',
        'button_text' => 'Explore Destinations',
        'button_url' => 'destinations.html',
    ];

    /**
     * Resolve the public URL for the background image.
     *
     * Supports both external URLs (kept untouched) and files stored on the
     * public disk (relative path). Returns null when no image is set.
     */
    public function getBackgroundImageUrlAttribute(): ?string
    {
        $image = $this->background_image;

        if (blank($image)) {
            return null;
        }

        if (Str::startsWith($image, ['http://', 'https://', '//'])) {
            return $image;
        }

        return Storage::disk('public')->url($image);
    }

    /**
     * Whether the stored background image points to a locally uploaded file.
     */
    public function hasUploadedBackgroundImage(): bool
    {
        return filled($this->background_image)
            && ! Str::startsWith($this->background_image, ['http://', 'https://', '//']);
    }
}
