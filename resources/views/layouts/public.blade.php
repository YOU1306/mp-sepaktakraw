<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-50 text-stone-900 font-sans antialiased">
    <header class="bg-emerald-900 text-white shadow">
        <div class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('home') }}" class="font-bold text-lg tracking-tight">
                MP Sepaktakraw Federation
            </a>
            <nav class="flex flex-wrap gap-x-4 gap-y-2 text-sm">
                <a href="{{ route('home') }}" class="hover:text-emerald-200">Home</a>
                <a href="{{ route('content.index.news') }}" class="hover:text-emerald-200">News</a>
                <a href="{{ route('content.index.notices') }}" class="hover:text-emerald-200">Notices</a>
                <a href="{{ route('content.index.results') }}" class="hover:text-emerald-200">Results</a>
                <a href="{{ route('content.index.events') }}" class="hover:text-emerald-200">Events</a>
                @auth
                    @if(auth()->user()->hasAnyRole(['super-admin', 'admin', 'executive']))
                        <a href="{{ url('/admin') }}" class="hover:text-emerald-200">Admin</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="hover:text-emerald-200">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:text-emerald-200">Login</a>
                    <a href="{{ route('register') }}" class="bg-orange-500 hover:bg-orange-600 px-3 py-1 rounded text-white font-medium">Register</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8 min-h-[70vh]">
        @yield('content')
    </main>

    <footer class="bg-stone-200 border-t border-stone-300 mt-12">
        <div class="max-w-6xl mx-auto px-4 py-6 text-sm text-stone-600">
            <p>&copy; {{ date('Y') }} Madhya Pradesh Sepaktakraw Federation. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
