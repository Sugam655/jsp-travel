<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Hotels\Models\Hotel;
use Modules\Transport\Models\TransportVehicle;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['Home', 'Bookings', 'Tours', 'Hotels', 'Transport'] as $module) {
        Artisan::call('module:migrate', ['module' => $module, '--force' => true]);
    }
});

test('the hotel listing filters by location on the server', function () {
    Hotel::query()->create([
        'title' => 'Pokhara Lakeside Hotel',
        'slug' => 'pokhara-lakeside-hotel',
        'location' => 'Pokhara',
        'rating' => 5,
        'price' => 12000,
        'is_active' => true,
    ]);

    Hotel::query()->create([
        'title' => 'Kathmandu Heritage Stay',
        'slug' => 'kathmandu-heritage-stay',
        'location' => 'Kathmandu',
        'rating' => 3,
        'price' => 4000,
        'is_active' => true,
    ]);

    $this->get(route('hotel', ['location' => 'Pokhara']))
        ->assertOk()
        ->assertSee('Pokhara Lakeside Hotel')
        ->assertDontSee('Kathmandu Heritage Stay');
});

test('the hotel listing filters by star rating and maximum price on the server', function () {
    Hotel::query()->create([
        'title' => 'Five Star Retreat',
        'slug' => 'five-star-retreat',
        'location' => 'Pokhara',
        'rating' => 5,
        'price' => 40000,
        'is_active' => true,
    ]);

    Hotel::query()->create([
        'title' => 'Budget Guesthouse',
        'slug' => 'budget-guesthouse',
        'location' => 'Pokhara',
        'rating' => 2,
        'price' => 2500,
        'is_active' => true,
    ]);

    $this->get(route('hotel', ['rating' => 5]))
        ->assertOk()
        ->assertSee('Five Star Retreat')
        ->assertDontSee('Budget Guesthouse');

    $this->get(route('hotel', ['max_price' => 5000]))
        ->assertOk()
        ->assertSee('Budget Guesthouse')
        ->assertDontSee('Five Star Retreat');
});

test('a hotel search with no matches shows an empty state instead of default hotels', function () {
    Hotel::query()->create([
        'title' => 'Only Hotel',
        'slug' => 'only-hotel',
        'location' => 'Dhulikhel',
        'rating' => 4,
        'price' => 3000,
        'is_active' => true,
    ]);

    $this->get(route('hotel', ['location' => 'Nowhere']))
        ->assertOk()
        ->assertSee('No hotels match your search')
        ->assertDontSee('Only Hotel');
});

test('the transport listing filters by brand, model and vehicle type on the server', function () {
    TransportVehicle::query()->create([
        'name' => 'Toyota Hiace',
        'slug' => 'toyota-hiace',
        'brand' => 'Toyota',
        'model' => 'Hiace',
        'vehicle_type' => 'van',
        'price' => 9000,
        'is_active' => true,
    ]);

    TransportVehicle::query()->create([
        'name' => 'Mahindra Bolero',
        'slug' => 'mahindra-bolero',
        'brand' => 'Mahindra',
        'model' => 'Bolero',
        'vehicle_type' => 'jeep',
        'price' => 7000,
        'is_active' => true,
    ]);

    $this->get(route('transport', ['brand' => 'Mahindra']))
        ->assertOk()
        ->assertSee('Mahindra Bolero')
        ->assertDontSee('Toyota Hiace');

    $this->get(route('transport', ['model' => 'Hiace']))
        ->assertOk()
        ->assertSee('Toyota Hiace')
        ->assertDontSee('Mahindra Bolero');

    $this->get(route('transport', ['vehicle_type' => 'van']))
        ->assertOk()
        ->assertSee('Toyota Hiace')
        ->assertDontSee('Mahindra Bolero');
});

test('the transport listing filters by maximum price on the server', function () {
    TransportVehicle::query()->create([
        'name' => 'Luxury SUV',
        'slug' => 'luxury-suv',
        'brand' => 'Toyota',
        'model' => 'Fortuner',
        'vehicle_type' => 'car',
        'price' => 22000,
        'is_active' => true,
    ]);

    TransportVehicle::query()->create([
        'name' => 'City Hatchback',
        'slug' => 'city-hatchback',
        'brand' => 'Suzuki',
        'model' => 'Swift',
        'vehicle_type' => 'car',
        'price' => 4000,
        'is_active' => true,
    ]);

    $this->get(route('transport', ['max_price' => 5000]))
        ->assertOk()
        ->assertSee('City Hatchback')
        ->assertDontSee('Luxury SUV');
});

test('invalid filter values are rejected instead of being used in the query', function () {
    $this->get(route('hotel', ['rating' => 99]))->assertSessionHasErrors('rating');
    $this->get(route('transport', ['max_price' => -5]))->assertSessionHasErrors('max_price');
});
