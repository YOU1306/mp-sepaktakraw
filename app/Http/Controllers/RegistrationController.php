<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function index(): View
    {
        return view('registration.index');
    }
}
