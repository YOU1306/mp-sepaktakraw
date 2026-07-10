@extends('layouts.public')

@section('title', 'Home — '.config('app.name'))

@section('content')
    <section class="mb-10 rounded-xl overflow-hidden bg-gradient-to-r from-emerald-800 to-emerald-600 text-white p-8 md:p-12">
        <h1 class="text-3xl md:text-4xl font-bold mb-3">Madhya Pradesh Sepaktakraw Federation</h1>
        <p class="text-emerald-100 max-w-2xl text-lg">
            Official portal for news, notices, results, and player registrations across all districts of Madhya Pradesh.
        </p>
    </section>

    @if($notices->isNotEmpty())
        <section class="mb-10 bg-amber-50 border border-amber-200 rounded-lg p-4">
            <h2 class="font-semibold text-amber-900 mb-2">Latest Notices</h2>
            <ul class="space-y-1 text-sm">
                @foreach($notices as $notice)
                    <li>
                        <a href="{{ route('content.show', ['type' => 'notices', 'content' => $notice->slug]) }}" class="text-amber-900 hover:underline">
                            {{ $notice->title }}
                        </a>
                        <span class="text-amber-700">— {{ $notice->published_at?->format('d M Y') }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="grid md:grid-cols-2 gap-8">
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-emerald-900">News</h2>
                <a href="{{ route('content.index.news') }}" class="text-sm text-emerald-700 hover:underline">View all</a>
            </div>
            <div class="space-y-4">
                @forelse($news as $item)
                    <article class="bg-white border border-stone-200 rounded-lg p-4 shadow-sm">
                        <h3 class="font-semibold">
                            <a href="{{ route('content.show', ['type' => 'news', 'content' => $item->slug]) }}" class="hover:text-emerald-700">
                                {{ $item->title }}
                            </a>
                        </h3>
                        <p class="text-xs text-stone-500 mt-1">{{ $item->published_at?->format('d M Y') }}</p>
                    </article>
                @empty
                    <p class="text-stone-500 text-sm">No news published yet.</p>
                @endforelse
            </div>
        </section>

        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-emerald-900">Events</h2>
                <a href="{{ route('content.index.events') }}" class="text-sm text-emerald-700 hover:underline">View all</a>
            </div>
            <div class="space-y-4">
                @forelse($events as $item)
                    <article class="bg-white border border-stone-200 rounded-lg p-4 shadow-sm">
                        <h3 class="font-semibold">
                            <a href="{{ route('content.show', ['type' => 'events', 'content' => $item->slug]) }}" class="hover:text-emerald-700">
                                {{ $item->title }}
                            </a>
                        </h3>
                        <p class="text-xs text-stone-500 mt-1">{{ $item->published_at?->format('d M Y') }}</p>
                    </article>
                @empty
                    <p class="text-stone-500 text-sm">No events published yet.</p>
                @endforelse
            </div>
        </section>
    </div>

    @if($openIntakes->isNotEmpty())
        <section class="mt-10">
            <h2 class="text-xl font-bold text-emerald-900 mb-4">Open Registrations</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($openIntakes as $intake)
                    <div class="bg-white border border-emerald-200 rounded-lg p-4">
                        <h3 class="font-semibold">{{ $intake->title }}</h3>
                        @if($intake->district)
                            <p class="text-sm text-stone-600 mt-1">{{ $intake->district->name }}</p>
                        @endif
                        <p class="text-sm font-medium text-emerald-800 mt-2">Fee: ₹{{ $intake->feeInRupees() }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection
