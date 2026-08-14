@extends('layouts.public')

@section('title', 'My Dashboard')

@section('content')
    @if (session('status'))
        <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 p-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($user->isMembershipExpired())
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-800 flex items-center justify-between flex-wrap gap-3">
            <span>Your membership expired on <strong>{{ $user->membership_expires_at->format('d M Y') }}</strong>. Renew to restore full access.</span>
            <a href="{{ route('membership.renew') }}" class="font-semibold text-red-900 hover:underline whitespace-nowrap">Renew now &rarr;</a>
        </div>
    @elseif ($user->membershipDueSoon())
        <div class="mb-6 rounded-lg bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800 flex items-center justify-between flex-wrap gap-3">
            <span>Your membership expires on <strong>{{ $user->membership_expires_at->format('d M Y') }}</strong>.</span>
            <a href="{{ route('membership.renew') }}" class="font-semibold text-amber-900 hover:underline whitespace-nowrap">Renew now &rarr;</a>
        </div>
    @endif

    <div class="flex items-start justify-between gap-4 mb-2">
        <div>
            <h1 class="text-2xl font-bold text-stone-900 mb-2">Welcome, {{ $user->name }}</h1>
            <p class="text-stone-600 mb-6">Your User ID: <span class="font-mono font-semibold">{{ $user->user_id }}</span></p>
        </div>

        <div class="relative" id="notif-wrap">
            <button type="button" id="notif-bell" class="relative inline-flex items-center justify-center w-10 h-10 rounded-full bg-white border border-stone-200 hover:border-emerald-400">
                <span aria-hidden="true">&#128276;</span>
                @if ($unreadCount > 0)
                    <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-bold rounded-full w-5 h-5 flex items-center justify-center">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                @endif
            </button>
            <div id="notif-panel" class="hidden absolute right-0 mt-2 w-80 bg-white border border-stone-200 rounded-lg shadow-lg z-10 max-h-96 overflow-y-auto">
                @forelse ($notifications as $n)
                    <form method="POST" action="{{ route('notifications.read', $n->id) }}" class="border-b border-stone-100 last:border-b-0">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-3 text-sm hover:bg-stone-50 {{ $n->read_at ? 'opacity-60' : '' }}">
                            <p class="font-medium text-stone-900">{{ $n->data['title'] ?? 'Notification' }}</p>
                            <p class="text-stone-600 mt-0.5">{{ $n->data['message'] ?? '' }}</p>
                            <p class="text-stone-400 text-xs mt-1">{{ $n->created_at->diffForHumans() }}</p>
                        </button>
                    </form>
                @empty
                    <p class="px-4 py-6 text-sm text-stone-500 text-center">No notifications yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <a href="{{ route('home') }}" class="block bg-white border border-stone-200 rounded-lg p-5 hover:border-emerald-400">
            <h2 class="font-semibold text-stone-900">Browse the portal</h2>
            <p class="text-sm text-stone-600 mt-1">News, notices, results and events.</p>
        </a>
        <a href="{{ route('content.index.events') }}" class="block bg-white border border-stone-200 rounded-lg p-5 hover:border-emerald-400">
            <h2 class="font-semibold text-stone-900">Open registrations</h2>
            <p class="text-sm text-stone-600 mt-1">Register for active intake openings.</p>
        </a>
        @if ($user->membership_expires_at)
            <a href="{{ route('membership.renew') }}" class="block bg-white border border-stone-200 rounded-lg p-5 hover:border-emerald-400">
                <h2 class="font-semibold text-stone-900">Membership</h2>
                <p class="text-sm text-stone-600 mt-1">Valid until {{ $user->membership_expires_at->format('d M Y') }}. Renew any time.</p>
            </a>
        @endif
    </div>

    <script>
        (function () {
            const bell = document.getElementById('notif-bell');
            const panel = document.getElementById('notif-panel');
            bell.addEventListener('click', () => panel.classList.toggle('hidden'));
            document.addEventListener('click', (e) => {
                if (!document.getElementById('notif-wrap').contains(e.target)) panel.classList.add('hidden');
            });
        })();
    </script>
@endsection
