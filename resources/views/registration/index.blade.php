@extends('layouts.public')

@section('title', 'Registration')

@section('content')
    <div class="max-w-5xl mx-auto">
        <div class="rounded-xl bg-gradient-to-r from-emerald-900 to-emerald-800 text-white px-6 sm:px-8 py-7 border-b-4 border-orange-500 mb-8">
            <h1 class="text-2xl font-bold tracking-tight">Registration Portal</h1>
            <p class="text-emerald-100 text-sm mt-1 max-w-3xl">Choose the type of registration below. Every application is
                verified by the federation admin. Once approved, login credentials (User ID and password) are issued by email.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            {{-- Individual --}}
            <a href="{{ route('register.individual') }}" class="group block bg-white border border-stone-200 rounded-xl overflow-hidden hover:border-emerald-500 hover:shadow-md transition">
                <div class="h-1.5 bg-emerald-600"></div>
                <div class="p-6 flex flex-col h-full">
                    <div class="w-11 h-11 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl mb-4">&#9823;</div>
                    <h2 class="font-bold text-stone-900 text-lg">Individual Player</h2>
                    <p class="text-sm text-stone-600 mt-2 flex-1">For individual players (Sub-junior / Junior / Senior). Submit your
                        details and documents.</p>
                    <div class="mt-4 flex items-center justify-between">
                        <span class="text-xs font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">Free</span>
                        <span class="text-sm font-semibold text-orange-600 group-hover:translate-x-0.5 transition">Start &rarr;</span>
                    </div>
                </div>
            </a>

            {{-- Federation --}}
            <a href="{{ route('register.federation') }}" class="group block bg-white border border-stone-200 rounded-xl overflow-hidden hover:border-emerald-500 hover:shadow-md transition">
                <div class="h-1.5 bg-emerald-600"></div>
                <div class="p-6 flex flex-col h-full">
                    <div class="w-11 h-11 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl mb-4">&#127963;</div>
                    <h2 class="font-bold text-stone-900 text-lg">District Federation</h2>
                    <p class="text-sm text-stone-600 mt-2 flex-1">For district federations with 7–14 office bearers (Secretary
                        required). Registration fee applies.</p>
                    <div class="mt-4 flex items-center justify-between">
                        <span class="text-xs font-medium text-stone-600 bg-stone-100 px-2 py-0.5 rounded">Fee + payment</span>
                        <span class="text-sm font-semibold text-orange-600 group-hover:translate-x-0.5 transition">Start &rarr;</span>
                    </div>
                </div>
            </a>

            {{-- Club --}}
            <a href="{{ route('register.club') }}" class="group block bg-white border border-stone-200 rounded-xl overflow-hidden hover:border-emerald-500 hover:shadow-md transition">
                <div class="h-1.5 bg-emerald-600"></div>
                <div class="p-6 flex flex-col h-full">
                    <div class="w-11 h-11 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl mb-4">&#128101;</div>
                    <h2 class="font-bold text-stone-900 text-lg">Club</h2>
                    <p class="text-sm text-stone-600 mt-2 flex-1">For clubs with office bearers (7–14) and members (players &
                        officials). Registration fee applies.</p>
                    <div class="mt-4 flex items-center justify-between">
                        <span class="text-xs font-medium text-stone-600 bg-stone-100 px-2 py-0.5 rounded">Fee + payment</span>
                        <span class="text-sm font-semibold text-orange-600 group-hover:translate-x-0.5 transition">Start &rarr;</span>
                    </div>
                </div>
            </a>
        </div>

        <div class="mt-8 rounded-lg bg-stone-100 border border-stone-200 px-5 py-4 text-sm text-stone-600 flex items-center justify-between flex-wrap gap-3">
            <span>Already registered and have a User ID?</span>
            <a href="{{ route('login') }}" class="font-semibold text-emerald-800 hover:underline">Log in to your account &rarr;</a>
        </div>
    </div>
@endsection
