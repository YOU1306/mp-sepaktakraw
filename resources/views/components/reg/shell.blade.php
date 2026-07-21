@props([
    'title',
    'subtitle' => null,
    'step' => null,
    'steps' => null,
])

<div class="max-w-4xl mx-auto">
    <nav class="text-xs text-stone-500 mb-4" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-emerald-700">Home</a>
        <span class="mx-1">/</span>
        <a href="{{ route('register') }}" class="hover:text-emerald-700">Registration</a>
        <span class="mx-1">/</span>
        <span class="text-stone-700">{{ $title }}</span>
    </nav>

    <div class="rounded-t-xl bg-gradient-to-r from-emerald-900 to-emerald-800 text-white px-6 sm:px-8 py-6 border-b-4 border-orange-500">
        <div class="flex items-start gap-4">
            <div class="hidden sm:flex w-12 h-12 rounded-lg bg-white/10 items-center justify-center shrink-0">
                <span class="text-2xl">&#9873;</span>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="text-emerald-100 text-sm mt-1 max-w-2xl">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        @if ($step && $steps)
            <div class="mt-4 flex flex-wrap gap-x-6 gap-y-1 text-xs text-emerald-100/90">
                @foreach ($steps as $i => $label)
                    <span class="flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[11px] font-semibold
                            {{ $i + 1 <= $step ? 'bg-orange-500 text-white' : 'bg-white/15 text-white/80' }}">{{ $i + 1 }}</span>
                        {{ $label }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="rounded-b-xl bg-white border border-t-0 border-stone-200 shadow-sm px-6 sm:px-8 py-7">
        @if ($errors->any())
            <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-800">
                <p class="font-semibold mb-1">Please correct the following {{ $errors->count() }} error(s):</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{ $slot }}
    </div>

    <p class="text-center text-xs text-stone-400 mt-4">
        Fields marked <span class="text-red-500">*</span> are mandatory. Do not refresh or close the page while submitting.
    </p>
</div>
