@props([
    'name',
    'label',
    'type' => 'text',
    'required' => false,
    'value' => null,
    'hint' => null,
])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-stone-700 mb-1">
        {{ $label }}@if ($required)<span class="text-red-500"> *</span>@endif
    </label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        @if ($required) required @endif
        {{ $attributes->merge(['class' => 'w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none']) }}
    >
    @if ($hint)
        <p class="mt-1 text-xs text-stone-500">{{ $hint }}</p>
    @endif
</div>
