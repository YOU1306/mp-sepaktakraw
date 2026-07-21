@extends('layouts.public')

@section('title', 'Club Registration')

@section('content')
    <x-reg.shell
        title="Club Registration"
        subtitle="Register your club with its office bearers and members. A registration fee is payable through a secure gateway before the application is finalised."
        :step="1" :steps="['Application', 'Payment', 'Under review']">

        @if ($fee > 0)
            <div class="mb-6 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                <strong>Registration fee: ₹{{ number_format($fee / 100, 2) }}</strong> — payable after you submit this form.
                The application is discarded if payment is not completed within {{ \App\Services\PaymentService::windowMinutes() }} minutes.
            </div>
        @endif

        <form method="POST" action="{{ route('register.club.store') }}" enctype="multipart/form-data" class="space-y-2">
            @csrf

            <x-reg.section number="1" title="Club Details">
                <div class="grid sm:grid-cols-2 gap-5">
                    <x-reg.input name="club_name" label="Club name" required />
                    <x-reg.input name="registration_number" label="Club registration number" required />
                    <x-reg.input name="place" label="Place" required />
                    <x-reg.select name="district_id" label="District"
                        :options="$districts->pluck('name', 'id')" required placeholder="Select district" />
                </div>
            </x-reg.section>

            @include('registration.partials.office-bearers-repeater', ['sectionNumber' => 2])

            @include('registration.partials.club-members-repeater', ['sectionNumber' => 3])

            <div class="pt-2 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between border-t border-stone-100">
                <p class="text-xs text-stone-500">You confirm all details are accurate and authorised by the club.</p>
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-8 py-2.5 rounded-lg shadow-sm">
                    {{ $fee > 0 ? 'Continue to payment' : 'Submit application' }}
                </button>
            </div>
        </form>
    </x-reg.shell>
@endsection
