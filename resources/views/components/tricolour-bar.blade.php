@props(['class' => 'w-14 h-1.5'])

<div {{ $attributes->merge(['class' => $class.' rounded-full overflow-hidden flex ring-1 ring-stone-200']) }} aria-hidden="true">
    <span class="flex-1 bg-orange-500"></span>
    <span class="flex-1 bg-white"></span>
    <span class="flex-1 bg-green-700"></span>
</div>
