<?php

namespace App\Http\Controllers;

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
        ]);
    }
}
