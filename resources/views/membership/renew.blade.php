@extends('layouts.public')

@section('title', 'Renew Membership')

@section('content')
    <div class="max-w-lg mx-auto">
        <div class="rounded-t-xl bg-gradient-to-r from-emerald-900 to-emerald-800 text-white px-6 py-5 border-b-4 border-orange-500">
            <h1 class="text-xl font-bold">Renew Your Membership</h1>
            <p class="text-emerald-100 text-sm mt-1">User ID: <span class="font-mono">{{ $user->user_id }}</span></p>
        </div>

        <div class="rounded-b-xl bg-white border border-t-0 border-stone-200 shadow-sm px-6 py-7">
            @if ($user->isMembershipExpired())
                <div class="mb-5 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    Your membership expired on <strong>{{ $user->membership_expires_at->format('d M Y') }}</strong>. Access to your
                    account is paused until you renew — all your data and history are safe and will be restored immediately.
                </div>
            @elseif ($user->membershipDueSoon())
                <div class="mb-5 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                    Your membership expires on <strong>{{ $user->membership_expires_at->format('d M Y') }}</strong>. Renew now to avoid
                    losing access.
                </div>
            @elseif ($user->membership_expires_at)
                <div class="mb-5 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                    Your membership is active until <strong>{{ $user->membership_expires_at->format('d M Y') }}</strong>. You can renew
                    early below if you wish.
                </div>
            @endif

            <form method="POST" action="{{ route('membership.renew.process') }}">
                @csrf
                <fieldset class="space-y-2 mb-6">
                    <legend class="block text-sm font-medium text-stone-700 mb-2">Choose a billing period</legend>
                    @foreach ($periods as $value => $label)
                        <label class="flex items-center justify-between rounded-lg border border-stone-300 px-4 py-3 cursor-pointer hover:border-emerald-500 [&:has(input:checked)]:border-emerald-600 [&:has(input:checked)]:bg-emerald-50">
                            <span class="flex items-center gap-3">
                                <input type="radio" name="billing_period" value="{{ $value }}" required @checked($loop->first) class="text-emerald-600 focus:ring-emerald-600">
                                <span class="text-sm font-medium text-stone-800">{{ $label }}</span>
                            </span>
                            <span class="text-sm font-semibold text-stone-900">₹{{ number_format($fees[$value] ?? 0) }}</span>
                        </label>
                    @endforeach
                </fieldset>

                @if ($testMode)
                    <p class="text-xs text-stone-500 mb-3 text-center">Payment gateway is in <strong>test mode</strong>. Click below to simulate a successful payment.</p>
                @else
                    <p class="text-xs text-stone-500 mb-3 text-center">You will be redirected to the secure Razorpay gateway.</p>
                @endif
                <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-lg shadow-sm">
                    Renew membership
                </button>
            </form>
        </div>
    </div>
@endsection
