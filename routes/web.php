<?php

use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\WebsiteManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServisController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\TeknisiController;
use App\Http\Controllers\StokController;
use App\Http\Controllers\KasController;
use App\Http\Controllers\JualBeliController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LaporanKeuanganController;
use App\Http\Controllers\RegisterServiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\BannerIklanController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\PenjualanSparepartController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\AktivitasSparepartController;
use App\Http\Controllers\TagihanSparepartController;
use App\Http\Controllers\SerialNumberController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ArsipServisController;
use App\Http\Controllers\TipeHpController;
use App\Http\Controllers\ActivationRequestController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\ThermalPrintController;
use App\Http\Controllers\TeknisiDashboardController;
use App\Http\Controllers\ServicePriceController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\GrosirDashboardController;
use App\Http\Controllers\HargaGrosirController;
use App\Http\Controllers\PelangganGrosirController;
use App\Http\Controllers\PenjualanGrosirController;
use App\Http\Controllers\PesananGrosirController;
use App\Http\Controllers\ReturGrosirController;
use App\Http\Controllers\PiutangGrosirController;
use App\Http\Controllers\LaporanGrosirController;
use App\Http\Controllers\StokGrosirController;
use Illuminate\Support\Facades\Route;

// Auth routes (login, register, logout, dll)
require __DIR__.'/auth.php';

// ===== PUBLIC WEBSITE =====
Route::get('/', [WebsiteController::class, 'index'])->name('website.home');
Route::post('/lacak-servis', [WebsiteController::class, 'lacakServis'])->name('website.lacak');
Route::get('/download-apk', [WebsiteController::class, 'downloadApk'])->name('website.download-apk');

// ===== PUBLIC WEBHOOKS (Payment Gateway + WhatsApp) — Fitur #8 & #9 =====
// Harus publik (tanpa auth / csrf) karena dipanggil dari server gateway.
Route::post('/payment/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');
Route::post('/whatsapp/webhook', [WhatsAppController::class, 'webhook'])->name('whatsapp.webhook');
// polling & status publik by kode (token-protected via kode acak)
Route::get('/payment/status/{kode}', [PaymentController::class, 'status'])->name('payment.status-public');

// ===== ADMIN WEBSITE MANAGEMENT (Super Admin only) =====
Route::middleware(['auth', 'active', 'role:Super Admin,Admin'])->prefix('admin/website')->group(function () {
    Route::get('/', [WebsiteManagementController::class, 'index'])->name('admin.website.index');
    Route::post('/update-section', [WebsiteManagementController::class, 'updateSection'])->name('admin.website.update-section');
    Route::post('/update-item', [WebsiteManagementController::class, 'updateItem'])->name('admin.website.update-item');
    Route::post('/update-json-items', [WebsiteManagementController::class, 'updateJsonItems'])->name('admin.website.update-json-items');
    Route::delete('/delete-item/{id}', [WebsiteManagementController::class, 'deleteItem'])->name('admin.website.delete-item');
    Route::post('/upload-apk', [WebsiteManagementController::class, 'uploadApk'])->name('admin.website.upload-apk');
    Route::delete('/delete-apk', [WebsiteManagementController::class, 'deleteApk'])->name('admin.website.delete-apk');
});

// ===== MULTI BAHASA — switch (semua user) =====
Route::get('/language/switch/{code}', [LanguageController::class, 'switch'])->name('language.switch');

// Authenticated routes
Route::middleware(['auth', 'active'])->group(function () {

    // ===== PAKET BERLANGGANAN =====
    Route::get('/langganan', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::middleware('role:Super Admin,Admin')->group(function () {
        Route::post('/langganan/activate', [SubscriptionController::class, 'activate'])->name('subscription.activate');
    });
    Route::post('/langganan/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');

    // Dashboard - all roles
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Chat routes (all authenticated users)
    Route::get('/chat/messages', [ChatController::class, 'getMessages'])->name('chat.messages');
    Route::post('/chat/send', [ChatController::class, 'sendMessage'])->name('chat.send');
    Route::get('/chat/admin/rooms', [ChatController::class, 'adminRooms'])->name('chat.admin.rooms');
    Route::get('/chat/admin/messages/{roomId}', [ChatController::class, 'adminGetMessages'])->name('chat.admin.messages');
    Route::post('/chat/admin/send', [ChatController::class, 'adminSendMessage'])->name('chat.admin.send');
    Route::get('/chat/admin/unread-count', [ChatController::class, 'adminUnreadCount'])->name('chat.admin.unread');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/redeem-serial', [ProfileController::class, 'redeemSerial'])->name('profile.redeem-serial');

    // API
    Route::get('/api/pelanggan/search', [PelangganController::class, 'search'])->name('pelanggan.search');
    Route::post('/api/set-cabang', [CabangController::class, 'setCabang'])->name('cabang.set');

    // API Tipe HP (dynamic dropdown)
    Route::get('/api/tipe-hp/by-merk', [TipeHpController::class, 'getByMerk'])->name('api.tipe-hp.by-merk');
    Route::get('/api/tipe-hp/search', [TipeHpController::class, 'search'])->name('api.tipe-hp.search');

    // API User paket (for edit modal)
    Route::get('/api/user/{user}/paket', function (App\Models\User $user) {
        return response()->json(['paket' => $user->paket ?? 'standar']);
    });

    // API Stok Alerts (paginated, untuk dashboard)
    Route::get('/api/stok-alerts', [DashboardController::class, 'stokAlerts'])->name('api.stok-alerts');

    // Chat Bot Test (Admin)
    Route::post('/api/test-bot', [ChatController::class, 'testBot'])->name('chat.test-bot');

    // ===== THERMAL PRINT (Admin, Staff & Super Admin) =====
    Route::middleware('role:Admin,Staff,Super Admin')->group(function () {
        Route::get('/print/servis/{servis}', [ThermalPrintController::class, 'servis'])->name('print.servis');
        Route::get('/print/penjualan-sparepart/{penjualan_sparepart}', [ThermalPrintController::class, 'penjualanSparepart'])->name('print.penjualan-sparepart');
        Route::get('/print/jual-beli/{jualBeli}', [ThermalPrintController::class, 'jualBeli'])->name('print.jual-beli');
    });

    // Admin & Staff & Super Admin routes
    Route::middleware('role:Admin,Staff,Super Admin')->group(function () {
        // Servis
        Route::get('/servis/{servis}/detail-json', [ServisController::class, 'detailJson'])->name('servis.detail-json');
        Route::get('/servis/{servis}/nota-pdf', [ServisController::class, 'notaPdf'])->name('servis.nota-pdf');
        Route::get('/servis/{servis}/preview-wa-nota', [ServisController::class, 'previewWaNota'])->name('servis.preview-wa-nota');
        Route::post('/servis/{servis}/kirim-wa-nota', [ServisController::class, 'kirimWaNota'])->name('servis.kirim-wa-nota');
        Route::post('/servis/{servis}/diambil', [ServisController::class, 'konfirmasiDiambil'])->name('servis.diambil');
        Route::post('/servis/{servis}/batal', [ServisController::class, 'batal'])->name('servis.batal');
        Route::post('/servis/{servis}/quick-status', [ServisController::class, 'quickStatus'])->name('servis.quick-status');
        Route::post('/servis/bulk-destroy', [ServisController::class, 'bulkDestroy'])->name('servis.bulk-destroy');
        Route::resource('servis', ServisController::class)->parameter('servis', 'servis');
    });

    // Banner Iklan — moved to Super Admin only section below

    // Arsip & Lacak Servis (all authenticated)
    Route::get('/arsip-servis', [ArsipServisController::class, 'index'])->name('arsip-servis.index');
    Route::get('/arsip-servis/lacak/{kode}', [ArsipServisController::class, 'lacak'])->name('arsip-servis.lacak');
    Route::get('/arsip-servis/print/{id}', [ArsipServisController::class, 'print'])->name('arsip-servis.print');

    // ===== ACTIVATION REQUEST (Admin Cabang) =====
    Route::get('/activation-request', [ActivationRequestController::class, 'index'])->name('activation-request.index');
    Route::post('/activation-request', [ActivationRequestController::class, 'store'])->name('activation-request.store');

    // ===== LAPORAN KEUANGAN — Admin & Staff =====
    Route::middleware('role:Admin,Staff')->group(function () {
        Route::get('/laporan-keuangan', [LaporanKeuanganController::class, 'index'])->name('laporan-keuangan.index');
        Route::get('/laporan-keuangan/export', [LaporanKeuanganController::class, 'export'])->name('laporan-keuangan.export');
    });

    // ===== STOK + PEMBELIAN + KARTU STOK =====
    // Admin (cabang pusat enterprise) DAN Admin Cabang Anak sama-sama boleh
    // mengelola daftar sparepart & pembelian — tapi strictly SESUAI CABANG MASING-MASING
    // (guard per-cabang ada di StokController::checkCabangAccess & PembelianController::checkCabangAccess)
    Route::middleware('role:Admin,Admin Cabang Anak')->group(function () {
        // Stok (daftar sparepart)
        Route::post('/quick-stok', [StokController::class, 'quickUpdate'])->name('stok.quick-update');
        Route::get('/stok/export-excel', [StokController::class, 'exportExcel'])->name('stok.export-excel');
        Route::get('/stok/template-excel', [StokController::class, 'templateExcel'])->name('stok.template-excel');
        Route::post('/stok/import-excel', [StokController::class, 'importExcel'])->name('stok.import-excel');
        Route::resource('stok', StokController::class);

        // Pembelian Supplier — Final (stok otomatis naik, hutang supplier, retur, nota)
        Route::get('/pembelian/hutang', [PembelianController::class, 'hutang'])->name('pembelian.hutang');
        Route::get('/pembelian/{pembelian}/nota', [PembelianController::class, 'nota'])->name('pembelian.nota');
        Route::post('/pembelian/{pembelian}/bayar-hutang', [PembelianController::class, 'bayarHutang'])->name('pembelian.bayar-hutang');
        Route::post('/pembelian/{pembelian}/retur', [PembelianController::class, 'retur'])->name('pembelian.retur');
        Route::post('/pembelian/{pembelian}/batal', [PembelianController::class, 'batal'])->name('pembelian.batal');
        Route::post('/pembelian/{pembelian}/proses', [PembelianController::class, 'proses'])->name('pembelian.proses');
        Route::post('/pembelian/{pembelian}/selesaikan', [PembelianController::class, 'selesaikan'])->name('pembelian.selesaikan');
        Route::resource('pembelian', PembelianController::class);

        // ===== AKTIVITAS SPAREPART (KARTU STOK) =====
        Route::get('/aktivitas-sparepart', [AktivitasSparepartController::class, 'index'])->name('aktivitas-sparepart.index');
        Route::get('/aktivitas-sparepart/riwayat', [AktivitasSparepartController::class, 'riwayat'])->name('aktivitas-sparepart.riwayat');
        Route::get('/aktivitas-sparepart/{stok}', [AktivitasSparepartController::class, 'show'])->name('aktivitas-sparepart.show');
        Route::get('/aktivitas-sparepart/{stok}/export', [AktivitasSparepartController::class, 'export'])->name('aktivitas-sparepart.export');
    });

    // Admin only routes
    Route::middleware('role:Admin')->group(function () {
        // Master Data
        Route::get('/tipe-hp', [TipeHpController::class, 'index'])->name('tipe-hp.index');
        Route::post('/tipe-hp', [TipeHpController::class, 'store'])->name('tipe-hp.store');
        Route::put('/tipe-hp/{tipeHp}', [TipeHpController::class, 'update'])->name('tipe-hp.update');
        Route::delete('/tipe-hp/{tipeHp}', [TipeHpController::class, 'destroy'])->name('tipe-hp.destroy');

        // Pelanggan
        Route::resource('pelanggan', PelangganController::class);

        // Teknisi
        Route::resource('teknisi', TeknisiController::class);

        // Stok, Pembelian & Kartu Stok dipindah ke grup tersendiri
        // (bisa diakses Admin + Admin Cabang Anak — lihat grup di bawah)

        // Kas
        Route::get('/kas', [KasController::class, 'index'])->name('kas.index');
        Route::post('/kas', [KasController::class, 'store'])->name('kas.store');
        Route::delete('/kas/{ka}', [KasController::class, 'destroy'])->name('kas.destroy');

        // Jual Beli
        Route::post('/jualbeli/{jualBeli}/batal', [JualBeliController::class, 'batal'])->name('jualbeli.batal');
        Route::post('/jualbeli/bulk-destroy', [JualBeliController::class, 'bulkDestroy'])->name('jualbeli.bulk-destroy');
        Route::resource('jualbeli', JualBeliController::class);

        // Pembelian Supplier dipindah ke grup tersendiri (lihat bawah)

        // ===== PAYMENT GATEWAY (Fitur #8) =====
        Route::get('/payment', [PaymentController::class, 'select'])->name('payment.select');
        Route::post('/payment/create', [PaymentController::class, 'create'])->name('payment.create');
        Route::get('/payment/riwayat', [PaymentController::class, 'riwayat'])->name('payment.riwayat');
        Route::get('/payment/{kode}', [PaymentController::class, 'show'])->name('payment.show');
        Route::post('/payment/{kode}/refresh', [PaymentController::class, 'refresh'])->name('payment.refresh');
        Route::get('/payment/return/{kode}', [PaymentController::class, 'returned'])->name('payment.return');

        // ===== WHATSAPP WEB (Fitur #9) =====
        Route::get('/whatsapp', [WhatsAppController::class, 'index'])->name('whatsapp.index');
        Route::get('/whatsapp/qr', [WhatsAppController::class, 'getQr'])->name('whatsapp.qr');
        Route::get('/whatsapp/device-status', [WhatsAppController::class, 'deviceStatus'])->name('whatsapp.device-status');
        Route::get('/whatsapp/poll', [WhatsAppController::class, 'poll'])->name('whatsapp.poll');
        Route::post('/whatsapp/send-auto', [WhatsAppController::class, 'sendAuto'])->name('whatsapp.send-auto');
        Route::get('/whatsapp/room/{room}', [WhatsAppController::class, 'show'])->name('whatsapp.show');
        Route::post('/whatsapp/room/{room}/send', [WhatsAppController::class, 'send'])->name('whatsapp.send');
        Route::post('/whatsapp/room/{room}/archive', [WhatsAppController::class, 'archive'])->name('whatsapp.archive');

        // Penjualan Sparepart (POS)
        Route::get('/penjualan-sparepart/api/products', [PenjualanSparepartController::class, 'getProducts'])->name('penjualan-sparepart.api.products');
        Route::get('/penjualan-sparepart/api/search', [PenjualanSparepartController::class, 'searchProduct'])->name('penjualan-sparepart.api.search');
        Route::get('/penjualan-sparepart/api/search-suggest', [PenjualanSparepartController::class, 'searchSuggest'])->name('penjualan-sparepart.api.search-suggest');
        Route::post('/penjualan-sparepart/cart', [PenjualanSparepartController::class, 'storeCart'])->name('penjualan-sparepart.store-cart');
        Route::post('/penjualan-sparepart/{penjualan_sparepart}/batal', [PenjualanSparepartController::class, 'batal'])->name('penjualan-sparepart.batal');
        Route::post('/penjualan-sparepart/bulk-destroy', [PenjualanSparepartController::class, 'bulkDestroy'])->name('penjualan-sparepart.bulk-destroy');
        Route::resource('penjualan-sparepart', PenjualanSparepartController::class)->parameter('penjualan-sparepart', 'penjualan_sparepart');

        // Tagihan Sparepart (Invoicing to other stores)
        Route::get('/tagihan-sparepart', [TagihanSparepartController::class, 'index'])->name('tagihan-sparepart.index');
        Route::post('/tagihan-sparepart', [TagihanSparepartController::class, 'store'])->name('tagihan-sparepart.store');
        Route::get('/tagihan-sparepart/{tagihan}', [TagihanSparepartController::class, 'show'])->name('tagihan-sparepart.show');
        Route::post('/tagihan-sparepart/{tagihan}/bayar', [TagihanSparepartController::class, 'bayar'])->name('tagihan-sparepart.bayar');
        Route::post('/tagihan-sparepart/{tagihan}/batal', [TagihanSparepartController::class, 'batal'])->name('tagihan-sparepart.batal');
        Route::get('/tagihan-sparepart/{tagihan}/print', [TagihanSparepartController::class, 'print'])->name('tagihan-sparepart.print');

        // Service Price List (Daftar Harga Service)
        Route::get('/service-prices', [ServicePriceController::class, 'index'])->name('service-prices.index');
        Route::post('/service-prices', [ServicePriceController::class, 'store'])->name('service-prices.store');
        Route::put('/service-prices/{servicePrice}', [ServicePriceController::class, 'update'])->name('service-prices.update');
        Route::delete('/service-prices/{servicePrice}', [ServicePriceController::class, 'destroy'])->name('service-prices.destroy');
        Route::get('/api/service-prices/search', [ServicePriceController::class, 'search'])->name('api.service-prices.search');

        // Laporan
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');

        // Barcode
        Route::get('/barcode', [\App\Http\Controllers\BarcodeController::class, 'index'])->name('barcode.index');
        Route::get('/barcode/print', [\App\Http\Controllers\BarcodeController::class, 'print'])->name('barcode.print');
        Route::post('/barcode/generate-all', [\App\Http\Controllers\BarcodeController::class, 'generateAll'])->name('barcode.generate-all');
        Route::post('/barcode/generate/{stok}', [\App\Http\Controllers\BarcodeController::class, 'generateSingle'])->name('barcode.generate');

        // Pengaturan
        Route::get('/pengaturan', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/pengaturan', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/pengaturan/upload-qris', [SettingController::class, 'uploadQris'])->name('settings.upload-qris');
        Route::get('/settings/qris/{cabangId}', [SettingController::class, 'getQris'])->name('settings.get-qris');
        Route::post('/pengaturan/test-fonnte', [SettingController::class, 'testFonnte'])->name('settings.test-fonnte');
        Route::post('/pengaturan/backup-db', [SettingController::class, 'backupDatabase'])->name('settings.backup-db');
        Route::post('/pengaturan/backup-json', [SettingController::class, 'backupJson'])->name('settings.backup-json');
        Route::post('/pengaturan/restore-json', [SettingController::class, 'restoreJson'])->name('settings.restore-json');
        Route::post('/pengaturan/data-reset', [SettingController::class, 'dataReset'])->name('settings.data-reset');
        Route::post('/pengaturan/delete-default-accounts', [SettingController::class, 'deleteDefaultAccounts'])->name('settings.delete-default-accounts');

        // ===== SYNC OFFLINE HISTORY (Fitur #7) — Admin & Super Admin =====
        Route::get('/sync', [SyncController::class, 'index'])->name('sync.index');
        Route::get('/sync/{sync}', [SyncController::class, 'show'])->name('sync.show');

        // Multi Cabang (Super Admin & Enterprise — guard di CabangController constructor)
        Route::resource('cabang', CabangController::class);
        Route::post('/cabang/transfer-stok', [CabangController::class, 'transferStok'])->name('cabang.transfer-stok');
        Route::post('/cabang/transfer-stok-batch', [CabangController::class, 'transferStokBatch'])->name('cabang.transfer-stok-batch');
        Route::post('/cabang/create-account', [CabangController::class, 'createBranchAccount'])->name('cabang.create-account');
        Route::get('/api/cabang-stok', [CabangController::class, 'getStokByCabang'])->name('cabang.get-stok');

        // Pengaturan
        Route::resource('user-management', UserManagementController::class);
        Route::post('/user-management/{user}/toggle-super', [UserManagementController::class, 'toggleSuperAdmin'])->name('user-management.toggle-super');
        Route::post('/user-management/{user}/toggle-paket', [UserManagementController::class, 'togglePaket'])->name('user-management.toggle-paket');

        // ===== SUPER ADMIN ONLY =====

        // Banner Iklan — hanya Super Admin yang boleh kelola
        Route::get('/banner-iklan', [BannerIklanController::class, 'index'])->name('banner-iklan.index');
        Route::post('/banner-iklan', [BannerIklanController::class, 'store'])->name('banner-iklan.store');
    Route::put('/banner-iklan/{banner_iklan}', [BannerIklanController::class, 'update'])->name('banner-iklan.update');
    Route::patch('/banner-iklan/{banner_iklan}', [BannerIklanController::class, 'update']);
    Route::delete('/banner-iklan/{banner_iklan}', [BannerIklanController::class, 'destroy'])->name('banner-iklan.destroy');
        // Serial Number & Aktivasi
        Route::get('/serial-number', [SerialNumberController::class, 'index'])->name('serial-number.index');
        Route::post('/serial-number/generate', [SerialNumberController::class, 'generate'])->name('serial-number.generate');
        Route::post('/serial-number/generate-bulk', [SerialNumberController::class, 'generateBulk'])->name('serial-number.generate-bulk');
        Route::delete('/serial-number/{serialNumber}', [SerialNumberController::class, 'destroy'])->name('serial-number.destroy');

        // Kode Aktivasi Login (untuk user expired)
        Route::get('/activation-code', [\App\Http\Controllers\ActivationCodeController::class, 'index'])->name('activation-code.index');
        Route::post('/activation-code/generate', [\App\Http\Controllers\ActivationCodeController::class, 'generate'])->name('activation-code.generate');
        Route::delete('/activation-code/{activationCode}', [\App\Http\Controllers\ActivationCodeController::class, 'destroy'])->name('activation-code.destroy');

        // Activation Requests Management (Super Admin)
        Route::get('/admin/activation-requests', [ActivationRequestController::class, 'adminIndex'])->name('admin.activation-requests.index');
        Route::post('/admin/activation-requests/{activationRequest}/approve', [ActivationRequestController::class, 'approve'])->name('admin.activation-requests.approve');
        Route::post('/admin/activation-requests/{activationRequest}/reject', [ActivationRequestController::class, 'reject'])->name('admin.activation-requests.reject');
        Route::get('/admin/activation-requests/{activationRequest}', [ActivationRequestController::class, 'show'])->name('admin.activation-requests.show');

        // Bank Accounts (Super Admin only)
        Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
        Route::post('/bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
        Route::put('/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
        Route::delete('/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');

        // Audit Log (Super Admin only - enforced in controller)
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
        Route::get('/audit-log/{audit_log}', [AuditLogController::class, 'show'])->name('audit-log.show');
        Route::get('/audit-log-export', [AuditLogController::class, 'exportCsv'])->name('audit-log.export-csv');
        Route::post('/audit-log/clear', [AuditLogController::class, 'clear'])->name('audit-log.clear');

        // ===== MULTI BAHASA — master data & terjemahan (Super Admin) =====
        Route::get('/admin/languages', [LanguageController::class, 'index'])->name('admin.languages.index');
        Route::post('/admin/languages', [LanguageController::class, 'store'])->name('admin.languages.store');
        Route::put('/admin/languages/{language}', [LanguageController::class, 'update'])->name('admin.languages.update');
        Route::delete('/admin/languages/{language}', [LanguageController::class, 'destroy'])->name('admin.languages.destroy');
        Route::get('/admin/languages/{language}/translations', [LanguageController::class, 'translations'])->name('admin.languages.translations');
        Route::post('/admin/languages/{language}/translations', [LanguageController::class, 'updateTranslations'])->name('admin.languages.translations.update');
        Route::post('/admin/languages/keys', [LanguageController::class, 'addKey'])->name('admin.languages.keys.store');
        Route::delete('/admin/languages/keys', [LanguageController::class, 'destroyKey'])->name('admin.languages.keys.destroy');
    });

    // ===== PENJUALAN GROSIR (Admin & Admin Cabang Anak — data TERPISAH per toko) =====
    Route::middleware('role:Admin,Admin Cabang Anak')->prefix('grosir')->name('grosir.')->group(function () {
        // Dashboard Grosir
        Route::get('/', [GrosirDashboardController::class, 'index'])->name('dashboard');

        // Harga Grosir (Eceran, Grosir 1-3, Reseller, Distributor + Harga Khusus)
        Route::get('/harga', [HargaGrosirController::class, 'index'])->name('harga.index');
        Route::post('/harga', [HargaGrosirController::class, 'store'])->name('harga.store');
        Route::post('/harga/massal', [HargaGrosirController::class, 'massal'])->name('harga.massal');
        Route::get('/harga/khusus', [HargaGrosirController::class, 'khusus'])->name('harga.khusus');
        Route::post('/harga/khusus', [HargaGrosirController::class, 'storeKhusus'])->name('harga.khusus.store');
        Route::delete('/harga/khusus/{harga_khusus}', [HargaGrosirController::class, 'destroyKhusus'])->name('harga.khusus.destroy');

        // Pelanggan Grosir (Data, Reseller, Member, Grosir, Distributor)
        Route::resource('pelanggan', PelangganGrosirController::class)
            ->parameter('pelanggan', 'pelanggan_grosir')
            ->names('pelanggan');

        // API POS grosir
        Route::get('/api/produk', [PenjualanGrosirController::class, 'apiProduk'])->name('penjualan.api.produk');
        Route::get('/api/pelanggan', [PenjualanGrosirController::class, 'apiPelanggan'])->name('penjualan.api.pelanggan');

        // Penjualan Grosir (transaksi, riwayat, nota, invoice, surat jalan)
        Route::get('/penjualan', [PenjualanGrosirController::class, 'index'])->name('penjualan.index');
        Route::get('/penjualan/create', [PenjualanGrosirController::class, 'create'])->name('penjualan.create');
        Route::post('/penjualan', [PenjualanGrosirController::class, 'store'])->name('penjualan.store');
        Route::get('/penjualan/{penjualan_grosir}', [PenjualanGrosirController::class, 'show'])->name('penjualan.show');
        Route::post('/penjualan/{penjualan_grosir}/batal', [PenjualanGrosirController::class, 'batal'])->name('penjualan.batal');
        Route::get('/penjualan/{penjualan_grosir}/nota', [PenjualanGrosirController::class, 'nota'])->name('penjualan.nota');
        Route::get('/penjualan/{penjualan_grosir}/invoice', [PenjualanGrosirController::class, 'invoice'])->name('penjualan.invoice');
        Route::get('/penjualan/{penjualan_grosir}/surat-jalan', [PenjualanGrosirController::class, 'suratJalan'])->name('penjualan.surat-jalan');

        // Pesanan Grosir
        Route::get('/pesanan/api/produk', [PesananGrosirController::class, 'apiProduk'])->name('pesanan.api.produk');
        Route::get('/pesanan', [PesananGrosirController::class, 'index'])->name('pesanan.index');
        Route::get('/pesanan/create', [PesananGrosirController::class, 'create'])->name('pesanan.create');
        Route::post('/pesanan', [PesananGrosirController::class, 'store'])->name('pesanan.store');
        Route::get('/pesanan/{pesanan_grosir}', [PesananGrosirController::class, 'show'])->name('pesanan.show');
        Route::post('/pesanan/{pesanan_grosir}/proses', [PesananGrosirController::class, 'proses'])->name('pesanan.proses');
        Route::post('/pesanan/{pesanan_grosir}/batal', [PesananGrosirController::class, 'batal'])->name('pesanan.batal');
        Route::get('/pesanan/{pesanan_grosir}/checkout', [PesananGrosirController::class, 'checkout'])->name('pesanan.checkout');

        // Retur Grosir
        Route::get('/retur', [ReturGrosirController::class, 'index'])->name('retur.index');
        Route::get('/retur/create', [ReturGrosirController::class, 'create'])->name('retur.create');
        Route::post('/retur', [ReturGrosirController::class, 'store'])->name('retur.store');
        Route::get('/retur/{retur_grosir}', [ReturGrosirController::class, 'show'])->name('retur.show');

        // Piutang Grosir (Aktif, Jatuh Tempo, Pembayaran, Riwayat)
        Route::get('/piutang', [PiutangGrosirController::class, 'index'])->name('piutang.index');
        Route::post('/piutang/{penjualan_grosir}/bayar', [PiutangGrosirController::class, 'bayar'])->name('piutang.bayar');

        // Laporan Grosir (Penjualan, Omzet, Laba, Terlaris, Per Pelanggan/Toko/Gudang, Piutang)
        Route::get('/laporan', [LaporanGrosirController::class, 'index'])->name('laporan.index');

        // Stok Grosir (Toko, Gudang, Minimum, Reservasi)
        Route::get('/stok', [StokGrosirController::class, 'index'])->name('stok.index');
    });

    // User + Staff + Admin routes - daftar servis HP
    Route::middleware('role:User,Staff,Admin')->group(function () {
        Route::get('/my-service', [RegisterServiceController::class, 'index'])->name('my-service.index');
        Route::get('/my-service/create', [RegisterServiceController::class, 'create'])->name('my-service.create');
        Route::post('/my-service', [RegisterServiceController::class, 'store'])->name('my-service.store');
        Route::get('/my-service/{servis}', [RegisterServiceController::class, 'show'])->name('my-service.show');
        Route::post('/my-service/{servis}/update-status', [RegisterServiceController::class, 'updateStatus'])->name('my-service.update-status');
        Route::delete('/my-service/{servis}', [RegisterServiceController::class, 'destroy'])->name('my-service.destroy');
    });

    // ===== TEKNISI DASHBOARD (Teknisi role) =====
    Route::middleware('role:Teknisi')->group(function () {
        Route::get('/teknisi-dashboard', [TeknisiDashboardController::class, 'index'])->name('teknisi-dashboard.index');
        Route::get('/teknisi-dashboard/servis/{id}', [TeknisiDashboardController::class, 'showServis'])->name('teknisi-dashboard.show');
    });
});
