@extends('layouts.auth')

@section('title', 'Change Password')

@section('content')
    <h1 class="text-2xl font-bold text-stone-900 mb-2">Change your password</h1>
    <p class="text-sm text-stone-600 mb-6">For security, please set a new password before continuing.</p>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-800">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.change.update') }}" class="space-y-4">
        @csrf

        <div>
            <label for="current_password" class="block text-sm font-medium text-stone-700 mb-1">Old password</label>
            <input id="current_password" type="password" name="current_password" required autocomplete="current-password"
                class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-stone-700 mb-1">New password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-stone-700 mb-1">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
        </div>

        <button type="submit" class="w-full bg-emerald-800 hover:bg-emerald-900 text-white font-medium py-2.5 rounded-lg">
            Update password
        </button>
    </form>
@endsection
