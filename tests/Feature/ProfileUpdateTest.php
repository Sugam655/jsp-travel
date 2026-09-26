<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A customer whose profile already carries every required contact field, which
 * is the state the live customers are in after their first save.
 */
function completeCustomer(array $attributes = []): User
{
    return User::factory()->create($attributes + [
        'is_admin' => false,
        'phone' => '9822664451',
        'address' => 'Dhangadhi',
        'city' => 'Dhangadhi',
        'country' => 'Nepal',
    ]);
}

test('a customer name change is saved and survives a refresh', function () {
    $user = completeCustomer();
    $originalPassword = $user->password;

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'JSP One',
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'country' => $user->country,
        ])
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->name)->toBe('JSP One')
        ->and($user->password)->toBe($originalPassword);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('JSP One');
});

test('a customer phone change is saved and survives a refresh', function () {
    $user = completeCustomer();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '+9779800000000',
            'address' => $user->address,
            'city' => $user->city,
            'country' => $user->country,
        ])
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->phone)->toBe('+9779800000000');

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('+9779800000000');
});

test('a customer address change is saved and survives a refresh', function () {
    $user = completeCustomer();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => 'Attariya',
            'city' => 'Bharatpur',
            'country' => $user->country,
        ])
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->address)->toBe('Attariya')
        ->and($user->city)->toBe('Bharatpur');

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Attariya');
});

test('a customer email change to an unused address succeeds and clears verification', function () {
    $user = completeCustomer();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'jsp1@gmail.com',
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'country' => $user->country,
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->email)->toBe('jsp1@gmail.com')
        ->and($user->email_verified_at)->toBeNull();
});

test('a customer email change to another users address is rejected', function () {
    $user = completeCustomer();
    $other = User::factory()->create(['email' => 'abc@gmail.com']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $other->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'country' => $user->country,
        ])
        ->assertSessionHasErrors('email');

    expect($user->refresh()->email)->not->toBe('abc@gmail.com');
});

test('an invalid email is rejected', function () {
    $user = completeCustomer();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'not-an-email',
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'country' => $user->country,
        ])
        ->assertSessionHasErrors('email');

    expect($user->refresh()->email)->not->toBe('not-an-email');
});

test('an empty required field is rejected and valid input is preserved', function () {
    $user = completeCustomer();

    $response = $this->from(route('profile.edit'))
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => '',
            'email' => $user->email,
            'phone' => '9800000000',
            'address' => $user->address,
            'city' => $user->city,
            'country' => $user->country,
        ]);

    $response->assertSessionHasErrors('name');
    $response->assertRedirect(route('profile.edit'));

    expect($user->refresh()->phone)->toBe('9822664451');

    // The entered phone is still in the form after the failed submit.
    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('9800000000');
});

test('profile changes persist after logging out and back in', function () {
    $user = completeCustomer();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Persist Me',
            'email' => $user->email,
            'phone' => '+9779812345678',
            'address' => 'Attariya',
            'city' => 'Bharatpur',
            'country' => $user->country,
        ])
        ->assertSessionHasNoErrors();

    $this->post(route('logout'));

    $this->assertGuest();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect();

    $this->assertAuthenticated();

    $this->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Persist Me')
        ->assertSee('+9779812345678')
        ->assertSee('Attariya');
});

test('a customer cannot update another customer profile by sending a user id', function () {
    $user = completeCustomer();
    $victim = completeCustomer(['name' => 'Victim Name']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'id' => $victim->id,
            'user_id' => $victim->id,
            'name' => 'Attacker',
            'email' => $user->email,
            'phone' => '9800000000',
            'address' => 'Attacker Address',
            'city' => 'Attacker City',
            'country' => 'Attacker Country',
        ])
        ->assertSessionHasNoErrors();

    expect($victim->refresh()->name)->toBe('Victim Name')
        ->and($victim->address)->toBe('Dhangadhi')
        ->and($victim->phone)->toBe('9822664451')
        ->and($user->refresh()->name)->toBe('Attacker')
        ->and($user->address)->toBe('Attacker Address');
});

test('a customer cannot reach the profile page without authentication', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
    $this->patch(route('profile.update'), [])->assertRedirect(route('login'));
});

test('the updated contact details are shown on the customer dashboard', function () {
    $user = completeCustomer();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '+9779811112222',
            'address' => 'Attariya Road',
            'city' => 'Bharatpur',
            'country' => 'Nepal',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->get(route('user.dashboard'))
        ->assertOk()
        ->assertSee('+9779811112222')
        ->assertSee('Attariya Road, Bharatpur, Nepal');
});

test('a successful save returns to the profile page with a success message', function () {
    $user = completeCustomer();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Feedback Please',
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'country' => $user->country,
        ])
        ->assertRedirect(route('profile.edit'));

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Profile updated successfully');
});

test('completing an incomplete profile returns to the profile page with a success message', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '9822664451',
            'address' => 'Dhangadhi',
            'city' => 'Dhangadhi',
            'country' => 'Nepal',
        ])
        ->assertRedirect(route('profile.edit'));

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Profile updated successfully');
});
