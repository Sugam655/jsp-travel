<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\Payment;

beforeEach(function () {
    Artisan::call('module:migrate', ['module' => 'Bookings', '--force' => true]);
});

/**
 * Build a customer account whose fields the view page must display verbatim.
 */
function managedCustomer(array $attributes = []): User
{
    return User::factory()->create($attributes + [
        'is_admin' => false,
        'phone' => '9812345678',
        'address' => 'Ward 5, Dhankuta',
        'city' => 'Dhankuta',
        'country' => 'Nepal',
    ]);
}

test('an administrator can view all registered users in user management', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customers = User::factory()->count(3)->create(['is_admin' => false]);

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk()
        ->assertSee('User Management')
        ->assertSee('users-table')
        ->assertSee($admin->name)
        ->assertSee($admin->email)
        ->assertSee('Admin')
        ->assertSee($customers[0]->name)
        ->assertSee($customers[0]->email)
        ->assertSee('User');
});

test('user management shows customer contact details, booking counts and registration date', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = managedCustomer();

    Booking::create([
        'user_id' => $customer->id,
        'booking_type' => 'tour',
        'service_title' => 'Everest Base Camp Trek',
        'name' => $customer->name,
        'email' => $customer->email,
        'status' => 'pending',
    ]);

    $content = preg_replace('/\s+/', ' ', $this->actingAs($admin)->get('/admin/users')->getContent());

    expect($content)
        ->toContain('9812345678')
        ->toContain('Ward 5, Dhankuta')
        ->toContain('Dhankuta</td> <td>Nepal</td> <td>1</td>')
        ->toContain($customer->created_at->format('M d, Y h:i A'));
});

test('user management never exposes passwords or security credentials', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = User::factory()->create(['is_admin' => false, 'password' => 'secret-password']);

    $content = $this->actingAs($admin)->get('/admin/users')->getContent();

    expect($content)
        ->not->toContain('secret-password')
        ->not->toContain($customer->getAuthPassword())
        ->not->toContain('remember_token')
        ->not->toContain($customer->getRememberToken());
});

test('a normal user cannot access user management', function () {
    $customer = User::factory()->create(['is_admin' => false]);

    $this->actingAs($customer)->get('/admin/users')->assertForbidden();
});

test('a guest is redirected to login before accessing user management', function () {
    $this->get('/admin/users')->assertRedirect('/login');
});

test('a newly registered user automatically appears in user management', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->post('/register', [
        'name' => 'Fresh Customer',
        'email' => 'fresh@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect('/user/dashboard');

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk()
        ->assertSee('Fresh Customer')
        ->assertSee('fresh@example.com');
});

test('the user list offers a View action and no Edit action at all', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = managedCustomer();

    $response = $this->actingAs($admin)->get('/admin/users');

    $response->assertOk()
        // View is the only action offered.
        ->assertSee(route('admin.users.show', $customer), false)
        ->assertSee('View')
        // No edit affordance of any kind remains in the list.
        ->assertDontSee('Edit')
        ->assertDontSee('Edit User')
        ->assertDontSee('fa-user-pen', false)
        ->assertDontSee("/admin/users/{$customer->id}/edit", false)
        ->assertDontSee('admin.users.edit', false)
        ->assertDontSee('admin.users.update', false);
});

test('the user list explains that accounts are read-only', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk()
        ->assertSee('read-only')
        ->assertSee('My Profile');
});

test('an administrator can open the read-only page for a user', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = managedCustomer(['name' => 'Viewed Customer']);

    $this->actingAs($admin)
        ->get(route('admin.users.show', $customer))
        ->assertOk()
        ->assertSee('Viewed Customer')
        ->assertSee($customer->email)
        ->assertSee('9812345678')
        ->assertSee('Ward 5, Dhankuta')
        ->assertSee('Dhankuta')
        ->assertSee('Nepal')
        ->assertSee('read-only');
});

test('the user view page shows real account, role and verification details', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = managedCustomer([
        'email' => 'verified.customer@example.com',
        'email_verified_at' => now()->subDays(4),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.show', $customer))
        ->assertOk()
        ->assertSee('Account')
        ->assertSee('Registered')
        ->assertSee('Last Updated')
        ->assertSee('Verified')
        ->assertSee($customer->email_verified_at->format('M d, Y'))
        ->assertSee('User');
});

test('the user view page reports an unverified account as unverified', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = managedCustomer(['email_verified_at' => null]);

    $this->actingAs($admin)
        ->get(route('admin.users.show', $customer))
        ->assertOk()
        ->assertSee('Unverified')
        ->assertDontSee('badge text-bg-success', false);
});

test('the user view page marks an administrator account as an admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.users.show', $admin))
        ->assertOk()
        ->assertSee('Admin');
});

test('the user view page summarises the account bookings', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = managedCustomer();

    Booking::create([
        'user_id' => $customer->id,
        'booking_type' => 'tour',
        'service_title' => 'Everest Base Camp Trek',
        'booking_reference' => 'JSP-REF-001',
        'name' => $customer->name,
        'email' => $customer->email,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.show', $customer))
        ->assertOk()
        ->assertSee('Recent Bookings')
        ->assertSee('JSP-REF-001')
        ->assertSee('Everest Base Camp Trek');
});

test('the user view page states when an account has no bookings', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = managedCustomer();

    $this->actingAs($admin)
        ->get(route('admin.users.show', $customer))
        ->assertOk()
        ->assertSee('This user has no bookings yet');
});

test('the user view page summarises the account payments with the verifying staff member', function () {
    $admin = User::factory()->create(['is_admin' => true, 'name' => 'Verifying Officer']);
    $customer = managedCustomer();

    $booking = Booking::create([
        'user_id' => $customer->id,
        'booking_type' => 'tour',
        'service_title' => 'Everest Base Camp Trek',
        'booking_reference' => 'JSP-PAY-001',
        'name' => $customer->name,
        'email' => $customer->email,
        'status' => 'confirmed',
    ]);

    $payment = Payment::create([
        'booking_id' => $booking->id,
        'user_id' => $customer->id,
        'amount' => 5000,
        'method' => 'bank_transfer',
        'type' => 'advance',
        'reference' => 'USER-PAGE-REF',
        'status' => 'paid',
        'recorded_by' => $customer->id,
        'verified_by' => $admin->id,
        'verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.show', $customer))
        ->assertOk()
        ->assertSee('Recent Payments')
        ->assertSee('1 payment recorded against')
        ->assertSee('USER-PAGE-REF')
        ->assertSee('Verifying Officer')
        ->assertSee(route('admin.payments.show', $payment), false);
});

test('the user view page states when an account has no payments', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = managedCustomer();

    $this->actingAs($admin)
        ->get(route('admin.users.show', $customer))
        ->assertOk()
        ->assertSee('This user has no payments yet');
});

test('the user view page never lists another account payments', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = managedCustomer();
    $other = managedCustomer();

    $otherBooking = Booking::create([
        'user_id' => $other->id,
        'booking_type' => 'tour',
        'service_title' => 'Annapurna Circuit',
        'booking_reference' => 'JSP-OTHER-001',
        'name' => $other->name,
        'email' => $other->email,
        'status' => 'confirmed',
    ]);

    Payment::create([
        'booking_id' => $otherBooking->id,
        'user_id' => $other->id,
        'amount' => 7500,
        'method' => 'cash',
        'type' => 'advance',
        'reference' => 'OTHER-ACCOUNT-REF',
        'status' => 'paid',
        'recorded_by' => $other->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.show', $customer))
        ->assertOk()
        ->assertDontSee('OTHER-ACCOUNT-REF')
        ->assertDontSee('JSP-OTHER-001');
});

test('the user view page contains no writable form controls', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = managedCustomer();

    $content = $this->actingAs($admin)->get(route('admin.users.show', $customer))->getContent();

    expect($content)
        ->not->toContain('name="name"')
        ->not->toContain('name="email"')
        ->not->toContain('name="phone"')
        ->not->toContain('name="address"')
        ->not->toContain('name="password"')
        ->not->toContain('name="is_admin"')
        ->not->toContain('name="profile_photo"')
        ->not->toContain('enctype="multipart/form-data"')
        ->not->toContain('admin.users.update', false);
});

test('the user view page never exposes the account password', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = managedCustomer(['password' => 'secret-password']);

    $content = $this->actingAs($admin)->get(route('admin.users.show', $customer))->getContent();

    expect($content)
        ->not->toContain('secret-password')
        ->not->toContain($customer->getAuthPassword())
        ->not->toContain('remember_token');
});

test('the user view page renders the initial for an account without a photo', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = managedCustomer([
        'name' => 'Zoltan Photo',
        'profile_photo' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.show', $customer))
        ->assertOk()
        ->assertSee('Z')
        ->assertDontSee('avatars/', false);
});

test('the user view page shows a stored profile photo when one exists', function () {
    Storage::fake('public');
    Storage::disk('public')->put('profile-photos/photo.jpg', 'fake-image-bytes');

    $admin = User::factory()->create(['is_admin' => true]);
    $customer = managedCustomer([
        'name' => 'Has Photo',
        'profile_photo' => 'profile-photos/photo.jpg',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.show', $customer))
        ->assertOk()
        ->assertSee('Has Photo')
        ->assertSee('profile-photos/photo.jpg');
});

test('the removed admin user edit route is no longer reachable by url', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = managedCustomer();

    $this->actingAs($admin)
        ->get("/admin/users/{$customer->id}/edit")
        ->assertNotFound();
});

test('the removed admin user update route cannot write to a user account', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = managedCustomer();

    $original = $customer->only(['name', 'email', 'phone', 'is_admin']);

    $this->actingAs($admin)
        ->put("/admin/users/{$customer->id}", [
            'name' => 'Hijacked',
            'email' => 'hijacked@example.com',
            'phone' => '9800000000',
            'is_admin' => true,
        ])
        ->assertStatus(405);
    expect($customer->refresh()->only(['name', 'email', 'phone', 'is_admin']))
        ->toEqual($original);
});

test('an administrator cannot change another account through any user management endpoint', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = managedCustomer(['name' => 'Hands Off']);

    $original = $customer->only(['name', 'email', 'phone', 'is_admin']);

    foreach ([
        ['post', "/admin/users/{$customer->id}"],
        ['patch', "/admin/users/{$customer->id}"],
        ['put', "/admin/users/{$customer->id}"],
        ['delete', "/admin/users/{$customer->id}"],
        ['post', "/admin/users/{$customer->id}/edit"],
        ['put', "/admin/users/{$customer->id}/edit"],
    ] as [$method, $uri]) {
        $response = $this->actingAs($admin)->$method($uri, [
            'name' => 'Hijacked',
            'email' => 'hijacked@example.com',
            'is_admin' => true,
        ]);

        expect($response->status())->toBeIn([404, 405]);
    }

    expect($customer->refresh()->only(['name', 'email', 'phone', 'is_admin']))
        ->toEqual($original);
});
test('an administrator cannot promote a customer from user management', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($admin)
        ->post("/admin/users/{$customer->id}", ['is_admin' => true, 'name' => 'Promoted']);

    expect($response->status())->toBeIn([404, 405]);
    expect($customer->refresh()->isAdmin())->toBeFalse();
});

test('a normal user cannot open the user view page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = User::factory()->create(['is_admin' => false]);

    $this->actingAs($customer)
        ->get(route('admin.users.show', $admin))
        ->assertForbidden();

    $this->actingAs($customer)
        ->get(route('admin.users.show', $customer))
        ->assertForbidden();
});

test('a guest is redirected to login before opening the user view page', function () {
    $customer = User::factory()->create(['is_admin' => false]);

    $this->get(route('admin.users.show', $customer))->assertRedirect('/login');
});

test('user management still opens a missing user with a 404', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/users/999999')
        ->assertNotFound();
});

test('an administrator still edits their own account from their own profile', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Personal Information')
        ->assertSee('Administrator Information');

    $this->actingAs($admin)
        ->patch(route('profile.update'), [
            'name' => 'Admin Own Profile',
            'email' => $admin->email,
            'phone' => '9800000000',
            'address' => 'Admin Street',
            'city' => 'Admin City',
            'country' => 'Nepal',
        ])
        ->assertRedirect(route('profile.edit'));

    expect($admin->refresh()->name)->toBe('Admin Own Profile')
        ->and($admin->isAdmin())->toBeTrue();
});
