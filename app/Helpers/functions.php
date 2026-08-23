<?php

if (! function_exists('formatRp')) {
    function formatRp($angka)
    {
        return 'Rp ' . number_format((float) ($angka ?? 0), 0, ',', '.');
    }
}

if (! function_exists('t')) {
    /**
     * Terjemahkan key multi bahasa.
     *   t('menu.dashboard', 'Dashboard')
     *   t('subscription.days_left', '{n} hari tersisa', ['n' => 30])
     */
    function t(string $key, ?string $fallback = null, array $params = []): string
    {
        return app(\App\Services\LocalizationService::class)->trans($key, $fallback, $params);
    }
}

if (! function_exists('active_language')) {
    function active_language(): \App\Models\Language
    {
        return app(\App\Services\LocalizationService::class)->activeModel();
    }
}
