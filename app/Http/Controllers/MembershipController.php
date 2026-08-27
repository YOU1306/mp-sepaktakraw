<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $feeType = $user->isSuperUser() ? 'federation' : 'individual';

        return view('membership.renew', [
            'user' => $user,
            'fees' => Setting::feesForType($feeType),
            'periods' => Setting::PERIODS,
            'testMode' => PaymentService::isTestMode(),
            'pendingPayment' => $user->payments()->where('status', Payment::STATUS_CREATED)->latest()->first(),
        ]);
    }

    /**
     * Step 1 of a renewal: pick a period and create the gateway order.
     * Test mode completes immediately; live mode hands over to the
     * Razorpay Checkout page, which posts the signed result to confirm().
     */
    public function process(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'billing_period' => ['required', 'in:'.implode(',', array_keys(Setting::PERIODS))],
        ]);

        $feeType = $user->isSuperUser() ? 'federation' : 'individual';
        $fee = PaymentService::feeForPeriod($feeType, $validated['billing_period']);

        $payment = PaymentService::createOrder($user, $fee, $validated['billing_period']);

        if (PaymentService::isTestMode()) {
            PaymentService::markPaid($payment);

            return redirect()->route('dashboard')->with('status', 'Membership renewed successfully. Your data and history are unchanged.');
        }

        return redirect()->route('membership.checkout', $payment);
    }

    /**
     * Step 2 (live mode only): Razorpay Checkout for the created order.
     */
    public function checkout(Request $request, Payment $payment): View
    {
        $this->authorizePendingPayment($request, $payment);

        return view('membership.checkout', [
            'user' => $request->user(),
            'payment' => $payment,
            'periodLabel' => Setting::PERIODS[$payment->billing_period] ?? ucfirst((string) $payment->billing_period),
        ]);
    }

    /**
     * Step 3 (live mode only): verify the browser callback signature with the
     * key secret before trusting it — never mark paid on unverified input.
     */
    public function confirm(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorizeOwnedPayment($request, $payment);

        // Webhook may have already completed this order — treat as success.
        if ($payment->status === Payment::STATUS_PAID) {
            return redirect()->route('dashboard')->with('status', 'Membership renewed successfully. Your data and history are unchanged.');
        }

        abort_unless($payment->status === Payment::STATUS_CREATED, 404);

        $validated = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        // The callback must be for THIS order — never trust a payment id alone;
        // the HMAC signature is what proves authenticity.
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

        return redirect()->route('dashboard')->with('status', 'Membership renewed successfully. Your data and history are unchanged.');
    }

    protected function authorizePendingPayment(Request $request, Payment $payment): void
    {
        $this->authorizeOwnedPayment($request, $payment);
        abort_unless($payment->status === Payment::STATUS_CREATED, 404);
    }

    protected function authorizeOwnedPayment(Request $request, Payment $payment): void
    {
        $user = $request->user();

        $owns = $payment->payable_type === (new User)->getMorphClass()
            && (int) $payment->payable_id === (int) $user?->id;

        abort_unless($owns, 404);
    }
}
