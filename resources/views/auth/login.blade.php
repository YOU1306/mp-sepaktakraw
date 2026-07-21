@extends('layouts.auth')

@section('title', 'Login')

@section('content')
    <h1 class="text-2xl font-bold text-stone-900 mb-6">Login</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-800">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 p-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="user_id" class="block text-sm font-medium text-stone-700 mb-1">User ID</label>
            <input id="user_id" type="text" name="user_id" value="{{ old('user_id') }}" required autofocus autocomplete="username"
                class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                placeholder="e.g. PLR000123">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-stone-700 mb-1">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
        </div>

        <div class="flex items-center">
            <input id="remember_me" type="checkbox" name="remember" class="rounded border-stone-300 text-emerald-700">
            <label for="remember_me" class="ml-2 text-sm text-stone-600">Remember me</label>
        </div>

        <button type="submit" class="w-full bg-emerald-800 hover:bg-emerald-900 text-white font-medium py-2.5 rounded-lg">
            Log in
        </button>
    </form>

    <p class="mt-6 text-sm text-stone-600 text-center">
        Need to register?
        <a href="{{ route('register') }}" class="text-emerald-800 font-medium hover:underline">Start a new registration</a>
    </p>
@endsection
