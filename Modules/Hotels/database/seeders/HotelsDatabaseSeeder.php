<?php

namespace Modules\Hotels\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Hotels\Models\Hotel;

class HotelsDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's hotels with the default list. The seed is
     * idempotent: existing hotels are matched by slug and updated instead of
     * being duplicated.
     */
    public function run(): void
    {
        foreach (Hotel::DEFAULTS as $item) {
            Hotel::query()->updateOrCreate(
                ['slug' => $item['slug']],
                $item
            );
        }
    }
}
