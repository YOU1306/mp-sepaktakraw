@props([
    'name',
    'label',
    'required' => false,
    'accept' => '.jpg,.jpeg,.png,.pdf',
    'maxSizeMb' => 5,
    'hint' => null,
])

@php
    $inputId = 'f-'.preg_replace('/[^a-zA-Z0-9_-]+/', '-', $name);

    $badges = collect(explode(',', $accept))
        ->map(fn ($ext) => trim($ext, ". \t"))
        ->filter()
        ->map(fn ($ext) => strtoupper($ext) === 'JPEG' ? 'JPG' : strtoupper($ext))
        ->unique()
        ->values();
@endphp

<div>
    <label for="{{ $inputId }}" class="block text-sm font-medium text-stone-700 mb-1">
        {{ $label }}@if ($required)<span class="text-red-500"> *</span>@endif
    </label>

    <div class="reg-dropzone rounded-lg border-2 border-dashed border-stone-300 bg-stone-50 transition-colors">
        <label for="{{ $inputId }}" class="flex items-center gap-3 p-3 cursor-pointer select-none">
            <span class="shrink-0 w-10 h-10 rounded-full bg-white border border-stone-200 flex items-center justify-center text-emerald-700">
                <svg class="reg-dropzone__icon-upload w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                </svg>
                <svg class="reg-dropzone__icon-done w-5 h-5 hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </span>

            <span class="flex-1 min-w-0">
                <span class="reg-dropzone__filename block text-sm text-stone-500 truncate">No file selected</span>
                <span class="block text-xs text-stone-400">Click to browse or drag &amp; drop</span>
            </span>

            <button type="button" class="reg-dropzone__remove hidden shrink-0 text-xs font-medium text-red-600 hover:text-red-700 px-2 py-1 rounded hover:bg-red-50">
                Remove
            </button>
            <span class="reg-dropzone__browse shrink-0 text-xs font-semibold text-emerald-700 bg-white border border-emerald-200 px-2.5 py-1.5 rounded-md">
                Browse
            </span>
        </label>

        <div class="flex flex-wrap items-center gap-1.5 px-3 pb-2.5 -mt-1">
            @foreach ($badges as $badge)
                <span class="text-[10px] font-medium uppercase tracking-wide text-stone-500 bg-white border border-stone-200 px-1.5 py-0.5 rounded">{{ $badge }}</span>
            @endforeach
            <span class="text-[10px] font-medium uppercase tracking-wide text-stone-500 bg-white border border-stone-200 px-1.5 py-0.5 rounded">Max {{ $maxSizeMb }} MB</span>
        </div>

        <input
            id="{{ $inputId }}"
            name="{{ $name }}"
            type="file"
            accept="{{ $accept }}"
            @if ($required) required @endif
            {{ $attributes->merge(['class' => 'reg-dropzone__input sr-only']) }}
        >
    </div>

    @if ($hint)
        <p class="mt-1 text-xs text-stone-500">{{ $hint }}</p>
    @endif
</div>
