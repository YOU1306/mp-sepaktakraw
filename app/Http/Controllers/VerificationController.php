<?php

namespace App\Http\Controllers;

use App\Models\VerificationCode;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class VerificationController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel' => ['required', 'in:phone,email'],
            'destination' => ['required', 'string', 'max:255'],
        ]);

        $key = 'otp-send:'.$validated['channel'].':'.$validated['destination'];

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'message' => 'Too many attempts. Please wait a minute before requesting another code.',
            ], 429);
        }
        RateLimiter::hit($key, 60);

        if ($validated['channel'] === VerificationCode::CHANNEL_PHONE
            && ! preg_match('/^[6-9]\d{9}$/', $validated['destination'])) {
            return response()->json(['message' => 'Enter a valid 10-digit Indian mobile number first.'], 422);
        }

        if ($validated['channel'] === VerificationCode::CHANNEL_EMAIL
            && ! filter_var($validated['destination'], FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'Enter a valid email address first.'], 422);
        }

        $result = OtpService::send($validated['channel'], $validated['destination']);

        return response()->json([
            'token' => $result['token'],
            'test_code' => $result['test_code'],
            'message' => 'Verification code sent.',
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $ok = OtpService::verify($validated['token'], $validated['code']);

        if (! $ok) {
            return response()->json(['verified' => false, 'message' => 'Incorrect or expired code.'], 422);
        }

        return response()->json(['verified' => true, 'message' => 'Verified.']);
    }
}
