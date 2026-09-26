<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/my-account');
});

test('administrators authenticate on the login screen', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($admin);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('an authenticated customer who opens /login or /register is sent to their dashboard, never the admin area', function () {
    $customer = User::factory()->create(['is_admin' => false]);

    $this->actingAs($customer)->get('/login')->assertRedirect('/my-account');
    $this->actingAs($customer)->get('/register')->assertRedirect('/my-account');
    $this->actingAs($customer)->get('/my-account')->assertOk();

    $this->actingAs($customer)->get('/dashboard')->assertForbidden();
});

test('an authenticated administrator who opens /login is sent to the admin dashboard', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get('/login')->assertRedirect('/dashboard');
    $this->actingAs($admin)->get('/dashboard')->assertOk();
});

test('an administrator logging in lands directly on the main admin dashboard, not a booking section', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($admin);
    $response->assertRedirect('/admin/dashboard');
    $this->get('/admin/dashboard')->assertOk();
});

test('an administrator logging in is always sent to the main dashboard, never an intended booking page', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->withSession(['url.intended' => '/admin/bookings']);

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect('/admin/dashboard');
});
