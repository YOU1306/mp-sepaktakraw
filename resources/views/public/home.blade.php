@extends('layouts.public')

@section('title', 'Home — '.config('app.name'))

@section('content')
    <section class="relative mb-10 rounded-xl overflow-hidden bg-stone-100">
        <img src="{{ asset('images/homepage-association.jpg') }}"
             alt="Sepaktakraw Association of Madhya Pradesh"
             class="block w-full h-auto" fetchpriority="high">
        <div class="absolute inset-x-0 bottom-0 h-1.5 flex z-10" aria-hidden="true">
            <span class="flex-1 bg-orange-500"></span>
            <span class="flex-1 bg-white"></span>
            <span class="flex-1 bg-green-700"></span>
        </div>
    </section>

    <section class="mb-10">
        <div class="flex items-center gap-3 mb-5">
            <span class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </span>
            <div>
                <h2 class="text-xl font-bold text-stone-900">Our Leadership</h2>
                <x-tricolour-bar class="w-14 h-1.5 mt-1.5 mb-1" />
                <p class="text-xs text-stone-500">Office bearers of the state association and its national affiliation</p>
            </div>
        </div>

        <div class="flex flex-col gap-6 max-w-2xl mx-auto">
            <figure class="bg-white border border-stone-200 rounded-xl shadow-sm overflow-hidden">
                <div class="h-1.5 flex" aria-hidden="true">
                    <span class="flex-1 bg-orange-500"></span>
                    <span class="flex-1 bg-white"></span>
                    <span class="flex-1 bg-green-700"></span>
                </div>
                <img src="{{ asset('images/leadership/sepaktakraw-federation-of-india.png') }}"
                     alt="Office bearers of the Sepaktakraw Federation of India: President, General Secretary and Treasurer"
                     class="w-full h-auto" loading="lazy">
                <figcaption class="px-4 py-3 border-t border-stone-100 bg-stone-50 text-xs font-medium text-stone-600 uppercase tracking-wide">
                    Affiliated to Sepaktakraw Federation of India
                </figcaption>
            </figure>

            <figure class="bg-white border border-stone-200 rounded-xl shadow-sm overflow-hidden">
                <div class="h-1.5 flex" aria-hidden="true">
                    <span class="flex-1 bg-orange-500"></span>
                    <span class="flex-1 bg-white"></span>
                    <span class="flex-1 bg-green-700"></span>
                </div>
                <img src="{{ asset('images/leadership/1000283779.jpg') }}"
                     alt="Sepaktakraw Federation of India leadership team"
                     class="w-full h-auto" loading="lazy">
                <figcaption class="px-4 py-3 border-t border-stone-100 bg-stone-50 text-xs font-medium text-stone-600 uppercase tracking-wide">
                    Sepaktakraw Federation of Madhya Pradesh
                </figcaption>
            </figure>

            <figure class="bg-white border border-stone-200 rounded-xl shadow-sm overflow-hidden">
                <div class="h-1.5 flex" aria-hidden="true">
                    <span class="flex-1 bg-orange-500"></span>
                    <span class="flex-1 bg-white"></span>
                    <span class="flex-1 bg-green-700"></span>
                </div>
                <img src="{{ asset('images/leadership/gemini-generated.png') }}"
                     alt="Sepaktakraw leadership and team"
                     class="w-full h-auto" loading="lazy">
                <figcaption class="px-4 py-3 border-t border-stone-100 bg-stone-50 text-xs font-medium text-stone-600 uppercase tracking-wide">
                    Sepaktakraw Federation of Madhya Pradesh
                </figcaption>
            </figure>
        </div>
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
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-xl font-bold text-stone-900">News</h2>
                <a href="{{ route('content.index.news') }}" class="text-sm text-emerald-700 hover:underline">View all</a>
            </div>
            <x-tricolour-bar class="w-14 h-1.5 mb-4" />
            <div class="space-y-4">
                @forelse($news as $item)
                    <article class="bg-white border border-stone-200 border-l-4 border-l-green-700 rounded-lg p-4 shadow-sm">
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
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-xl font-bold text-stone-900">Events</h2>
                <a href="{{ route('content.index.events') }}" class="text-sm text-orange-700 hover:underline">View all</a>
            </div>
            <x-tricolour-bar class="w-14 h-1.5 mb-4" />
            <div class="space-y-4">
                @forelse($events as $item)
                    <article class="bg-white border border-stone-200 border-l-4 border-l-orange-500 rounded-lg p-4 shadow-sm">
                        <h3 class="font-semibold">
                            <a href="{{ route('content.show', ['type' => 'events', 'content' => $item->slug]) }}" class="hover:text-orange-700">
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
            <h2 class="text-xl font-bold text-stone-900">Open Registrations</h2>
            <x-tricolour-bar class="w-14 h-1.5 mt-1.5 mb-4" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($openIntakes as $intake)
                    <div class="bg-white border border-stone-200 rounded-lg overflow-hidden">
                        <div class="h-1.5 flex" aria-hidden="true">
                            <span class="flex-1 bg-orange-500"></span>
                            <span class="flex-1 bg-white"></span>
                            <span class="flex-1 bg-green-700"></span>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold">{{ $intake->title }}</h3>
                            @if($intake->district)
                                <p class="text-sm text-stone-600 mt-1">{{ $intake->district->name }}</p>
                            @endif
                            <p class="text-sm font-medium text-emerald-800 mt-2">Fee: ₹{{ $intake->feeInRupees() }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mt-10 rounded-xl overflow-hidden bg-stone-900">
        <div class="h-1.5 flex" aria-hidden="true">
            <span class="flex-1 bg-orange-500"></span>
            <span class="flex-1 bg-white"></span>
            <span class="flex-1 bg-green-700"></span>
        </div>
        <div class="px-6 py-6">
            <p class="text-center text-[11px] font-semibold uppercase tracking-wider text-stone-400 mb-5">
                National Initiatives &amp; Affiliations
            </p>
            <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-4">
                @foreach([
                    ['file' => 'g20.png', 'alt' => 'G20 India 2023'],
                    ['file' => 'minister-anurag-thakur.png', 'alt' => "Shri Anurag Singh Thakur, Hon'ble Union Minister of Youth Affairs and Sports"],
                    ['file' => 'azadi-ka-amrit-mahotsav.png', 'alt' => 'Azadi Ka Amrit Mahotsav'],
                    ['file' => 'fit-india.png', 'alt' => 'Fit India Movement'],
                    ['file' => 'astaf.png', 'alt' => 'Asian Sepaktakraw Federation (ASTAF)'],
                    ['file' => 'istaf.png', 'alt' => 'International Sepaktakraw Federation (ISTAF)'],
                    ['file' => 'swachh-bharat.png', 'alt' => 'Swachh Bharat Abhiyan'],
                    ['file' => 'khelo-india.png', 'alt' => 'Khelo India'],
                    ['file' => 'sai.png', 'alt' => 'Sports Authority of India (SAI)'],
                ] as $partner)
                    <img src="{{ asset('images/partners/'.$partner['file']) }}" alt="{{ $partner['alt'] }}"
                         class="h-16 w-auto object-contain rounded" loading="lazy">
                @endforeach
            </div>
        </div>
    </section>
@endsection
