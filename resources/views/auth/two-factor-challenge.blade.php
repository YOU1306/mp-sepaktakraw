@extends('layouts.auth')

@section('title', 'Two-Factor Authentication')

@section('content')
    <h1 class="text-2xl font-bold text-emerald-900 mb-2">Two-factor code</h1>
    <p class="text-sm text-stone-600 mb-6">Enter the code from your authenticator app.</p>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-800">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium text-stone-700 mb-1">Authentication code</label>
            <input id="code" type="text" name="code" inputmode="numeric" autofocus autocomplete="one-time-code"
                class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
        </div>

        <button type="submit" class="w-full bg-emerald-800 hover:bg-emerald-900 text-white font-medium py-2.5 rounded-lg">
            Verify
        </button>
    </form>
@endsection
