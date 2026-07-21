@extends('layouts.public')

@section('title', 'Payment Session Expired')

@section('content')
    <div class="max-w-lg mx-auto text-center py-10">
        <div class="mx-auto w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mb-6">
            <span class="text-3xl text-red-600">&#10005;</span>
        </div>
        <h1 class="text-2xl font-bold text-stone-800 mb-2">Payment session expired</h1>
        <p class="text-stone-600 mb-6">
            The payment for application <span class="font-mono">{{ $application->reference_no }}</span> was not completed in time,
            so this application has expired and will not be processed. Please start a new registration.
        </p>
        <a href="{{ route('register') }}" class="inline-block bg-emerald-800 hover:bg-emerald-900 text-white font-semibold px-6 py-2.5 rounded-lg">
            Start a new registration
        </a>
    </div>
@endsection
