<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if ($user->isPrivileged()) {
            return redirect('/admin');
        }

        return view('dashboard', [
            'user' => $user,
            'notifications' => $user->notifications()->latest()->take(10)->get(),
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markNotificationRead(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->where('id', $notification)->first()?->markAsRead();

        return back();
    }
}
