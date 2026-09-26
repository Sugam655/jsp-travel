<?php

namespace Modules\Contact\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Contact\Models\ContactSetting;

class ContactDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (ContactSetting::query()->doesntExist()) {
            ContactSetting::query()->create(array_merge(ContactSetting::DEFAULTS, ['is_active' => true]));
        }
    }
}
