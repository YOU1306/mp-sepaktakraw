<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\RegistrationApplication;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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

        // Razorpay receipt max length is 40 characters.
        $receipt = Str::limit((string) $receipt, 40, '');

        $orderId = self::isTestMode()
            ? 'TEST_'.Str::upper(Str::random(16))
            : self::createRazorpayOrder($amount, $receipt, $payable);

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
     *
     * Idempotent under concurrent browser callback + webhook: a row lock
     * ensures side effects (district notify, membership extension) run once.
     */
    public static function markPaid(Payment $payment, ?string $gatewayPaymentId = null, ?string $signature = null): void
    {
        DB::transaction(function () use ($payment, $gatewayPaymentId, $signature) {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === Payment::STATUS_PAID) {
                return;
            }

            $locked->update([
                'status' => Payment::STATUS_PAID,
                'gateway_payment_id' => $gatewayPaymentId ?? 'TEST_PAY_'.Str::upper(Str::random(12)),
                'gateway_signature' => $signature ?? $locked->gateway_signature,
            ]);

            $payable = $locked->payable;

            if ($payable instanceof RegistrationApplication) {
                $payable->update([
                    'status' => RegistrationApplication::STATUS_UNDER_REVIEW,
                    'submitted_at' => now(),
                    'expires_at' => null,
                ]);

                AuditService::logModel('payment_completed', $payable, ['amount' => $locked->amount]);

                NotificationService::notifyDistrictOfPayment($payable);
            }

            if ($payable instanceof User) {
                $period = $locked->billing_period ?? $payable->membership_period ?? Setting::PERIOD_QUARTERLY;
                $base = $payable->isMembershipExpired() || ! $payable->membership_expires_at
                    ? now()
                    : $payable->membership_expires_at;

                $payable->update([
                    'membership_period' => $period,
                    'membership_expires_at' => $base->copy()->addMonths(Setting::periodMonths($period)),
                    'membership_reminder_sent_at' => null,
                ]);

                AuditService::logModel('membership_renewed', $payable, ['amount' => $locked->amount, 'period' => $period]);
            }
        });
    }

    /**
     * Verify the HMAC-SHA256 signature Razorpay returns to the browser for an
     * order/payment. Format: hash_hmac('sha256', order_id . "|" . payment_id, secret).
     * Always compare with hash_equals to avoid timing attacks.
     */
    public static function verifySignature(string $orderId, string $paymentId, string $signature): bool
    {
        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, (string) config('services.razorpay.secret'));

        return hash_equals($expected, $signature);
    }

    /**
     * Verify the HMAC-SHA256 signature on a Razorpay webhook request. The body
     * must be the RAW request payload (not json-encoded/decoded) and the header
     * is X-Razorpay-Signature. Returns false if no webhook secret is configured.
     */
    public static function verifyWebhook(string $rawBody, ?string $signatureHeader): bool
    {
        $secret = (string) config('services.razorpay.webhook_secret');

        if ($secret === '' || ! $signatureHeader) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signatureHeader);
    }

    /**
     * $amountRupees is stored as-is in our DB; Razorpay's API requires paise,
     * so it is only multiplied by 100 at this API boundary.
     */
    protected static function createRazorpayOrder(int $amountRupees, string $receipt, Model $payable): string
    {
        $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

        $notes = [
            'payable_type' => class_basename($payable),
            'payable_id' => (string) $payable->getKey(),
        ];

        if ($payable instanceof RegistrationApplication) {
            $notes['reference_no'] = (string) $payable->reference_no;
        }

        if ($payable instanceof User) {
            $notes['user_id'] = (string) $payable->user_id;
        }

        $order = $api->order->create([
            'amount' => $amountRupees * 100,
            'currency' => 'INR',
            'receipt' => $receipt,
            'payment_capture' => 1,
            'notes' => $notes,
        ]);

        return $order['id'];
    }
}
