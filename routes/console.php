<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ===== Fitur #11 — Notifikasi Masa Aktif Aktivasi (Hitung Mundur) =====
// Kirim pengingat WhatsApp harian jam 09:00 WIB untuk lisensi yang akan berakhir.
Schedule::command('aktivasi:reminder')->dailyAt('09:00')->timezone('Asia/Jakarta')->withoutOverlapping();
