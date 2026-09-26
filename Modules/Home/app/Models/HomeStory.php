<?php

namespace Modules\Home\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HomeStory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'author_name',
        'trip',
        'review',
        'avatar',
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
     * Default traveler stories promoted on the homepage when no
     * active record exists yet.
     *
     * @var array<int, array<string, string|int|null>>
     */
    public const DEFAULTS = [
        [
            'author_name' => 'Sophia Carter',
            'trip' => 'Everest Base Camp, Nepal',
            'review' => '“Everything was perfectly organized—from our airport pickup to the mountain lodge. We simply relaxed and enjoyed every magical moment.”',
            'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=160&h=160&fit=crop',
        ],
        [
            'author_name' => 'Daniel Moore',
            'trip' => 'Annapurna Circuit, Nepal',
            'review' => '“The guides felt like old friends and showed us places we would never have discovered alone. Nepal completely stole our hearts.”',
            'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=160&h=160&fit=crop',
        ],
        [
            'author_name' => 'Emma Wilson',
            'trip' => 'Pokhara Escape, Nepal',
            'review' => '“Beautiful hotels, safe transport and such thoughtful service. Our honeymoon became even more special than we imagined.”',
            'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=160&h=160&fit=crop',
        ],
        [
            'author_name' => 'Olivia Brown',
            'trip' => 'Kathmandu Valley, Nepal',
            'review' => '“A flawless cultural tour with wonderful food, warm people and expert planning. Every day brought a new unforgettable experience.”',
            'avatar' => 'https://images.unsplash.com/photo-1531123897727-8f129e1688ce?w=160&h=160&fit=crop',
        ],
        [
            'author_name' => 'James Lee',
            'trip' => 'Nagarkot Sunrise, Nepal',
            'review' => '“The sunrise over the Himalayas was breathtaking. The entire trip was safe, comfortable and planned with genuine care.”',
            'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=160&h=160&fit=crop',
        ],
        [
            'author_name' => 'Mia Anderson',
            'trip' => 'Chitwan Safari, Nepal',
            'review' => '“From booking to the final goodbye, communication was excellent. This team turned our family holiday into a lifelong memory.”',
            'avatar' => 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=160&h=160&fit=crop',
        ],
    ];

    /**
     * Resolve the public URL for the story avatar.
     *
     * Supports both external URLs (kept untouched) and files stored on the
     * public disk (relative path). Returns null when no avatar is set.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        $avatar = $this->avatar;

        if (blank($avatar)) {
            return null;
        }

        if (Str::startsWith($avatar, ['http://', 'https://', '//'])) {
            return $avatar;
        }

        return Storage::disk('public')->url($avatar);
    }

    /**
     * Whether the stored avatar points to a locally uploaded file.
     */
    public function hasUploadedAvatar(): bool
    {
        return filled($this->avatar)
            && ! Str::startsWith($this->avatar, ['http://', 'https://', '//']);
    }
}
