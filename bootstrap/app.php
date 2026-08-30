<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // statefulApi() causes issues with pure Bearer token auth from mobile apps.
        // Web login uses 'auth' (session) guard, not 'auth:sanctum'.
        // API uses 'auth:sanctum' which only needs Bearer token — no cookies needed.
        // So statefulApi() is NOT required here.
        $middleware->alias([
            'active' => \App\Http\Middleware\EnsureIsActive::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'superadmin' => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);

        // ===== Fitur #8 & #9 — Webhook publik dari gateway/Fonnte TANPA CSRF =====
        // Signature diverifikasi manual di controller (token / HMAC).
        $middleware->validateCsrfTokens(except: [
            'payment/webhook',
            'whatsapp/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
