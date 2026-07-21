@extends('layouts.public')

@section('title', 'Registration Submitted')

@section('content')
    <div class="max-w-xl mx-auto text-center py-10">
        <div class="mx-auto w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mb-6">
            <span class="text-3xl text-emerald-700">&#10003;</span>
        </div>
        <h1 class="text-2xl font-bold text-stone-900 mb-2">Registration submitted</h1>
        <p class="text-stone-600 mb-6">
            Your {{ $type }} application has been received and is now <strong>under review</strong> by the federation admin.
        </p>

        @if ($reference)
            <div class="inline-block bg-white border border-stone-200 rounded-lg px-6 py-4 mb-6">
                <p class="text-sm text-stone-500">Your reference number</p>
                <p class="text-lg font-mono font-semibold text-emerald-900">{{ $reference }}</p>
            </div>
        @endif

        <p class="text-sm text-stone-600">
            Once approved, your login credentials (User ID + password) will be emailed to you.
            If not approved, you'll receive an email inviting you to submit a new request.
        </p>

        <a href="{{ route('home') }}" class="inline-block mt-8 text-emerald-800 font-medium hover:underline">Back to home</a>
    </div>
@endsection
