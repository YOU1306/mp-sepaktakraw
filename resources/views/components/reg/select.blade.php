@props([
    'name',
    'label',
    'options' => [],
    'required' => false,
    'value' => null,
    'placeholder' => 'Select',
    'hint' => null,
])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-stone-700 mb-1">
        {{ $label }}@if ($required)<span class="text-red-500"> *</span>@endif
    </label>
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @if ($required) required @endif
        {{ $attributes->merge(['class' => 'w-full rounded-lg border border-stone-300 px-3 py-2 text-sm bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none']) }}
    >
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $optValue => $optLabel)
            <option value="{{ $optValue }}" @selected((string) old($name, $value) === (string) $optValue)>{{ $optLabel }}</option>
        @endforeach
    </select>
    @if ($hint)
        <p class="mt-1 text-xs text-stone-500">{{ $hint }}</p>
    @endif
</div>
