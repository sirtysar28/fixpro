<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\LocalizationService;
use Illuminate\Support\Facades\Session;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LocalizationService::class);
    }

    public function boot(): void
    {
        // Set locale awal dari session (default: id / Indonesia)
        // Dijalankan setiap request untuk Blade helper t()
        try {
            $code = (string) (Session::get('app_locale') ?? 'id');
            if ($code !== '') {
                app()->setLocale($code);
            }
        } catch (\Throwable $e) {
            // session mungkin belum tersedia (console) — abaikan
        }
    }
}
