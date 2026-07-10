<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(string $type): View
    {
        $types = [
            'news' => Content::TYPE_NEWS,
            'notices' => Content::TYPE_NOTICE,
            'results' => Content::TYPE_RESULT,
            'events' => Content::TYPE_EVENT,
        ];

        abort_unless(isset($types[$type]), 404);

        $items = Content::query()
            ->published()
            ->ofType($types[$type])
            ->latest('published_at')
            ->paginate(12);

        return view('public.content.index', [
            'type' => $type,
            'title' => ucfirst($type),
            'items' => $items,
        ]);
    }

    public function show(string $type, Content $content): View
    {
        $types = [
            'news' => Content::TYPE_NEWS,
            'notices' => Content::TYPE_NOTICE,
            'results' => Content::TYPE_RESULT,
            'events' => Content::TYPE_EVENT,
        ];

        abort_unless(isset($types[$type]) && $content->type === $types[$type], 404);
        abort_unless($content->status === Content::STATUS_PUBLISHED, 404);

        return view('public.content.show', [
            'type' => $type,
            'content' => $content,
        ]);
    }

    public function page(string $slug): View
    {
        $content = Content::query()
            ->published()
            ->ofType(Content::TYPE_PAGE)
            ->where('slug', $slug)
            ->firstOrFail();

        return view('public.content.page', compact('content'));
    }
}
