<?php

namespace Modules\Home\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Home\Models\HomeDestination;
use Modules\Home\Models\HomeHero;
use Modules\Home\Models\HomeService;
use Modules\Home\Models\HomeStory;
use Modules\Home\Models\HomeWhyChooseUs;

class HomeDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (HomeHero::query()->doesntExist()) {
            HomeHero::query()->create(HomeHero::DEFAULTS + ['is_active' => true]);
        }

        if (HomeDestination::query()->doesntExist()) {
            foreach (HomeDestination::DEFAULTS as $index => $default) {
                HomeDestination::query()->create($default + [
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]);
            }
        }

        if (HomeWhyChooseUs::query()->doesntExist()) {
            HomeWhyChooseUs::query()->create(HomeWhyChooseUs::DEFAULTS + ['is_active' => true]);
        }

        if (HomeStory::query()->doesntExist()) {
            foreach (HomeStory::DEFAULTS as $index => $default) {
                HomeStory::query()->create($default + [
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]);
            }
        }

        if (HomeService::query()->doesntExist()) {
            foreach (HomeService::DEFAULTS as $index => $default) {
                HomeService::query()->create($default + [
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]);
            }
        }
    }
}
