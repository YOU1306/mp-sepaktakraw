<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-100 text-stone-900 font-sans antialiased min-h-screen flex flex-col">
    <header class="bg-emerald-900 text-white py-4">
        <div class="max-w-md mx-auto px-4">
            <a href="{{ route('home') }}" class="font-bold hover:text-emerald-200">← MP Sepaktakraw Federation</a>
        </div>
    </header>

    <main class="flex-1 flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-md bg-white rounded-xl shadow-sm border border-stone-200 p-8">
            @yield('content')
        </div>
    </main>
</body>
</html>
