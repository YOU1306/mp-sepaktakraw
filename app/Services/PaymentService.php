<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\RegistrationApplication;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Razorpay\Api\Api;

class PaymentService
{
    public static function feeForPeriod(string $type, string $period): int
    {
        return Setting::feeForPeriod($type, $period);
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

    public static function createOrder(Model $payable, int $amount, ?string $period = null): Payment
    {
        $receipt = $payable->reference_no
            ?? $payable->user_id
            ?? Str::upper(Str::random(10));

        $orderId = self::isTestMode()
            ? 'TEST_'.Str::upper(Str::random(16))
            : self::createRazorpayOrder($amount, $receipt);

        return $payable->payments()->create([
            'user_id' => $payable instanceof User ? $payable->id : null,
            'amount' => $amount,
            'currency' => 'INR',
            'gateway_order_id' => $orderId,
            'status' => Payment::STATUS_CREATED,
            'billing_period' => $period,
        ]);
    }

    /**
     * Mark a payment as paid and move the application into review, or
     * extend a membership renewal — depending on what it is paying for.
     */
    public static function markPaid(Payment $payment, ?string $gatewayPaymentId = null, ?string $signature = null): void
    {
        $payment->update([
            'status' => Payment::STATUS_PAID,
            'gateway_payment_id' => $gatewayPaymentId ?? 'TEST_PAY_'.Str::upper(Str::random(12)),
            'gateway_signature' => $signature,
        ]);

        $payable = $payment->payable;

        if ($payable instanceof RegistrationApplication) {
            $payable->update([
                'status' => RegistrationApplication::STATUS_UNDER_REVIEW,
                'submitted_at' => now(),
                'expires_at' => null,
            ]);

            AuditService::logModel('payment_completed', $payable, ['amount' => $payment->amount]);

            NotificationService::notifyDistrictOfPayment($payable);
        }

        if ($payable instanceof User) {
            $period = $payment->billing_period ?? $payable->membership_period ?? Setting::PERIOD_QUARTERLY;
            $base = $payable->isMembershipExpired() || ! $payable->membership_expires_at
                ? now()
                : $payable->membership_expires_at;

            $payable->update([
                'membership_period' => $period,
                'membership_expires_at' => $base->copy()->addMonths(Setting::periodMonths($period)),
                'membership_reminder_sent_at' => null,
            ]);

            AuditService::logModel('membership_renewed', $payable, ['amount' => $payment->amount, 'period' => $period]);
        }
    }

    /**
     * $amountRupees is stored as-is in our DB; Razorpay's API requires paise,
     * so it is only multiplied by 100 at this API boundary.
     */
    protected static function createRazorpayOrder(int $amountRupees, string $receipt): string
    {
        $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

        $order = $api->order->create([
            'amount' => $amountRupees * 100,
            'currency' => 'INR',
            'receipt' => $receipt,
            'payment_capture' => 1,
        ]);

        return $order['id'];
    }
}
