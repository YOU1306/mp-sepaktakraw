@extends('layouts.public')

@section('title', $title.' — '.config('app.name'))

@section('content')
    <h1 class="text-2xl font-bold text-stone-900 mb-6">{{ $title }}</h1>

    <div class="space-y-4">
        @forelse($items as $item)
            <article class="bg-white border border-stone-200 rounded-lg p-5 shadow-sm">
                <h2 class="text-lg font-semibold">
                    <a href="{{ route('content.show', ['type' => $type, 'content' => $item->slug]) }}" class="hover:text-emerald-700">
                        {{ $item->title }}
                    </a>
                </h2>
                <p class="text-sm text-stone-500 mt-1">{{ $item->published_at?->format('d M Y') }}</p>
            </article>
        @empty
            <p class="text-stone-500">Nothing published yet.</p>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $items->links() }}
    </div>
@endsection
