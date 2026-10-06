<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\Payment;

#[Fillable(['name', 'email', 'password', 'is_admin', 'phone', 'address', 'city', 'country', 'emergency_contact', 'date_of_birth', 'profile_photo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Whether this account is an administrator.
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * The bookings this customer has requested.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class)->latest();
    }

    /**
     * The payments the customer has made across their bookings.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * The customer contact fields the booking flow depends on. A profile
     * is considered complete once every one of them is filled in.
     *
     * @return array<int, string>
     */
    public function requiredProfileFields(): array
    {
        return ['phone', 'address', 'city', 'country'];
    }

    /**
     * Whether the customer has supplied every required profile field.
     */
    public function isProfileComplete(): bool
    {
        foreach ($this->requiredProfileFields() as $field) {
            if (blank($this->getAttribute($field))) {
                return false;
            }
        }

        return true;
    }

    /**
     * The absolute url for the stored profile photo, if any.
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        $image = $this->profile_photo;

        if (blank($image)) {
            return null;
        }

        if (Str::startsWith($image, ['http://', 'https://', '//'])) {
            return $image;
        }

        return Storage::disk('public')->url($image);
    }

    /**
     * Whether the profile photo points to a locally uploaded file.
     */
    public function hasUploadedProfilePhoto(): bool
    {
        return filled($this->profile_photo)
            && ! Str::startsWith($this->profile_photo, ['http://', 'https://', '//']);
    }

    /**
     * Store an uploaded profile photo on the public disk and return its path.
     */
    public function storeProfilePhoto(UploadedFile $photo): string
    {
        return $photo->store('avatars', 'public');
    }

    /**
     * Delete a previously uploaded profile photo. Remote paths are left
     * untouched because they are not managed by this application.
     */
    public function deleteStoredProfilePhoto(?string $path): void
    {
        if (blank($path) || ! $this->pathIsLocallyStored($path)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    /**
     * Whether the given profile photo path is managed by this application.
     */
    protected function pathIsLocallyStored(string $path): bool
    {
        return ! Str::startsWith($path, ['http://', 'https://', '//']);
    }

    /**
     * The profile url used by the AdminLTE navbar user menu.
     */
    public function adminlte_profile_url(): string
    {
        return route('profile.edit');
    }
}
