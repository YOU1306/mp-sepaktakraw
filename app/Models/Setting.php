<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const FEE_INDIVIDUAL = 'fee_individual';

    public const FEE_FEDERATION = 'fee_federation';

    public const REGISTRATION_SESSION_MINUTES = 'registration_session_minutes';

    public const PERIOD_QUARTERLY = 'quarterly';

    public const PERIOD_HALF_YEARLY = 'half_yearly';

    public const PERIOD_YEARLY = 'yearly';

    public const PERIODS = [
        self::PERIOD_QUARTERLY => 'Quarterly (3 months)',
        self::PERIOD_HALF_YEARLY => 'Half-yearly (6 months)',
        self::PERIOD_YEARLY => 'Yearly (12 months)',
    ];

    public const PERIOD_MONTHS = [
        self::PERIOD_QUARTERLY => 3,
        self::PERIOD_HALF_YEARLY => 6,
        self::PERIOD_YEARLY => 12,
    ];

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

    /**
     * Fee for a registration type ("individual" | "federation") for a given billing period.
     */
    public static function feeForPeriod(string $type, string $period): int
    {
        return (int) static::get("fee_{$type}_{$period}", 0);
    }

    /**
     * @return array<string, int> period => amount
     */
    public static function feesForType(string $type): array
    {
        $fees = [];
        foreach (array_keys(self::PERIODS) as $period) {
            $fees[$period] = self::feeForPeriod($type, $period);
        }

        return $fees;
    }

    public static function periodMonths(string $period): int
    {
        return self::PERIOD_MONTHS[$period] ?? 12;
    }

    public static function sessionMinutes(): int
    {
        return (int) static::get(self::REGISTRATION_SESSION_MINUTES, 50);
    }
}
