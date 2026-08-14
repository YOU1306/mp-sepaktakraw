<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'fee_individual_quarterly' => '100',
            'fee_individual_half_yearly' => '190',
            'fee_individual_yearly' => '360',
            'fee_federation_quarterly' => '500',
            'fee_federation_half_yearly' => '950',
            'fee_federation_yearly' => '1800',
            Setting::REGISTRATION_SESSION_MINUTES => '50',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
