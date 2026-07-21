@props([
    'name',
    'label',
    'required' => false,
    'value' => null,
    'rows' => 2,
    'hint' => null,
])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-stone-700 mb-1">
        {{ $label }}@if ($required)<span class="text-red-500"> *</span>@endif
    </label>
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($required) required @endif
        {{ $attributes->merge(['class' => 'w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none']) }}
    >{{ old($name, $value) }}</textarea>
    @if ($hint)
        <p class="mt-1 text-xs text-stone-500">{{ $hint }}</p>
    @endif
</div>
