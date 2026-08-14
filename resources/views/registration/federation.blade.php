@extends('layouts.public')

@section('title', 'District Federation Registration')

@section('content')
    <x-reg.shell
        title="District Federation Registration"
        subtitle="Register your district Sepaktakraw federation with its office bearers. A registration fee is payable through a secure gateway before the application is finalised."
        :step="1" :steps="['Application', 'Payment', 'Under review']">

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

            <x-reg.section number="2" title="Registration Fee" description="Choose a billing period. Payment is completed on the next step.">
                <fieldset class="space-y-2">
                    <legend class="sr-only">Billing period</legend>
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
                <p class="text-xs text-stone-500 mt-3">The application is discarded if payment is not completed within {{ \App\Services\PaymentService::windowMinutes() }} minutes of submitting.</p>
            </x-reg.section>

            @include('registration.partials.office-bearers-repeater', ['sectionNumber' => 3])

            <div class="pt-2 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between border-t border-stone-100">
                <p class="text-xs text-stone-500">You confirm all details are accurate and authorised by the federation.</p>
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-8 py-2.5 rounded-lg shadow-sm">
                    Continue to payment
                </button>
            </div>
        </form>
    </x-reg.shell>
@endsection
