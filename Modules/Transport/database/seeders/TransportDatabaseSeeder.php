<?php

namespace Modules\Transport\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Transport\Models\TransportVehicle;

class TransportDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's transport vehicles with the default fleet. The
     * seed is idempotent: existing vehicles are matched by slug and updated
     * instead of being duplicated.
     */
    public function run(): void
    {
        foreach (TransportVehicle::DEFAULTS as $item) {
            TransportVehicle::query()->updateOrCreate(
                ['slug' => $item['slug']],
                $item
            );
        }
    }
}
