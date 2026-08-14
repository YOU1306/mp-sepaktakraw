<?php

namespace App\Services;

use App\Mail\VerificationCodeMail;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OtpService
{
    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    /**
     * @return array{token: string, test_code: ?string}
     */
    public static function send(string $channel, string $destination, string $purpose = 'registration'): array
    {
        $code = (string) random_int(100000, 999999);
        $token = Str::random(40);

        VerificationCode::create([
            'channel' => $channel,
            'destination' => $destination,
            'purpose' => $purpose,
            'code' => $code,
            'token' => $token,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        $testMode = false;

        if ($channel === VerificationCode::CHANNEL_PHONE) {
            $testMode = SmsService::isTestMode();
            SmsService::sendOtp($destination, $code);
        } else {
            $testMode = empty(config('mail.mailers.'.config('mail.default').'.transport')) === false && config('mail.default') === 'log';
            Mail::to($destination)->send(new VerificationCodeMail($code));
        }

        return [
            'token' => $token,
            // Surfaced only when no real gateway is configured, so registration
            // is testable locally/pre-launch without a live SMS/mail account.
            'test_code' => $testMode ? $code : null,
        ];
    }

    public static function verify(string $token, string $code): bool
    {
        $record = VerificationCode::where('token', $token)->first();

        if (! $record || $record->isExpired() || $record->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! hash_equals($record->code, $code)) {
            $record->increment('attempts');

            return false;
        }

        $record->update(['verified_at' => now()]);

        return true;
    }

    /**
     * Server-side re-check at submission time: the token must correspond to a
     * verified code for exactly this channel + destination.
     */
    public static function isVerifiedFor(?string $token, string $channel, string $destination): bool
    {
        if (empty($token)) {
            return false;
        }

        return VerificationCode::where('token', $token)
            ->where('channel', $channel)
            ->where('destination', $destination)
            ->whereNotNull('verified_at')
            ->where('expires_at', '>=', now()->subMinutes(self::TTL_MINUTES))
            ->exists();
    }
}
