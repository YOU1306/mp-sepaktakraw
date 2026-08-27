<?php

use App\Http\Middleware\EnsureMembershipActive;
use App\Http\Middleware\EnsurePasswordChanged;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            EnsurePasswordChanged::class,
            EnsureMembershipActive::class,
        ]);

        // Razorpay signs the raw webhook body, so the gateway cannot send a CSRF
        // token. Exempt the webhook route; we verify its HMAC signature instead.
        $middleware->validateCsrfTokens(except: [
            'webhooks/payment',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
