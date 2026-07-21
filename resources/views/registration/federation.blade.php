@extends('layouts.public')

@section('title', 'District Federation Registration')

@section('content')
    <x-reg.shell
        title="District Federation Registration"
        subtitle="Register your district Sepaktakraw federation with its office bearers. A registration fee is payable through a secure gateway before the application is finalised."
        :step="1" :steps="['Application', 'Payment', 'Under review']">

        @if ($fee > 0)
            <div class="mb-6 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                <strong>Registration fee: ₹{{ number_format($fee / 100, 2) }}</strong> — payable after you submit this form.
                The application is discarded if payment is not completed within {{ \App\Services\PaymentService::windowMinutes() }} minutes.
            </div>
        @endif

        <form method="POST" action="{{ route('register.federation.store') }}" enctype="multipart/form-data" class="space-y-2">
            @csrf

            <x-reg.section number="1" title="Federation Details">
                <div class="grid sm:grid-cols-2 gap-5">
                    <x-reg.input name="registration_number" label="Federation registration number" required />
                    <x-reg.select name="district_id" label="District"
                        :options="$districts->pluck('name', 'id')" required placeholder="Select district" />
                    <div class="sm:col-span-2">
                        <x-reg.file name="acknowledgement" label="Acknowledgement copy" required
                            hint="Upload the federation acknowledgement / recognition document." />
                    </div>
                </div>
            </x-reg.section>

            @include('registration.partials.office-bearers-repeater', ['sectionNumber' => 2])

            <div class="pt-2 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between border-t border-stone-100">
                <p class="text-xs text-stone-500">You confirm all details are accurate and authorised by the federation.</p>
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-8 py-2.5 rounded-lg shadow-sm">
                    {{ $fee > 0 ? 'Continue to payment' : 'Submit application' }}
                </button>
            </div>
        </form>
    </x-reg.shell>
@endsection
