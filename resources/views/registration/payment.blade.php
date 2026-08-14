@extends('layouts.public')

@section('title', 'Payment')

@section('content')
    <div class="max-w-lg mx-auto">
        <div class="rounded-t-xl bg-gradient-to-r from-emerald-900 to-emerald-800 text-white px-6 py-5 border-b-4 border-orange-500">
            <h1 class="text-xl font-bold">Registration Payment</h1>
            <p class="text-emerald-100 text-sm mt-1">Reference: <span class="font-mono">{{ $application->reference_no }}</span></p>
        </div>

        <div class="rounded-b-xl bg-white border border-t-0 border-stone-200 shadow-sm px-6 py-7">
            <div class="flex items-baseline justify-between border-b border-stone-100 pb-4 mb-4">
                <span class="text-stone-600">Amount payable</span>
                <span class="text-2xl font-bold text-emerald-900">₹{{ number_format($payment->amount, 2) }}</span>
            </div>

            <dl class="text-sm space-y-2 mb-6">
                <div class="flex justify-between"><dt class="text-stone-500">Applicant</dt><dd class="text-stone-800 font-medium">{{ $application->applicant_name }}</dd></div>
                <div class="flex justify-between"><dt class="text-stone-500">Type</dt><dd class="text-stone-800 font-medium">{{ ucfirst($application->type) }}</dd></div>
                @if ($application->billing_period)
                    <div class="flex justify-between"><dt class="text-stone-500">Billing period</dt><dd class="text-stone-800 font-medium">{{ \App\Models\Setting::PERIODS[$application->billing_period] ?? ucfirst($application->billing_period) }}</dd></div>
                @endif
            </dl>

            @if ($deadline)
                <div class="mb-5 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                    Complete payment before <strong id="deadline">{{ $deadline->format('d M Y, H:i') }}</strong>.
                    <span id="countdown" class="font-mono"></span>
                </div>
            @endif

            <form method="POST" action="{{ route('register.payment.process', $application->reference_no) }}">
                @csrf
                @if ($testMode)
                    <p class="text-xs text-stone-500 mb-3 text-center">Payment gateway is in <strong>test mode</strong> (no live keys configured). Click below to simulate a successful payment.</p>
                    <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-lg shadow-sm">
                        Pay ₹{{ number_format($payment->amount, 2) }} (Test Mode)
                    </button>
                @else
                    <p class="text-xs text-stone-500 mb-3 text-center">You will be redirected to the secure Razorpay gateway.</p>
                    <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-lg shadow-sm">
                        Pay ₹{{ number_format($payment->amount, 2) }}
                    </button>
                @endif
            </form>
        </div>
    </div>

    @if ($deadline)
        <script>
            (function () {
                const end = new Date(@json($deadline->toIso8601String())).getTime();
                const el = document.getElementById('countdown');
                function tick() {
                    const diff = end - Date.now();
                    if (diff <= 0) { el.textContent = ' — expired'; location.reload(); return; }
                    const m = Math.floor(diff / 60000), s = Math.floor((diff % 60000) / 1000);
                    el.textContent = ' — ' + m + 'm ' + String(s).padStart(2, '0') + 's left';
                }
                tick(); setInterval(tick, 1000);
            })();
        </script>
    @endif
@endsection
