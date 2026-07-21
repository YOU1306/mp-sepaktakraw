@extends('layouts.public')

@section('title', $content->title.' — '.config('app.name'))

@section('content')
    <article class="max-w-3xl">
        <p class="text-sm text-stone-500 mb-2">
            <a href="{{ route('content.index.'.$type) }}" class="hover:underline capitalize">{{ $type }}</a>
            · {{ $content->published_at?->format('d M Y') }}
        </p>
        <h1 class="text-3xl font-bold text-stone-900 mb-6">{{ $content->title }}</h1>
        <div class="prose prose-stone max-w-none">
            {!! $content->body !!}
        </div>
    </article>
@endsection
