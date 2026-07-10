<?php

use App\Http\Controllers\ContentController;
use App\Http\Controllers\HomeController;
use App\Models\Content;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/news', fn () => app(ContentController::class)->index('news'))->name('content.index.news');
Route::get('/notices', fn () => app(ContentController::class)->index('notices'))->name('content.index.notices');
Route::get('/results', fn () => app(ContentController::class)->index('results'))->name('content.index.results');
Route::get('/events', fn () => app(ContentController::class)->index('events'))->name('content.index.events');

Route::get('/{type}/{content:slug}', [ContentController::class, 'show'])
    ->whereIn('type', ['news', 'notices', 'results', 'events'])
    ->name('content.show');

Route::get('/page/{slug}', [ContentController::class, 'page'])->name('page.show');

Route::bind('content', fn (string $value) => Content::query()->where('slug', $value)->firstOrFail());
