@extends('layouts.public')

@section('title', 'Complete Payment')

@section('content')
    <div class="max-w-lg mx-auto">
        <div class="rounded-t-xl bg-gradient-to-r from-emerald-900 to-emerald-800 text-white px-6 py-5 border-b-4 border-orange-500">
            <h1 class="text-xl font-bold">Complete Membership Payment</h1>
            <p class="text-emerald-100 text-sm mt-1">User ID: <span class="font-mono">{{ $user->user_id }}</span></p>
        </div>

        <div class="rounded-b-xl bg-white border border-t-0 border-stone-200 shadow-sm px-6 py-7">
            <div class="flex items-baseline justify-between border-b border-stone-100 pb-4 mb-4">
                <span class="text-stone-600">Amount payable</span>
                <span class="text-2xl font-bold text-emerald-900">₹{{ number_format($payment->amount, 2) }}</span>
            </div>

            <dl class="text-sm space-y-2 mb-6">
                <div class="flex justify-between"><dt class="text-stone-500">Member</dt><dd class="text-stone-800 font-medium">{{ $user->name }}</dd></div>
                <div class="flex justify-between"><dt class="text-stone-500">Billing period</dt><dd class="text-stone-800 font-medium">{{ $periodLabel }}</dd></div>
            </dl>

            <form method="POST" action="{{ route('membership.renew.confirm', $payment) }}" id="pay-form">
                @csrf
                <input type="hidden" name="razorpay_order_id" value="{{ $payment->gateway_order_id }}">
                <input type="hidden" name="razorpay_payment_id" value="">
                <input type="hidden" name="razorpay_signature" value="">

                <p class="text-xs text-stone-500 mb-3 text-center">You will be redirected to the secure Razorpay gateway.</p>
                <button type="button" id="rzp-button" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-lg shadow-sm">
                    Pay ₹{{ number_format($payment->amount, 2) }}
                </button>
            </form>
        </div>
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        const rzpButton = document.getElementById('rzp-button');
        const payForm = document.getElementById('pay-form');
        rzpButton.addEventListener('click', function () {
            rzpButton.disabled = true;
            const rzp = new Razorpay({
                key: @json(config('services.razorpay.key')),
                amount: {{ intval($payment->amount) * 100 }},
                currency: 'INR',
                name: 'MP Sepaktakraw Federation',
                description: 'Membership renewal ' + @json($user->user_id),
                order_id: @json($payment->gateway_order_id),
                prefill: {
                    name: @json($user->name),
                    email: @json($user->email),
                    contact: @json($user->phone),
                },
                notes: {
                    user_id: @json($user->user_id),
                    billing_period: @json($payment->billing_period),
                },
                handler: function (response) {
                    payForm.querySelector('[name=razorpay_payment_id]').value = response.razorpay_payment_id;
                    payForm.querySelector('[name=razorpay_signature]').value = response.razorpay_signature;
                    payForm.submit();
                },
                modal: {
                    ondismiss: function () {
                        rzpButton.disabled = false;
                    }
                },
                theme: { color: '#f97316' }
            });
            rzp.on('payment.failed', function () {
                rzpButton.disabled = false;
                alert('Payment failed. Please try again.');
            });
            rzp.open();
        });
    </script>
@endsection
