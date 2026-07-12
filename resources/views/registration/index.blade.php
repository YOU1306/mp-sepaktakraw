@extends('layouts.public')

@section('title', 'Register')

@section('content')
    <h1 class="text-2xl font-bold text-emerald-900 mb-2">Start a registration</h1>
    <p class="text-stone-600 mb-8">Choose the type of registration. All applications are reviewed by the federation admin before approval.</p>

    <div class="grid md:grid-cols-3 gap-5">
        <div class="block bg-stone-50 border border-stone-200 rounded-lg p-6 opacity-70">
            <h2 class="font-semibold text-stone-700 text-lg">Individual Player</h2>
            <p class="text-sm text-stone-500 mt-2">Register as a player (Sub-junior / Junior / Senior) with your documents. <span class="italic">Coming next.</span></p>
        </div>

        <div class="block bg-stone-50 border border-stone-200 rounded-lg p-6 opacity-70">
            <h2 class="font-semibold text-stone-700 text-lg">District Federation</h2>
            <p class="text-sm text-stone-500 mt-2">Register your district federation with office bearers. <span class="italic">Coming next.</span></p>
        </div>

        <div class="block bg-stone-50 border border-stone-200 rounded-lg p-6 opacity-70">
            <h2 class="font-semibold text-stone-700 text-lg">Club</h2>
            <p class="text-sm text-stone-500 mt-2">Register your club with office bearers and members. <span class="italic">Coming next.</span></p>
        </div>
    </div>

    <p class="mt-8 text-sm text-stone-600">
        Already registered? <a href="{{ route('login') }}" class="text-emerald-800 font-medium hover:underline">Log in with your User ID</a>
    </p>
@endsection
