<?php

namespace Modules\Contact\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContactSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_name',
        'page_title',
        'page_subtitle',
        'address',
        'phone',
        'alternate_phone',
        'landline',
        'whatsapp_number',
        'viber',
        'email',
        'alternate_email',
        'manager_name',
        'md_name',
        'opening_hours',
        'map_url',
        'facebook_url',
        'instagram_url',
        'youtube_url',
        'twitter_url',
        'contact_image',
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
     * Default contact settings seeded from the original static website.
     *
     * @var array<string, string|null>
     */
    public const DEFAULTS = [
        'company_name' => 'Jay Shiv Parvati Travel & Tour',
        'page_title' => 'Contact Us',
        'page_subtitle' => "Tell us when and where you'd like to go and we'll confirm availability within 24 hours.",
        'address' => 'Attariya-01, Kailali, Dhangadhi Road',
        'phone' => '9822-773259',
        'alternate_phone' => '9868-442393',
        'landline' => '091-550614',
        'whatsapp_number' => '9866-521122',
        'viber' => '9858-483291',
        'email' => 'Jsptravel291@gmail.com',
        'alternate_email' => null,
        'manager_name' => 'Keshav Bhatta',
        'md_name' => 'Renu Bhatta',
        'opening_hours' => '24/7 services',
        'map_url' => null,
        'facebook_url' => null,
        'instagram_url' => null,
        'youtube_url' => null,
        'twitter_url' => null,
        'contact_image' => 'https://www.dntt.com.np/uploads/ecategory/86862100.jpg',
    ];

    /**
     * Resolve the public URL for the contact image.
     *
     * Supports both external URLs (kept untouched) and files stored on the
     * public disk (relative path). Returns null when no image is set.
     */
    public function getContactImageUrlAttribute(): ?string
    {
        $image = $this->contact_image;

        if (blank($image)) {
            return null;
        }

        if (Str::startsWith($image, ['http://', 'https://', '//'])) {
            return $image;
        }

        return Storage::disk('public')->url($image);
    }

    /**
     * Whether the stored contact image points to a locally uploaded file.
     */
    public function hasUploadedContactImage(): bool
    {
        return filled($this->contact_image)
            && ! Str::startsWith($this->contact_image, ['http://', 'https://', '//']);
    }
}
