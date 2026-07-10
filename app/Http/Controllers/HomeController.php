<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Content;
use App\Models\IntakeOpening;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('public.home', [
            'banners' => Banner::query()->active()->get(),
            'news' => Content::query()->published()->ofType(Content::TYPE_NEWS)->latest('published_at')->limit(6)->get(),
            'notices' => Content::query()->published()->ofType(Content::TYPE_NOTICE)->latest('published_at')->limit(5)->get(),
            'events' => Content::query()->published()->ofType(Content::TYPE_EVENT)->latest('published_at')->limit(4)->get(),
            'openIntakes' => IntakeOpening::query()->open()->latest()->limit(3)->get(),
        ]);
    }
}
