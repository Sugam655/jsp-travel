<?php

namespace Modules\Tours\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Tours\Models\Tour;

class ToursDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's tours with the default packages. The seed is
     * idempotent: existing tours are matched by slug and updated instead of
     * being duplicated.
     */
    public function run(): void
    {
        foreach (Tour::DEFAULTS as $item) {
            Tour::query()->updateOrCreate(
                ['slug' => $item['slug']],
                $item
            );
        }
    }
}
