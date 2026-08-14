<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Setting;
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

        $validatedGateway = $request->validate([
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        PaymentService::markPaid($payment, $validatedGateway['razorpay_payment_id'], $validatedGateway['razorpay_signature']);

        return redirect()->route('dashboard')->with('status', 'Membership renewed successfully. Your data and history are unchanged.');
    }
}
