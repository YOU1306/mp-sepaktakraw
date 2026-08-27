<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\RegistrationApplication;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function show(string $reference): View|RedirectResponse
    {
        $application = RegistrationApplication::where('reference_no', $reference)->firstOrFail();

        if ($this->expireIfNeeded($application)) {
            return view('registration.payment-expired', ['application' => $application]);
        }

        if ($application->status !== RegistrationApplication::STATUS_PENDING_PAYMENT) {
            return redirect()->route($this->successRoute($application), ['ref' => $application->reference_no]);
        }

        $payment = $application->payment;

        return view('registration.payment', [
            'application' => $application,
            'payment' => $payment,
            'testMode' => PaymentService::isTestMode(),
            'deadline' => $application->expires_at,
        ]);
    }

    public function process(Request $request, string $reference): RedirectResponse
    {
        $application = RegistrationApplication::where('reference_no', $reference)->firstOrFail();

        if ($this->expireIfNeeded($application)) {
            return redirect()->route('register.payment', $application->reference_no);
        }

        // Browser refresh / double-submit after a successful Checkout callback
        // (or after the webhook already completed the payment) should land on
        // the success page, not 404.
        if ($application->status !== RegistrationApplication::STATUS_PENDING_PAYMENT) {
            return redirect()->route($this->successRoute($application), ['ref' => $application->reference_no]);
        }

        $payment = $application->payment;
        abort_unless($payment instanceof Payment, 404);

        if ($payment->status === Payment::STATUS_PAID) {
            return redirect()->route($this->successRoute($application), ['ref' => $application->reference_no]);
        }

        if (PaymentService::isTestMode()) {
            // Test-mode: simulate a successful gateway payment.
            PaymentService::markPaid($payment);
        } else {
            $validated = $request->validate([
                'razorpay_order_id' => ['required', 'string'],
                'razorpay_payment_id' => ['required', 'string'],
                'razorpay_signature' => ['required', 'string'],
            ]);

            // The browser callback must be for THIS order — never trust a
            // payment id alone; the signature is what proves authenticity.
            abort_unless(hash_equals($payment->gateway_order_id, $validated['razorpay_order_id']), 400, 'Order mismatch.');

            abort_unless(
                PaymentService::verifySignature(
                    $validated['razorpay_order_id'],
                    $validated['razorpay_payment_id'],
                    $validated['razorpay_signature']
                ),
                400,
                'Payment signature verification failed.'
            );

            PaymentService::markPaid($payment, $validated['razorpay_payment_id'], $validated['razorpay_signature']);
        }

        return redirect()->route($this->successRoute($application), ['ref' => $application->reference_no]);
    }

    protected function expireIfNeeded(RegistrationApplication $application): bool
    {
        if ($application->status === RegistrationApplication::STATUS_PENDING_PAYMENT
            && $application->expires_at
            && $application->expires_at->isPast()) {
            $application->update(['status' => RegistrationApplication::STATUS_EXPIRED]);

            return true;
        }

        return $application->status === RegistrationApplication::STATUS_EXPIRED;
    }

    protected function successRoute(RegistrationApplication $application): string
    {
        return match ($application->type) {
            RegistrationApplication::TYPE_FEDERATION => 'register.federation.success',
            default => 'register.individual.success',
        };
    }
}
