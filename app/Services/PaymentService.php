<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\RegistrationApplication;
use App\Models\Setting;
use Illuminate\Support\Str;

class PaymentService
{
    public static function feeForType(string $type): int
    {
        return match ($type) {
            RegistrationApplication::TYPE_FEDERATION => Setting::fee('federation'),
            RegistrationApplication::TYPE_CLUB => Setting::fee('club'),
            default => 0,
        };
    }

    public static function windowMinutes(): int
    {
        return Setting::sessionMinutes();
    }

    /**
     * Real Razorpay is used only when API keys are configured; otherwise a
     * test-mode stub lets the workflow complete locally.
     */
    public static function isTestMode(): bool
    {
        return empty(config('services.razorpay.key')) || empty(config('services.razorpay.secret'));
    }

    public static function createOrder(RegistrationApplication $application, int $amount): Payment
    {
        $orderId = self::isTestMode()
            ? 'TEST_'.Str::upper(Str::random(16))
            : self::createRazorpayOrder($amount, $application->reference_no);

        return $application->payment()->create([
            'user_id' => null,
            'amount' => $amount,
            'currency' => 'INR',
            'gateway_order_id' => $orderId,
            'status' => Payment::STATUS_CREATED,
        ]);
    }

    /**
     * Mark a payment as paid and move the application into review.
     */
    public static function markPaid(Payment $payment, ?string $gatewayPaymentId = null, ?string $signature = null): void
    {
        $payment->update([
            'status' => Payment::STATUS_PAID,
            'gateway_payment_id' => $gatewayPaymentId ?? 'TEST_PAY_'.Str::upper(Str::random(12)),
            'gateway_signature' => $signature,
        ]);

        $application = $payment->payable;

        if ($application instanceof RegistrationApplication) {
            $application->update([
                'status' => RegistrationApplication::STATUS_UNDER_REVIEW,
                'submitted_at' => now(),
                'expires_at' => null,
            ]);

            AuditService::logModel('payment_completed', $application, ['amount' => $payment->amount]);
        }
    }

    protected static function createRazorpayOrder(int $amount, string $receipt): string
    {
        $api = new \Razorpay\Api\Api(config('services.razorpay.key'), config('services.razorpay.secret'));

        $order = $api->order->create([
            'amount' => $amount,
            'currency' => 'INR',
            'receipt' => $receipt,
            'payment_capture' => 1,
        ]);

        return $order['id'];
    }
}
