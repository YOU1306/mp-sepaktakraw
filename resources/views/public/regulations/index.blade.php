@extends('layouts.public')

@section('title', 'Rules & Regulations — '.config('app.name'))

@section('content')
    <section class="mb-8">
        <div class="flex items-center gap-3 mb-2">
            <span class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
            </span>
            <div>
                <h1 class="text-xl font-bold text-stone-900">Rules &amp; Regulations</h1>
                <x-tricolour-bar class="w-14 h-1.5 mt-1.5 mb-1" />
                <p class="text-xs text-stone-500">Official Laws of the Game and match documents, as prescribed by ISTAF / STFI</p>
            </div>
        </div>
    </section>

    @if($regulations->isEmpty())
        <p class="text-stone-500 text-sm">No rules or regulations have been published yet.</p>
    @else
        <div class="grid sm:grid-cols-2 gap-4">
            @foreach($regulations as $regulation)
                <a href="{{ route('regulations.show', $regulation) }}" target="_blank" rel="noopener"
                   class="group flex items-start gap-3 bg-white border border-stone-200 hover:border-emerald-400 hover:shadow-md rounded-lg p-4 transition">
                    <span class="shrink-0 w-10 h-10 rounded bg-red-50 text-red-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block font-semibold text-stone-900 group-hover:text-emerald-700">{{ $regulation->title }}</span>
                        @if($regulation->description)
                            <span class="block text-sm text-stone-500 mt-0.5">{{ $regulation->description }}</span>
                        @endif
                        <span class="inline-flex items-center gap-1 text-xs text-emerald-700 mt-1.5 font-medium">
                            View PDF
                            @if($regulation->sizeForHumans())
                                <span class="text-stone-400">&middot; {{ $regulation->sizeForHumans() }}</span>
                            @endif
                        </span>
                    </span>
                </a>
            @endforeach
        </div>
    @endif
@endsection
