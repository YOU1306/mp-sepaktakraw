<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('data/mp-districts.json');

        if (! File::exists($path)) {
            $this->command?->warn('District data file not found at data/mp-districts.json');

            return;
        }

        $districts = json_decode(File::get($path), true);

        foreach ($districts as $district) {
            District::query()->updateOrCreate(
                ['code' => $district['code']],
                ['name' => $district['name']],
            );
        }
    }
}
