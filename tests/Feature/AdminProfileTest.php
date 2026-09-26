<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/**
 * An administrator account carrying the same real profile fields a customer
 * can edit, so both dashboards exercise the identical update mechanism.
 */
function administrator(array $attributes = []): User
{
    return User::factory()->create($attributes + [
        'is_admin' => true,
        'phone' => '9822664451',
        'address' => 'Dhangadhi',
        'city' => 'Dhangadhi',
        'country' => 'Nepal',
    ]);
}

test('the admin profile page renders inside the adminlte dashboard', function () {
    $admin = administrator();

    $this->actingAs($admin)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('class="skip-links"', false)
        ->assertSee('Administrator Information')
        // The Breeze/Tailwind wrapper was what made the admin profile look fake.
        ->assertDontSee('min-h-screen bg-gray-100', false);
});

test('every section of the profile page is an adminlte card not a tailwind stub', function () {
    $admin = administrator();

    $page = $this->actingAs($admin)->get(route('profile.edit'));

    $page->assertOk()
        // AdminLTE card chrome for the three sections.
        ->assertSee('class="card card-primary card-outline mb-4"', false)
        ->assertSee('Update Password')
        ->assertSee('Delete Account')
        // Breeze/Tailwind residue that the fake admin profile used to render.
        ->assertDontSee('text-gray-900', false)
        ->assertDontSee('x-data', false)
        ->assertDontSee('bg-gray-100', false)
        ->assertDontSee('border-gray-300', false);
});

test('the customer and admin profile pages are the same view and mechanism', function () {
    $admin = administrator();
    $customer = User::factory()->create(['is_admin' => false]);

    $adminPage = $this->actingAs($admin)->get(route('profile.edit'));
    $customerPage = $this->actingAs($customer)->get(route('profile.edit'));

    $adminPage->assertOk();
    $customerPage->assertOk();

    // Both dashboards submit to the one profile.update endpoint.
    expect($adminPage->getContent())->toContain('action="'.route('profile.update').'"')
        ->and($customerPage->getContent())->toContain('action="'.route('profile.update').'"');

    // The administrator card is the only difference, and it is role driven.
    $adminPage->assertSee('Administrator Information');
    $customerPage->assertDontSee('Administrator Information');
});

test('the admin dashboard profile link opens the real admin profile page', function () {
    $admin = administrator();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('profile.edit').'"', false);

    $this->actingAs($admin)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Administrator Information');
});

test('the admin profile page shows the real authenticated account data', function () {
    $admin = administrator([
        'name' => 'Sugam Admin',
        'email' => 'real.admin@jsp.test',
        'phone' => '9801234567',
        'address' => 'Attariya Road',
        'city' => 'Bharatpur',
    ]);

    $this->actingAs($admin)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Sugam Admin')
        ->assertSee('real.admin@jsp.test')
        ->assertSee('value="9801234567"', false)
        ->assertSee('value="Attariya Road"', false)
        ->assertSee('value="Bharatpur"', false)
        ->assertSee($admin->created_at->format('M j, Y'));
});

test('an admin name change is saved and survives a refresh', function () {
    $admin = administrator();
    $originalPassword = $admin->password;

    $this->actingAs($admin)
        ->patch(route('profile.update'), [
            'name' => 'New Admin Name',
            'email' => $admin->email,
            'phone' => $admin->phone,
            'address' => $admin->address,
            'city' => $admin->city,
            'country' => $admin->country,
        ])
        ->assertSessionHasNoErrors();

    expect($admin->refresh()->name)->toBe('New Admin Name')
        ->and($admin->password)->toBe($originalPassword);

    $this->actingAs($admin)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('New Admin Name');
});

test('an admin email and phone change is saved and survives a refresh', function () {
    $admin = administrator();

    $this->actingAs($admin)
        ->patch(route('profile.update'), [
            'name' => $admin->name,
            'email' => 'transferred.admin@jsp.test',
            'phone' => '+9779812345678',
            'address' => $admin->address,
            'city' => $admin->city,
            'country' => $admin->country,
        ])
        ->assertSessionHasNoErrors();

    expect($admin->refresh()->email)->toBe('transferred.admin@jsp.test')
        ->and($admin->phone)->toBe('+9779812345678');

    $this->actingAs($admin)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('transferred.admin@jsp.test')
        ->assertSee('+9779812345678');
});

test('admin profile changes persist after logging out and back in', function () {
    $admin = administrator();

    $this->actingAs($admin)
        ->patch(route('profile.update'), [
            'name' => 'Persisting Admin',
            'email' => 'persist.admin@jsp.test',
            'phone' => '+9779811112222',
            'address' => 'Attariya',
            'city' => 'Bharatpur',
            'country' => $admin->country,
        ])
        ->assertSessionHasNoErrors();

    $this->post(route('logout'));
    $this->assertGuest();

    $this->post(route('login'), [
        'email' => 'persist.admin@jsp.test',
        'password' => 'password',
    ])->assertRedirect();

    $this->assertAuthenticated();

    $this->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Persisting Admin')
        ->assertSee('+9779811112222')
        ->assertSee('Administrator Information');
});

test('an admin password change is hashed and the new password logs in', function () {
    $admin = administrator();
    $originalHash = $admin->password;

    $this->actingAs($admin)
        ->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-admin-secret',
            'password_confirmation' => 'new-admin-secret',
        ])
        ->assertSessionHasNoErrors();

    $admin->refresh();

    expect($admin->password)->not->toBe($originalHash)
        ->and($admin->password)->not->toBe('new-admin-secret')
        ->and(Hash::check('new-admin-secret', $admin->password))->toBeTrue()
        ->and(Hash::check('password', $admin->password))->toBeFalse();

    $this->post(route('logout'));
    $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])->assertRedirect();
    $this->assertGuest();

    $this->post(route('login'), ['email' => $admin->email, 'password' => 'new-admin-secret'])
        ->assertRedirect();
    $this->assertAuthenticatedAs($admin);
});

test('an admin password change keeps the administrator role', function () {
    $admin = administrator();

    $this->actingAs($admin)
        ->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'role-keeping-secret',
            'password_confirmation' => 'role-keeping-secret',
        ])
        ->assertSessionHasNoErrors();

    expect($admin->refresh()->isAdmin())->toBeTrue();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk();

    $this->post(route('logout'));

    $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'role-keeping-secret',
    ])->assertRedirect();

    $this->assertAuthenticated();
    $this->get(route('admin.dashboard'))->assertOk();
});

test('an incorrect current password is rejected for an admin', function () {
    $admin = administrator();
    $originalHash = $admin->password;

    $this->actingAs($admin)
        ->put(route('password.update'), [
            'current_password' => 'not-the-password',
            'password' => 'attempted-secret',
            'password_confirmation' => 'attempted-secret',
        ])
        ->assertSessionHasErrors('current_password', null, 'updatePassword');

    expect($admin->refresh()->password)->toBe($originalHash);
});

test('saving the admin profile without a password keeps the current password', function () {
    $admin = administrator(['email' => 'keep.hashing@jsp.test']);
    $originalHash = $admin->password;

    $this->actingAs($admin)
        ->patch(route('profile.update'), [
            'name' => 'Password Stays',
            'email' => $admin->email,
            'phone' => $admin->phone,
            'address' => $admin->address,
            'city' => $admin->city,
            'country' => $admin->country,
        ])
        ->assertSessionHasNoErrors();

    expect($admin->refresh()->password)->toBe($originalHash)
        ->and(Hash::check('password', $admin->password))->toBeTrue();
});

test('updating the admin profile leaves every customer record untouched', function () {
    $admin = administrator();
    $customer = User::factory()->create([
        'is_admin' => false,
        'name' => 'Untouched Customer',
        'phone' => '9822664451',
        'address' => 'Dhangadhi',
        'city' => 'Dhangadhi',
        'country' => 'Nepal',
    ]);

    $this->actingAs($admin)
        ->patch(route('profile.update'), [
            'name' => 'Admin Changed',
            'email' => 'admin.changed@jsp.test',
            'phone' => '9809999999',
            'address' => 'Admin Road',
            'city' => 'Admin City',
            'country' => 'Admin Country',
        ])
        ->assertSessionHasNoErrors();

    expect($customer->refresh()->name)->toBe('Untouched Customer')
        ->and($customer->email)->not->toBe('admin.changed@jsp.test')
        ->and($customer->phone)->toBe('9822664451')
        ->and($customer->address)->toBe('Dhangadhi')
        ->and($customer->isAdmin())->toBeFalse();
});

test('the admin profile always edits the authenticated account and never another user', function () {
    $admin = administrator();
    $victim = administrator(['name' => 'Victim Admin', 'email' => 'victim@jsp.test']);

    $this->actingAs($admin)
        ->patch(route('profile.update'), [
            'id' => $victim->id,
            'user_id' => $victim->id,
            'name' => 'Authenticated Admin',
            'email' => 'authenticated.admin@jsp.test',
            'phone' => '9800000000',
            'address' => 'Authenticated Address',
            'city' => 'Authenticated City',
            'country' => 'Authenticated Country',
        ])
        ->assertSessionHasNoErrors();

    expect($victim->refresh()->name)->toBe('Victim Admin')
        ->and($victim->email)->toBe('victim@jsp.test')
        ->and($victim->address)->toBe('Dhangadhi')
        ->and($admin->refresh()->name)->toBe('Authenticated Admin')
        ->and($admin->email)->toBe('authenticated.admin@jsp.test');
});

test('the admin email verification status is read from the account', function () {
    $unverified = administrator([
        'email' => 'unverified.admin@jsp.test',
        'email_verified_at' => null,
    ]);
    $verified = administrator([
        'email' => 'verified.admin@jsp.test',
        'email_verified_at' => now()->subDays(3),
    ]);

    $this->actingAs($unverified)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Unverified')
        ->assertDontSee('bg-success', false);

    $this->actingAs($verified)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('bg-success', false)
        ->assertSee($verified->email_verified_at->format('M j, Y'));
});

test('changing the admin email clears verification on the real account', function () {
    $admin = administrator(['email' => 'was.verified@jsp.test']);

    $admin->forceFill(['email_verified_at' => now()->subWeek()])->save();
    expect($admin->refresh()->hasVerifiedEmail())->toBeTrue();

    $this->actingAs($admin)
        ->patch(route('profile.update'), [
            'name' => $admin->name,
            'email' => 'now.unverified@jsp.test',
            'phone' => $admin->phone,
            'address' => $admin->address,
            'city' => $admin->city,
            'country' => $admin->country,
        ])
        ->assertSessionHasNoErrors();

    expect($admin->refresh()->email)->toBe('now.unverified@jsp.test')
        ->and($admin->email_verified_at)->toBeNull();

    $this->actingAs($admin)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Unverified');
});

test('a customer cannot see administrator information or reach admin routes', function () {
    $customer = User::factory()->create(['is_admin' => false]);

    $this->actingAs($customer)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertDontSee('Administrator Information')
        ->assertDontSee('Administrator</span>');

    $this->actingAs($customer)
        ->get(route('admin.users.index'))
        ->assertForbidden();

    $this->actingAs($customer)
        ->get(route('admin.users.show', administrator()))
        ->assertForbidden();
});

test('an admin cannot self promote or grant admin through the profile form', function () {
    $customer = User::factory()->create(['is_admin' => false]);

    $this->actingAs($customer)
        ->patch(route('profile.update'), [
            'is_admin' => true,
            'role' => 'admin',
            'name' => 'Self Promoter',
            'email' => $customer->email,
            'phone' => '9800000000',
            'address' => 'Self Address',
            'city' => 'Self City',
            'country' => 'Self Country',
        ])
        ->assertSessionHasNoErrors();

    expect($customer->refresh()->isAdmin())->toBeFalse();
});

test('the profile page requires authentication for admins too', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
    $this->patch(route('profile.update'), [])->assertRedirect(route('login'));
    $this->put(route('password.update'), [])->assertRedirect(route('login'));
});

test('an admin may save the profile with only the fields that are required for an admin', function () {
    $admin = administrator();

    $this->actingAs($admin)
        ->patch(route('profile.update'), [
            'name' => 'Minimal Admin',
            'email' => $admin->email,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($admin->refresh()->name)->toBe('Minimal Admin')
        ->and($admin->isAdmin())->toBeTrue();
});
