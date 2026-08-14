<?php

use App\Http\Controllers\ContentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FederationRegistrationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IndividualRegistrationController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\VerificationController;
use App\Models\Content;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/news', fn () => app(ContentController::class)->index('news'))->name('content.index.news');
Route::get('/notices', fn () => app(ContentController::class)->index('notices'))->name('content.index.notices');
Route::get('/results', fn () => app(ContentController::class)->index('results'))->name('content.index.results');
Route::get('/events', fn () => app(ContentController::class)->index('events'))->name('content.index.events');

// Registration landing (individual / federation flows)
Route::get('/register', [RegistrationController::class, 'index'])->name('register');

Route::prefix('register')->name('register.')->group(function () {
    Route::get('/individual', [IndividualRegistrationController::class, 'create'])->name('individual');
    Route::post('/individual', [IndividualRegistrationController::class, 'store'])->name('individual.store');
    Route::get('/individual/success', [IndividualRegistrationController::class, 'success'])->name('individual.success');

    Route::get('/federation', [FederationRegistrationController::class, 'create'])->name('federation');
    Route::post('/federation', [FederationRegistrationController::class, 'store'])->name('federation.store');
    Route::get('/federation/success', [FederationRegistrationController::class, 'success'])->name('federation.success');

    Route::post('/otp/send', [VerificationController::class, 'send'])->name('otp.send');
    Route::post('/otp/verify', [VerificationController::class, 'verify'])->name('otp.verify');

    Route::get('/payment/{reference}', [PaymentController::class, 'show'])->name('payment');
    Route::post('/payment/{reference}', [PaymentController::class, 'process'])->name('payment.process');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/password/change', [PasswordChangeController::class, 'edit'])->name('password.change');
    Route::post('/password/change', [PasswordChangeController::class, 'update'])->name('password.change.update');

    Route::get('/membership/renew', [MembershipController::class, 'show'])->name('membership.renew');
    Route::post('/membership/renew', [MembershipController::class, 'process'])->name('membership.renew.process');

    Route::post('/notifications/{notification}/read', [DashboardController::class, 'markNotificationRead'])->name('notifications.read');

    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
});

Route::get('/{type}/{content:slug}', [ContentController::class, 'show'])
    ->whereIn('type', ['news', 'notices', 'results', 'events'])
    ->name('content.show');

Route::get('/page/{slug}', [ContentController::class, 'page'])->name('page.show');

Route::bind('content', fn (string $value) => Content::query()->where('slug', $value)->firstOrFail());
