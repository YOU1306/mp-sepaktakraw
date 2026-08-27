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

    {{-- Tricolour strip --}}
    <div class="h-1.5 w-full flex" aria-hidden="true">
        <span class="flex-1 bg-orange-500"></span>
        <span class="flex-1 bg-white"></span>
        <span class="flex-1 bg-green-700"></span>
    </div>

    {{-- Utility bar --}}
    <div class="bg-stone-800 text-stone-300 text-xs">
        <div class="max-w-6xl mx-auto px-4 py-1.5 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-3">
                <a href="#main-content" class="hover:text-white underline-offset-2 hover:underline">Skip to Main Content</a>
                <span class="text-stone-600">|</span>
                <span class="hidden sm:inline">Screen Reader Access</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="hidden md:inline">{{ now()->format('l, d F Y') }}</span>
                <span class="text-stone-600 hidden md:inline">|</span>
                <div class="flex items-center gap-1" role="group" aria-label="Adjust text size">
                    <button type="button" data-fontsize="dec" class="w-5 h-5 flex items-center justify-center rounded bg-stone-700 hover:bg-stone-600 text-[10px]" title="Decrease text size">A-</button>
                    <button type="button" data-fontsize="reset" class="w-5 h-5 flex items-center justify-center rounded bg-stone-700 hover:bg-stone-600 text-[11px]" title="Reset text size">A</button>
                    <button type="button" data-fontsize="inc" class="w-5 h-5 flex items-center justify-center rounded bg-stone-700 hover:bg-stone-600 text-[12px]" title="Increase text size">A+</button>
                </div>
                <span class="text-stone-600">|</span>
                <span class="font-medium text-stone-200">EN</span>
            </div>
        </div>
    </div>

    {{-- Header --}}
    <header class="bg-white border-b border-stone-200">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center gap-4">
            <div class="shrink-0 w-14 h-14 rounded-full ring-4 ring-stone-200 shadow-sm overflow-hidden bg-white">
                <img src="{{ asset('images/logo.jpg') }}" alt="Sepaktakraw Association Of Madhya Pradesh Logo" class="w-full h-full object-cover object-center">
            </div>
            <div class="min-w-0">
                <a href="{{ route('home') }}" class="block font-bold text-lg sm:text-xl tracking-tight text-stone-900 leading-tight">
                    Sepaktakraw Association Of Madhya Pradesh
                </a>
                <p class="text-[11px] sm:text-xs uppercase tracking-wide text-stone-500 mt-0.5">
                    Official Registration &amp; Information Portal
                </p>
            </div>
        </div>
    </header>

    {{-- Main navigation --}}
    <nav class="bg-gradient-to-r from-orange-200 via-white to-green-200 text-stone-800 shadow relative z-20 border-b border-stone-200">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm">
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                    <a href="{{ route('home') }}" class="hover:text-green-800 {{ request()->routeIs('home') ? 'font-semibold text-stone-900' : '' }}">Home</a>
                    <a href="{{ route('content.index.news') }}" class="hover:text-green-800 {{ request()->routeIs('content.index.news') ? 'font-semibold text-stone-900' : '' }}">News</a>
                    <a href="{{ route('content.index.notices') }}" class="hover:text-green-800 {{ request()->routeIs('content.index.notices') ? 'font-semibold text-stone-900' : '' }}">Notices</a>
                    <a href="{{ route('content.index.results') }}" class="hover:text-green-800 {{ request()->routeIs('content.index.results') ? 'font-semibold text-stone-900' : '' }}">Results</a>
                    <a href="{{ route('content.index.events') }}" class="hover:text-green-800 {{ request()->routeIs('content.index.events') ? 'font-semibold text-stone-900' : '' }}">Events</a>
                    <a href="{{ route('regulations.index') }}" class="hover:text-green-800 {{ request()->routeIs('regulations.*') ? 'font-semibold text-stone-900' : '' }}">Rules &amp; Regulations</a>

                    <div class="relative group">
                        <a href="{{ route('register') }}" class="flex items-center gap-1 hover:text-green-800 {{ request()->routeIs('register*') ? 'font-semibold text-stone-900' : '' }}">
                            Registration
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        <div class="absolute left-0 top-full hidden group-hover:block bg-white text-stone-800 rounded-b-md shadow-lg border border-stone-200 w-56 py-1.5">
                            <a href="{{ route('register.individual') }}" class="block px-4 py-2 text-sm hover:bg-emerald-50">Individual Registration</a>
                            <a href="{{ route('register.federation') }}" class="block px-4 py-2 text-sm hover:bg-emerald-50">District Federation</a>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="hover:text-green-800">Dashboard</a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="hover:text-green-800">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-green-800">Login</a>
                        <a href="{{ route('register') }}" class="bg-orange-600 hover:bg-orange-700 px-3 py-1 rounded text-white font-medium">Register</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main id="main-content" tabindex="-1" class="max-w-6xl mx-auto px-4 py-8 min-h-[70vh]">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-stone-900 text-stone-300 mt-12">
        <div class="max-w-6xl mx-auto px-4 py-10 grid sm:grid-cols-2 md:grid-cols-4 gap-8 text-sm">
            <div>
                <h3 class="text-white font-semibold mb-3">About</h3>
                <p class="text-stone-400 leading-relaxed">
                    Official portal of the Madhya Pradesh Sepaktakraw Federation for individual and district federation
                    registration, and for publishing news, notices, results and events across all districts.
                </p>
            </div>
            <div>
                <h3 class="text-white font-semibold mb-3">Quick Links</h3>
                <ul class="space-y-1.5">
                    <li><a href="{{ route('home') }}" class="hover:text-white hover:underline">Home</a></li>
                    <li><a href="{{ route('content.index.news') }}" class="hover:text-white hover:underline">News</a></li>
                    <li><a href="{{ route('content.index.notices') }}" class="hover:text-white hover:underline">Notices</a></li>
                    <li><a href="{{ route('content.index.results') }}" class="hover:text-white hover:underline">Results</a></li>
                    <li><a href="{{ route('content.index.events') }}" class="hover:text-white hover:underline">Events</a></li>
                    <li><a href="{{ route('regulations.index') }}" class="hover:text-white hover:underline">Rules &amp; Regulations</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-white font-semibold mb-3">Registration</h3>
                <ul class="space-y-1.5">
                    <li><a href="{{ route('register.individual') }}" class="hover:text-white hover:underline">Individual Registration</a></li>
                    <li><a href="{{ route('register.federation') }}" class="hover:text-white hover:underline">District Federation</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-white hover:underline">Login</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-white font-semibold mb-3">Contact Us</h3>
                <ul class="space-y-2">
                    <li>
                        <a href="https://www.facebook.com/share/1BbNwFKH2m/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 hover:text-white hover:underline">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
                            </svg>
                            Facebook
                        </a>
                    </li>
                    <li>
                        <a href="https://www.instagram.com/mp_sepaktakraw?utm_source=qr&igsh=djM1dHlwMzhmOHFt" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 hover:text-white hover:underline">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                <path d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"/>
                            </svg>
                            Instagram
                        </a>
                    </li>
                    <li>
                        <a href="https://youtube.com/@mpsepaktakrawassociation791?si=4kB8S-0_iNf1n2f4" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 hover:text-white hover:underline">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                            YouTube
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="border-t border-stone-800">
            <div class="max-w-6xl mx-auto px-4 py-4 flex flex-wrap items-center justify-between gap-2 text-xs text-stone-500">
                <p>&copy; {{ date('Y') }} Madhya Pradesh Sepaktakraw Federation. All rights reserved.</p>
                <p>Last updated: {{ now()->format('d M Y') }} &middot; Best viewed in the latest browsers</p>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            var root = document.documentElement;
            var steps = [87.5, 100, 112.5, 125];
            var idx = 1;
            function apply() { root.style.fontSize = steps[idx] + '%'; }
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-fontsize]');
                if (!btn) return;
                var action = btn.getAttribute('data-fontsize');
                if (action === 'inc' && idx < steps.length - 1) idx++;
                else if (action === 'dec' && idx > 0) idx--;
                else if (action === 'reset') idx = 1;
                apply();
            });
        })();
    </script>

    {{-- Document upload widget (used across registration forms) --}}
    <script>
        window.RegDropzone = (function () {
            function fmt(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB';
                return (bytes / 1024 / 1024).toFixed(1) + ' MB';
            }

            function elements(zone) {
                return {
                    name: zone.querySelector('.reg-dropzone__filename'),
                    remove: zone.querySelector('.reg-dropzone__remove'),
                    iconUp: zone.querySelector('.reg-dropzone__icon-upload'),
                    iconOk: zone.querySelector('.reg-dropzone__icon-done'),
                };
            }

            function reset(input) {
                var zone = input.closest('.reg-dropzone');
                if (!zone) return;
                var el = elements(zone);
                zone.classList.remove('border-emerald-400', 'bg-emerald-50/60');
                zone.classList.add('border-stone-300', 'bg-stone-50');
                if (el.name) { el.name.textContent = 'No file selected'; el.name.classList.remove('text-emerald-800', 'font-medium'); }
                if (el.remove) el.remove.classList.add('hidden');
                if (el.iconUp) el.iconUp.classList.remove('hidden');
                if (el.iconOk) el.iconOk.classList.add('hidden');
            }

            function update(input) {
                var zone = input.closest('.reg-dropzone');
                if (!zone) return;
                var file = input.files && input.files[0];
                if (!file) { reset(input); return; }
                var el = elements(zone);
                zone.classList.remove('border-stone-300', 'bg-stone-50');
                zone.classList.add('border-emerald-400', 'bg-emerald-50/60');
                if (el.name) { el.name.textContent = file.name + ' \u00b7 ' + fmt(file.size); el.name.classList.add('text-emerald-800', 'font-medium'); }
                if (el.remove) el.remove.classList.remove('hidden');
                if (el.iconUp) el.iconUp.classList.add('hidden');
                if (el.iconOk) el.iconOk.classList.remove('hidden');
            }

            document.addEventListener('change', function (e) {
                if (e.target.matches && e.target.matches('.reg-dropzone__input')) update(e.target);
            });

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.reg-dropzone__remove');
                if (!btn) return;
                e.preventDefault();
                e.stopPropagation();
                var zone = btn.closest('.reg-dropzone');
                var input = zone && zone.querySelector('.reg-dropzone__input');
                if (input) { input.value = ''; reset(input); }
            });

            ['dragover', 'dragenter'].forEach(function (evt) {
                document.addEventListener(evt, function (e) {
                    var zone = e.target.closest && e.target.closest('.reg-dropzone');
                    if (zone) { e.preventDefault(); zone.classList.add('ring-2', 'ring-emerald-400'); }
                }, true);
            });
            ['dragleave', 'drop'].forEach(function (evt) {
                document.addEventListener(evt, function (e) {
                    var zone = e.target.closest && e.target.closest('.reg-dropzone');
                    if (zone) zone.classList.remove('ring-2', 'ring-emerald-400');
                }, true);
            });
            document.addEventListener('drop', function (e) {
                var zone = e.target.closest && e.target.closest('.reg-dropzone');
                if (!zone) return;
                e.preventDefault();
                var input = zone.querySelector('.reg-dropzone__input');
                if (input && e.dataTransfer.files.length) {
                    input.files = e.dataTransfer.files;
                    update(input);
                }
            }, true);

            return { update: update, reset: reset };
        })();
    </script>
</body>
</html>
