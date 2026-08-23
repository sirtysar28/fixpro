<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\ServisApiController;
use App\Http\Controllers\Api\CabangApiController;
use App\Http\Controllers\Api\ProfileApiController;
use App\Http\Controllers\Api\MasterApiController;

// Auth
Route::post('/login', [AuthApiController::class, 'login']);
Route::post('/register', [AuthApiController::class, 'register']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthApiController::class, 'logout']);
    Route::get('/user', [AuthApiController::class, 'user']);

    // Dashboard
    Route::get('/dashboard', [DashboardApiController::class, 'index']);

    // Cabang
    Route::get('/cabang', [CabangApiController::class, 'index']);
    Route::post('/set-cabang', [CabangApiController::class, 'setCabang']);

    // Servis
    Route::get('/servis', [ServisApiController::class, 'index']);
    Route::get('/servis/arsip', [ServisApiController::class, 'arsip']);
    Route::get('/servis/{id}', [ServisApiController::class, 'show']);
    Route::get('/servis/{id}/detail-json', [ServisApiController::class, 'detailJson']);
    Route::post('/servis', [ServisApiController::class, 'store']);
    Route::put('/servis/{id}', [ServisApiController::class, 'update']);
    Route::delete('/servis/{id}', [ServisApiController::class, 'destroy']);
    Route::post('/servis/{id}/quick-status', [ServisApiController::class, 'quickStatus']);
    Route::post('/servis/{id}/batal', [ServisApiController::class, 'batal']);
    Route::post('/servis/{id}/diambil', [ServisApiController::class, 'diambil']);

    // My Service
    Route::get('/my-service', [ServisApiController::class, 'myService']);
    Route::post('/my-service', [ServisApiController::class, 'myServiceStore']);
    Route::get('/my-service/{id}', [ServisApiController::class, 'myServiceShow']);

    // Serial Number - Redeem
    Route::post('/redeem-serial', [AuthApiController::class, 'redeemSerial']);

    // Profile
    Route::get('/profile', [ProfileApiController::class, 'show']);
    Route::put('/profile', [ProfileApiController::class, 'update']);
    Route::put('/profile/password', [ProfileApiController::class, 'updatePassword']);

    // Teknisi
    Route::get('/teknisi', [MasterApiController::class, 'teknisiIndex']);
    Route::get('/teknisi-list', [MasterApiController::class, 'teknisiList']);
    Route::get('/teknisi/dashboard', [MasterApiController::class, 'teknisiDashboard']);
    Route::post('/teknisi', [MasterApiController::class, 'teknisiStore']);
    Route::put('/teknisi/{id}', [MasterApiController::class, 'teknisiUpdate']);
    Route::delete('/teknisi/{id}', [MasterApiController::class, 'teknisiDestroy']);

    // Pelanggan
    Route::get('/pelanggan', [MasterApiController::class, 'pelangganIndex']);
    Route::post('/pelanggan', [MasterApiController::class, 'pelangganStore']);
    Route::put('/pelanggan/{id}', [MasterApiController::class, 'pelangganUpdate']);
    Route::delete('/pelanggan/{id}', [MasterApiController::class, 'pelangganDestroy']);

    // Stok
    Route::get('/stok', [MasterApiController::class, 'stokIndex']);
    Route::get('/stok-list', [MasterApiController::class, 'stokList']);
    Route::post('/stok', [MasterApiController::class, 'stokStore']);
    Route::put('/stok/{id}', [MasterApiController::class, 'stokUpdate']);
    Route::delete('/stok/{id}', [MasterApiController::class, 'stokDestroy']);

    // Kas
    Route::get('/kas', [MasterApiController::class, 'kasIndex']);
    Route::post('/kas', [MasterApiController::class, 'kasStore']);
    Route::delete('/kas/{id}', [MasterApiController::class, 'kasDestroy']);

    // Jual Beli
    Route::get('/jual-beli', [MasterApiController::class, 'jualbeliIndex']);
    Route::get('/jualbeli', [MasterApiController::class, 'jualbeliIndex']);
    Route::post('/jual-beli', [MasterApiController::class, 'jualbeliStore']);
    Route::post('/jualbeli', [MasterApiController::class, 'jualbeliStore']);
    Route::put('/jual-beli/{id}', [MasterApiController::class, 'jualbeliUpdate']);
    Route::put('/jualbeli/{id}', [MasterApiController::class, 'jualbeliUpdate']);
    Route::delete('/jual-beli/{id}', [MasterApiController::class, 'jualbeliDestroy']);
    Route::delete('/jualbeli/{id}', [MasterApiController::class, 'jualbeliDestroy']);

    // Penjualan Sparepart
    Route::get('/penjualan-sparepart', [MasterApiController::class, 'penjualanSparepartIndex']);
    Route::get('/penjualan-sp', [MasterApiController::class, 'penjualanSparepartIndex']);
    Route::post('/penjualan-sparepart', [MasterApiController::class, 'penjualanSparepartStore']);
    Route::post('/penjualan-sp', [MasterApiController::class, 'penjualanSparepartStore']);
    Route::get('/penjualan-sparepart/{id}', [MasterApiController::class, 'penjualanSparepartShow']);
    Route::get('/penjualan-sp/{id}', [MasterApiController::class, 'penjualanSparepartShow']);

    // Laporan
    Route::get('/laporan', [MasterApiController::class, 'laporanIndex']);
    Route::get('/laporan-keuangan', [MasterApiController::class, 'laporanKeuanganIndex']);
    Route::get('/laporan-keuangan/export', [MasterApiController::class, 'laporanKeuanganExport']);

    // Banner
    Route::get('/banner', [MasterApiController::class, 'bannerIndex']);

    // Tipe HP
    Route::get('/tipe-hp', [MasterApiController::class, 'tipeHpIndex']);
    Route::post('/tipe-hp', [MasterApiController::class, 'tipeHpStore']);
    Route::put('/tipe-hp/{id}', [MasterApiController::class, 'tipeHpUpdate']);
    Route::delete('/tipe-hp/{id}', [MasterApiController::class, 'tipeHpDestroy']);

    // Settings
    Route::get('/settings', [MasterApiController::class, 'settingsIndex']);
    Route::post('/settings', [MasterApiController::class, 'settingsUpdate']);

    // Cabang CRUD (super admin)
    Route::get('/cabang-full', [MasterApiController::class, 'cabangFullIndex']);
    Route::post('/cabang-crud', [MasterApiController::class, 'cabangStore']);
    Route::put('/cabang-crud/{id}', [MasterApiController::class, 'cabangUpdate']);
    Route::delete('/cabang-crud/{id}', [MasterApiController::class, 'cabangDestroy']);

    // User Management (super admin)
    Route::get('/users', [MasterApiController::class, 'userIndex']);
    Route::post('/users', [MasterApiController::class, 'userStore']);
    Route::put('/users/{id}', [MasterApiController::class, 'userUpdate']);
    Route::delete('/users/{id}', [MasterApiController::class, 'userDestroy']);
    Route::post('/users/{id}/toggle-super', [MasterApiController::class, 'userToggleSuper']);

    // Serial Number (Admin)
    Route::get('/serial-number', [MasterApiController::class, 'serialIndex']);
    Route::post('/serial-number/generate', [MasterApiController::class, 'serialGenerate']);
    Route::delete('/serial-number/{id}', [MasterApiController::class, 'serialDestroy']);

    // Audit Log (Super Admin)
    Route::get('/audit-log', [MasterApiController::class, 'auditLogIndex']);
    Route::get('/audit-log/{id}', [MasterApiController::class, 'auditLogShow']);

    // ===== OFFLINE SYNC (Fitur #11) =====
    Route::post('/offline/sync', [\App\Http\Controllers\Api\OfflineSyncController::class, 'sync']);
    Route::get('/offline/sync/status', [\App\Http\Controllers\Api\OfflineSyncController::class, 'status']);
    Route::get('/offline/last-sync', [\App\Http\Controllers\Api\OfflineSyncController::class, 'lastSync']);
    Route::get('/offline/conflicts', [\App\Http\Controllers\Api\OfflineSyncController::class, 'conflicts']);
});
