<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const FEE_INDIVIDUAL = 'fee_individual';
    public const FEE_FEDERATION = 'fee_federation';
    public const FEE_CLUB = 'fee_club';
    public const REGISTRATION_SESSION_MINUTES = 'registration_session_minutes';

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting.$key", function () use ($key, $default) {
            return static::query()->where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.$key");
    }

    public static function fee(string $type): int
    {
        return (int) static::get('fee_'.$type, 0);
    }

    public static function sessionMinutes(): int
    {
        return (int) static::get(self::REGISTRATION_SESSION_MINUTES, 30);
    }
}
