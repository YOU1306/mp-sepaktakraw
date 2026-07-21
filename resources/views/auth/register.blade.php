@extends('layouts.auth')

@section('title', 'Register')

@section('content')
    <h1 class="text-2xl font-bold text-stone-900 mb-6">Create account</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-800">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-stone-700 mb-1">Full name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-stone-700 mb-1">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-stone-700 mb-1">Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-stone-700 mb-1">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
        </div>

        <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-medium py-2.5 rounded-lg">
            Register
        </button>
    </form>

    <p class="mt-6 text-sm text-stone-600 text-center">
        Already have an account?
        <a href="{{ route('login') }}" class="text-emerald-800 font-medium hover:underline">Log in</a>
    </p>
@endsection
