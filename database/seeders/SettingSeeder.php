<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            Setting::FEE_INDIVIDUAL => '0',
            Setting::FEE_FEDERATION => '0',
            Setting::FEE_CLUB => '0',
            Setting::REGISTRATION_SESSION_MINUTES => '30',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
