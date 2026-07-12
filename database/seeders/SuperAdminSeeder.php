<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL', 'superadmin@mpsepaktakraw.test')],
            [
                'user_id' => env('SUPER_ADMIN_USER_ID', 'SA000001'),
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'ChangeMeNow!')),
                'email_verified_at' => now(),
                'status' => User::STATUS_ACTIVE,
                'must_change_password' => false,
            ],
        );

        $user->syncRoles(['super-admin']);
    }
}
