<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '9800000000',
            'address' => 'Dhankuta',
            'city' => 'Dhankuta',
            'country' => 'Nepal',
            'emergency_contact' => '9811111111',
            'date_of_birth' => '1995-05-20',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertSame('9800000000', $user->phone);
    $this->assertSame('Dhankuta', $user->address);
    $this->assertSame('Nepal', $user->country);
    $this->assertSame('9811111111', $user->emergency_contact);
    $this->assertNull($user->email_verified_at);
});

test('a customer cannot update their profile without the required contact details', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])
        ->assertSessionHasErrors(['phone', 'address', 'city', 'country']);

    $this->assertNotSame('Test User', $user->refresh()->name);
});

test('an administrator can update their profile without contact details', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->patch('/profile', [
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertSame('Super Admin', $admin->refresh()->name);
    $this->assertNull($admin->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
            'phone' => '9800000000',
            'address' => 'Dhankuta',
            'city' => 'Dhankuta',
            'country' => 'Nepal',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('a customer is prompted to complete their profile and is returned to the profile page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get('/user/dashboard')
        ->assertOk()
        ->assertSee('Complete Your Profile');

    $response = $this->actingAs($user)
        ->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '9800000000',
            'address' => 'Dhankuta',
            'city' => 'Dhankuta',
            'country' => 'Nepal',
        ]);

    $response->assertRedirect('/profile');

    $this->actingAs($user)->get('/user/dashboard')
        ->assertOk()
        ->assertDontSee('Complete Your Profile');
});

test('the customer profile page shows the personal, contact and address sections', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get('/profile')
        ->assertOk()
        ->assertSee('Personal Information')
        ->assertSee('Contact Information')
        ->assertSee('Address')
        ->assertSee('Update Password');
});

test('a customer can upload a profile photo', function () {
    $user = User::factory()->create(['is_admin' => false]);

    Storage::fake('public');

    $this->actingAs($user)
        ->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '9800000000',
            'address' => 'Dhankuta',
            'city' => 'Dhankuta',
            'country' => 'Nepal',
            'profile_photo' => UploadedFile::fake()->image('profile.jpg'),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    Storage::disk('public')->assertExists($user->profile_photo);
});

test('a non-image profile photo upload is rejected', function () {
    $user = User::factory()->create(['is_admin' => false]);

    Storage::fake('public');

    $this->actingAs($user)
        ->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '9800000000',
            'address' => 'Dhankuta',
            'city' => 'Dhankuta',
            'country' => 'Nepal',
            'profile_photo' => UploadedFile::fake()->create('resume.txt'),
        ])
        ->assertSessionHasErrors('profile_photo');

    $this->assertNull($user->refresh()->profile_photo);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
