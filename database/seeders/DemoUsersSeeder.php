<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local/testing-only sample logins covering every role, so the whole
 * permission matrix can be exercised without going through each
 * registration + approval flow by hand. Not intended for production
 * (see DemoContentSeeder for the same convention).
 */
class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $bhopal = District::query()->where('code', 'BP')->first();

        $accounts = [
            [
                'role' => 'admin',
                'user_id' => 'AD000001',
                'email' => 'admin.demo@mpsepaktakraw.test',
                'name' => 'Demo Admin',
                'password' => 'AdminDemo@123',
            ],
            [
                'role' => 'super-user',
                'user_id' => 'SU000001',
                'email' => 'superuser.demo@mpsepaktakraw.test',
                'name' => 'Demo District Federation (Bhopal)',
                'password' => 'SuperUserDemo@123',
                'district_id' => $bhopal?->id,
                'membership_period' => 'yearly',
                'membership_expires_at' => now()->addMonths(12),
            ],
            [
                'role' => 'user',
                'user_id' => 'PLR000001',
                'email' => 'player.demo@mpsepaktakraw.test',
                'name' => 'Demo Player',
                'password' => 'PlayerDemo@123',
                'district_id' => $bhopal?->id,
                'membership_period' => 'quarterly',
                'membership_expires_at' => now()->addMonths(3),
            ],
        ];

        foreach ($accounts as $account) {
            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'user_id' => $account['user_id'],
                    'name' => $account['name'],
                    'password' => Hash::make($account['password']),
                    'email_verified_at' => now(),
                    'status' => User::STATUS_ACTIVE,
                    'must_change_password' => false,
                    'district_id' => $account['district_id'] ?? null,
                    'membership_period' => $account['membership_period'] ?? null,
                    'membership_expires_at' => $account['membership_expires_at'] ?? null,
                ],
            );

            $user->syncRoles([$account['role']]);
        }
    }
}
