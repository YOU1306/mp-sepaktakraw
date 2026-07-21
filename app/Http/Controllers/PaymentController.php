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

        abort_unless($application->status === RegistrationApplication::STATUS_PENDING_PAYMENT, 404);

        $payment = $application->payment;
        abort_unless($payment instanceof Payment, 404);

        if (PaymentService::isTestMode()) {
            // Test-mode: simulate a successful gateway payment.
            PaymentService::markPaid($payment);
        } else {
            $validated = $request->validate([
                'razorpay_payment_id' => ['required', 'string'],
                'razorpay_signature' => ['required', 'string'],
            ]);
            // Signature verification would happen here before marking paid.
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
            RegistrationApplication::TYPE_CLUB => 'register.club.success',
            default => 'register.individual.success',
        };
    }
}
