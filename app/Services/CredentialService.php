<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CredentialService
{
    public const PREFIXES = [
        'super-admin' => 'SA',
        'admin' => 'AD',
        'super-user' => 'SU',
        'user' => 'PLR',
    ];

    public static function generateUserId(string $roleOrType): string
    {
        $prefix = self::PREFIXES[$roleOrType] ?? 'USR';

        do {
            $candidate = $prefix.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (User::query()->where('user_id', $candidate)->exists());

        return $candidate;
    }

    public static function generatePassword(int $length = 10): string
    {
        return Str::password($length, letters: true, numbers: true, symbols: false);
    }

    /**
     * Create a login account with a generated user_id + temporary password.
     *
     * @return array{user: User, user_id: string, password: string}
     */
    public static function createAccount(string $role, array $attributes, ?string $type = null): array
    {
        $userId = self::generateUserId($type ?? $role);
        $password = self::generatePassword();

        $user = User::create(array_merge($attributes, [
            'user_id' => $userId,
            'password' => Hash::make($password),
            'must_change_password' => true,
            'status' => User::STATUS_ACTIVE,
        ]));

        $user->assignRole($role);

        return ['user' => $user, 'user_id' => $userId, 'password' => $password];
    }
}
