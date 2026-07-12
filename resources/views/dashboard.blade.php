@extends('layouts.public')

@section('title', 'My Dashboard')

@section('content')
    @if (session('status'))
        <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 p-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <h1 class="text-2xl font-bold text-emerald-900 mb-2">Welcome, {{ $user->name }}</h1>
    <p class="text-stone-600 mb-8">Your User ID: <span class="font-mono font-semibold">{{ $user->user_id }}</span></p>

    <div class="grid sm:grid-cols-2 gap-4">
        <a href="{{ route('home') }}" class="block bg-white border border-stone-200 rounded-lg p-5 hover:border-emerald-400">
            <h2 class="font-semibold text-emerald-900">Browse the portal</h2>
            <p class="text-sm text-stone-600 mt-1">News, notices, results and events.</p>
        </a>
        <a href="{{ route('content.index.events') }}" class="block bg-white border border-stone-200 rounded-lg p-5 hover:border-emerald-400">
            <h2 class="font-semibold text-emerald-900">Open registrations</h2>
            <p class="text-sm text-stone-600 mt-1">Register for active intake openings.</p>
        </a>
    </div>
@endsection
