<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around MSG91 (recommended provider). Falls back to a
 * test-mode stub (logs instead of sending) when no API key is configured,
 * matching the same pattern used by PaymentService for Razorpay.
 *
 * Indian DLT rules require every transactional SMS to map to an approved
 * template. Each message type therefore has its own MSG91 Flow template ID
 * (configurable in .env under services.msg91.templates). When a template is
 * configured the Flow API is called with the message text as the template's
 * first content variable ("1"); otherwise the raw message is sent as free
 * text, which DLT-locked accounts will reject — but keeps the integration
 * working in test mode / non-DLT setups.
 */
class SmsService
{
    /** Message types mapped to MSG91 Flow template IDs in config/services.php. */
    public const TPL_OTP = 'otp';

    public const TPL_APPROVED = 'approved';

    public const TPL_REJECTED = 'rejected';

    public const TPL_MEMBERSHIP_EXPIRY = 'membership_expiry';

    public static function isTestMode(): bool
    {
        return empty(config('services.msg91.auth_key'));
    }

    public static function send(string $phone, string $message, ?string $templateKey = null): bool
    {
        if (self::isTestMode()) {
            Log::info('[SMS test-mode] To: '.$phone.' | Template: '.($templateKey ?? '-').' | Message: '.$message);

            return true;
        }

        $payload = [
            'authkey' => config('services.msg91.auth_key'),
            'mobiles' => '91'.preg_replace('/\D/', '', $phone),
            'sender' => config('services.msg91.sender_id'),
            'route' => '4',
        ];

        $templateId = $templateKey ? config("services.msg91.templates.$templateKey") : null;

        if ($templateId) {
            // DLT-compliant send via the Flow API: the whole message text is the
            // template's first content variable. Your DLT template must have a
            // single {{variable}} placeholder for the full sentence.
            $payload['template_id'] = $templateId;
            $payload['variables'] = json_encode(['1' => $message]);
        } else {
            // Free-text fallback — kept so sends degrade gracefully until the
            // template IDs are registered and added to .env.
            $payload['message'] = $message;
        }

        $response = Http::asForm()->post('https://control.msg91.com/api/v5/flow/', $payload);

        if (! $response->successful()) {
            Log::warning('MSG91 SMS send failed', ['phone' => $phone, 'response' => $response->body()]);
        }

        return $response->successful();
    }

    public static function sendOtp(string $phone, string $code): bool
    {
        return self::send(
            $phone,
            "Your verification code for MP Sepaktakraw registration is {$code}. Valid for 10 minutes. Do not share this code.",
            self::TPL_OTP
        );
    }
}
