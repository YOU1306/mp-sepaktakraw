<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\AuditService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Razorpay webhook endpoint (payment.captured). This is the authoritative
 * source of truth for a payment: it completes registrations/memberships even
 * when the user closes the browser before the Checkout callback fires, and it
 * is safe against replays/duplicates because markPaid() only acts on unpaid
 * orders and every request's signature is verified against the raw body.
 */
class WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // Signature must be computed over the RAW body exactly as received.
        $payload = $request->getContent();

        if (! PaymentService::verifyWebhook($payload, $request->header('X-Razorpay-Signature'))) {
            Log::warning('Razorpay webhook rejected — signature verification failed.');

            return response()->json(['verified' => false], 400);
        }

        $data = json_decode($payload, true);
        $event = $data['event'] ?? null;
        $entity = $data['payload']['payment']['entity'] ?? null;

        if ($event !== 'payment.captured' || ! $entity) {
            return response()->json(['verified' => true, 'ignored' => true]);
        }

        $orderId = $entity['order_id'] ?? null;
        $paymentId = $entity['id'] ?? null;

        if (! $orderId) {
            return response()->json(['verified' => true, 'ignored' => true]);
        }

        /** @var Payment|null $payment */
        $payment = Payment::query()->where('gateway_order_id', $orderId)->first();

        if (! $payment) {
            // Not one of our orders (or created before this deployment) — ack so
            // Razorpay stops retrying, but do nothing.
            Log::warning('Razorpay webhook references an unknown order.', ['order_id' => $orderId]);

            return response()->json(['verified' => true, 'ignored' => 'unknown_order']);
        }

        if ($payment->status === Payment::STATUS_PAID) {
            // Browser callback already completed it — idempotent no-op.
            return response()->json(['verified' => true, 'idempotent' => true]);
        }

        if ($payment->status === Payment::STATUS_REFUNDED) {
            return response()->json(['verified' => true, 'ignored' => 'refunded']);
        }

        PaymentService::markPaid($payment, $paymentId);

        AuditService::log('webhook_payment_captured', 'payments', $payment->id, [
            'order_id' => $orderId,
            'payment_id' => $paymentId,
        ]);

        return response()->json(['verified' => true]);
    }
}
