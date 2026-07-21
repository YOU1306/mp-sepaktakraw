@props([
    'title',
    'number' => null,
    'description' => null,
])

<section class="mb-8 last:mb-0">
    <div class="flex items-center gap-3 border-b border-stone-200 pb-2 mb-5">
        @if ($number)
            <span class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-800 font-semibold text-sm flex items-center justify-center shrink-0">{{ $number }}</span>
        @endif
        <div>
            <h2 class="text-base font-semibold text-stone-900">{{ $title }}</h2>
            @if ($description)
                <p class="text-xs text-stone-500">{{ $description }}</p>
            @endif
        </div>
    </div>

    {{ $slot }}
</section>
