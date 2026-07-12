<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SettingSeeder::class,
            DistrictSeeder::class,
            SuperAdminSeeder::class,
            DemoContentSeeder::class,
        ]);
    }
}
