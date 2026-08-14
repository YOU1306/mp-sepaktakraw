<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around MSG91 (recommended provider). Falls back to a
 * test-mode stub (logs instead of sending) when no API key is configured,
 * matching the same pattern used by PaymentService for Razorpay.
 */
class SmsService
{
    public static function isTestMode(): bool
    {
        return empty(config('services.msg91.auth_key'));
    }

    public static function send(string $phone, string $message): bool
    {
        if (self::isTestMode()) {
            Log::info("[SMS test-mode] To: {$phone} | Message: {$message}");

            return true;
        }

        $response = Http::asForm()->post('https://control.msg91.com/api/v5/flow/', [
            'authkey' => config('services.msg91.auth_key'),
            'mobiles' => '91'.preg_replace('/\D/', '', $phone),
            'sender' => config('services.msg91.sender_id'),
            'route' => '4',
            'message' => $message,
        ]);

        if (! $response->successful()) {
            Log::warning('MSG91 SMS send failed', ['phone' => $phone, 'response' => $response->body()]);
        }

        return $response->successful();
    }

    public static function sendOtp(string $phone, string $code): bool
    {
        return self::send($phone, "Your verification code for MP Sepaktakraw registration is {$code}. Valid for 10 minutes. Do not share this code.");
    }
}
