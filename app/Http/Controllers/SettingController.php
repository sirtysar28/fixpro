<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Cabang;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingController extends Controller
{
    /**
     * Key settings yang PER CABANG (masing-masing punya nilai sendiri)
     */
    private function cabangSettingKeys(): array
    {
        return [
            'nama_toko', 'alamat', 'telp',
            'wa_template', 'wa_api_key',
        ];
    }

    /**
     * Key settings yang GLOBAL (super admin saja)
     */
    private function globalSettingKeys(): array
    {
        return [
            'google_client_id', 'google_client_secret', 'google_redirect_uri',
            'openai_api_key', 'bot_system_prompt',
            'bot_provider', 'gemini_api_key', 'groq_api_key',
            // Fitur #8 — Payment Gateway
            'pg_provider', 'pg_mode', 'pg_api_key', 'pg_private_key', 'pg_merchant_code', 'pg_webhook_token',
            // Fitur #9 — WhatsApp Web
            'wa_webhook_token',
            // Fitur #12 — Kode Aktivasi Login (untuk user expired)
            'admin_wa_number',
        ];
    }

    /**
     * Ambil setting per-cabang. Key-nya tanpa suffix di view.
     */
    private function getCabangSettings(int $cabangId): array
    {
        $allSettings = Setting::pluck('value', 'key')->toArray();
        $result = [];

        // Settings per-cabang
        foreach ($this->cabangSettingKeys() as $key) {
            $cabangKey = $key . '_' . $cabangId;
            // Prioritas: cabang-specific > global > default
            if (isset($allSettings[$cabangKey])) {
                $result[$key] = $allSettings[$cabangKey];
            } elseif (isset($allSettings[$key])) {
                $result[$key] = $allSettings[$key];
            }
        }

        // Global settings (semua bisa baca)
        foreach ($this->globalSettingKeys() as $key) {
            if (isset($allSettings[$key])) {
                $result[$key] = $allSettings[$key];
            }
        }

        // QRIS
        $qrisKey = 'qris_image_' . $cabangId;
        if (isset($allSettings[$qrisKey])) {
            $result['qris_image'] = $allSettings[$qrisKey];
        }

        return $result;
    }

    public function index()
    {
        $user = auth()->user();
        $cabangId = $user->getEffectiveCabangId();
        $activeCabang = Cabang::find($cabangId);

        // Fallback: kalau cabang tidak ditemukan, pakai default
        if (!$activeCabang) {
            $activeCabang = Cabang::first() ?? new Cabang(['nama' => 'Default', 'alamat' => '-', 'telp' => '-']);
            $cabangId = $activeCabang->id ?? $cabangId;
        }

        // Ambil settings per cabang
        $settings = $this->getCabangSettings($cabangId);

        // Default nama_toko dari nama cabang kalau belum diset
        if (empty($settings['nama_toko']) && $activeCabang) {
            $settings['nama_toko'] = $activeCabang->nama;
        }
        if (empty($settings['alamat']) && $activeCabang) {
            $settings['alamat'] = $activeCabang->alamat;
        }
        if (empty($settings['telp']) && $activeCabang) {
            $settings['telp'] = $activeCabang->telp;
        }

        // QRIS dropdown: admin cabang hanya lihat cabang sendiri, super admin lihat semua
        if ($user->isSuperAdmin()) {
            $cabangs = Cabang::where('aktif', true)->orderBy('nama')->get();
        } else {
            $cabangs = Cabang::where('id', $cabangId)->get();
        }

        return view('settings.index', compact('settings', 'activeCabang', 'cabangs'));
    }

    public function update(Request $request)
    {
        $cabangId = auth()->user()->getEffectiveCabangId();
        $isSuperAdmin = auth()->user()->isSuperAdmin();

        // Simpan per-cabang fields (selalu pakai suffix _{cabangId})
        foreach ($this->cabangSettingKeys() as $field) {
            $newValue = $request->input($field);
            if ($newValue !== null) {
                $cabangKey = $field . '_' . $cabangId;
                Setting::set($cabangKey, $newValue);

                // Sinkron juga ke tabel cabang (nama, alamat, telp)
                if (in_array($field, ['nama_toko', 'alamat', 'telp'])) {
                    $cabangField = $field === 'nama_toko' ? 'nama' : $field;
                    Cabang::where('id', $cabangId)->update([$cabangField => $newValue]);
                }
            }
        }

        // Simpan global fields (hanya super admin)
        if ($isSuperAdmin) {
            foreach ($this->globalSettingKeys() as $field) {
                $newValue = $request->input($field);
                if ($newValue !== null) {
                    Setting::set($field, $newValue);
                }
            }
        }

        AuditLogService::custom('settings', 'update', "Mengupdate pengaturan untuk cabang ID: {$cabangId}");
        return redirect()->route('settings.index')->with('success', 'Pengaturan berhasil disimpan!');
    }

    /**
     * Upload QRIS image per cabang
     */
    public function uploadQris(Request $request)
    {
        $request->validate([
            'cabang_id' => 'required|exists:cabang,id',
            'qris_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $cabangId = $request->cabang_id;

        // Admin cabang hanya bisa upload untuk cabang sendiri
        $user = auth()->user();
        if (!$user->isSuperAdmin() && $cabangId != $user->getActiveCabangId()) {
            return redirect()->route('settings.index')->with('error', 'Anda hanya bisa upload QRIS untuk cabang Anda sendiri.');
        }

        // Delete old image if exists
        $oldImage = Setting::get("qris_image_{$cabangId}");
        if ($oldImage && Storage::disk('public')->exists($oldImage)) {
            Storage::disk('public')->delete($oldImage);
        }

        // Store new image
        $path = $request->file('qris_image')->store('qris', 'public');
        Setting::set("qris_image_{$cabangId}", $path);

        AuditLogService::custom('settings', 'upload-qris', "Upload QRIS untuk cabang ID: {$cabangId}");
        return redirect()->route('settings.index')->with('success', 'QRIS berhasil diupload!');
    }

    /**
     * Get QRIS image for a cabang
     */
    public function getQris($cabangId)
    {
        $qris = Setting::get("qris_image_{$cabangId}");
        if (!$qris) return response()->json(['found' => false]);
        return response()->json([
            'found' => true,
            'url' => asset('storage/' . $qris),
        ]);
    }

    /**
     * Validasi / test API Key Fonnte.
     * Memakai endpoint /device (cek status device) untuk memvalidasi koneksi & key.
     */
    public function testFonnte(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isStaff() && !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $cabangId = $user->getEffectiveCabangId();
        // Ambil key dari input (test sebelum simpan) atau dari setting tersimpan
        $apiKey = trim((string) ($request->input('api_key') ?? Setting::get('wa_api_key_' . $cabangId) ?? Setting::get('wa_api_key') ?? ''));

        if ($apiKey === '' || strlen($apiKey) < 10) {
            return response()->json([
                'success' => false,
                'valid'   => false,
                'message' => 'API Key kosong atau tidak valid. Masukkan API Key Fonnte yang benar.',
            ]);
        }

        try {
            $resp = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => $apiKey,
            ])->timeout(15)->post('https://api.fonnte.com/device');

            $body = $resp->json();

            // Fonnte /device mengembalikan status device saat key valid
            if ($resp->successful() && isset($body['status']) && $body['status'] === true) {
                // Format baru: top-level device_status string
                if (isset($body['device_status'])) {
                    $connected = $body['device_status'] === 'connected' ? 1 : 0;
                    return response()->json([
                        'success' => true,
                        'valid'   => true,
                        'message' => 'API Key VALID. Koneksi ke Fonnte berhasil.',
                        'devices' => 1,
                        'connected' => $connected,
                    ]);
                }
                $devices = $body['data'] ?? [];
                $connected = collect($devices)->where('status', 'connected')->count();
                $total = is_array($devices) ? count($devices) : 0;
                return response()->json([
                    'success' => true,
                    'valid'   => true,
                    'message' => 'API Key VALID. Koneksi ke Fonnte berhasil.',
                    'devices' => $total,
                    'connected' => $connected,
                ]);
            }

            // Key ditolak / error lain — sertakan response asli Fonnte agar mudah didiagnosis
            $rawBody = trim((string) $resp->body());
            $reason  = $body['reason'] ?? ($body['message'] ?? null);
            if ($reason === null) {
                $reason = $rawBody !== '' ? ('HTTP ' . $resp->status() . ' — ' . $rawBody) : ('HTTP ' . $resp->status());
            }
            // Pesan spesifik bila server memakai endpoint lama yang sudah dihapus Fonnte
            if (preg_match('/cannot\s+(get|post)/i', $rawBody)) {
                $reason .= ' — Endpoint Fonnte tidak ditemukan (404). Kode di server kemungkinan masih versi lama; pastikan file SettingController & WhatsAppService sudah versi terbaru (POST /device).';
            }
            return response()->json([
                'success' => false,
                'valid'   => false,
                'message' => 'API Key TIDAK valid atau ditolak Fonnte: ' . $reason,
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'success' => false,
                'valid'   => false,
                'message' => 'Tidak bisa terhubung ke server Fonnte. Periksa koneksi internet. (' . $e->getMessage() . ')',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'valid'   => false,
                'message' => 'Gagal validasi: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Backup database to local download
     */
    public function backupDatabase()
    {
        try {
            $dbHost = config('database.connections.mysql.host');
            $dbPort = config('database.connections.mysql.port');
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');

            $filename = 'fixpro_backup_' . date('Y-m-d_His') . '.sql';
            $filepath = storage_path('app/' . $filename);

            $mysqldump = trim(shell_exec('which mysqldump 2>/dev/null') ?? '');

            if ($mysqldump && is_executable($mysqldump)) {
                $command = sprintf(
                    'mysqldump -h%s -P%s -u%s -p%s %s > %s 2>/dev/null',
                    escapeshellarg($dbHost),
                    escapeshellarg($dbPort),
                    escapeshellarg($dbUser),
                    escapeshellarg($dbPass),
                    escapeshellarg($dbName),
                    escapeshellarg($filepath)
                );
                exec($command, $output, $returnVar);

                if ($returnVar === 0 && file_exists($filepath) && filesize($filepath) > 0) {
                    AuditLogService::custom('settings', 'backup', 'Backup database via mysqldump');
                    return response()->download($filepath)->deleteFileAfterSend(true);
                }
            }

            // Fallback: PHP-based export
            $tables = DB::select('SHOW TABLES');
            $sql = "-- FIXPRO Database Backup\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";

            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                $sql .= "-- Table: {$tableName}\n";
                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                $sql .= $createTable[0]->{'Create Table'} . ";\n\n";

                $rows = DB::table($tableName)->get();
                foreach ($rows as $row) {
                    $values = array_map(function ($val) {
                        return $val === null ? 'NULL' : "'" . addslashes($val) . "'";
                    }, (array) $row);
                    $sql .= "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }

            file_put_contents($filepath, $sql);
            AuditLogService::custom('settings', 'backup', 'Backup database via PHP export');
            return response()->download($filepath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return redirect()->route('settings.index')->with('error', 'Gagal backup database: ' . $e->getMessage());
        }
    }

    /**
     * Export data ke JSON
     */
    public function backupJson()
    {
        try {
            $tables = ['users', 'pelanggans', 'teknisis', 'servis', 'stoks', 'kas', 'jual_belis',
                'penjualan_sparepart', 'cabang', 'settings', 'serial_numbers', 'roles',
                'banner_iklan', 'chat_rooms', 'chats', 'audit_logs', 'tipe_hp',
                'activation_requests', 'bank_accounts', 'pembelians', 'stock_transfers', 'sparepart_movements'];

            $data = [];
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $data[$table] = DB::table($table)->get()->toArray();
                }
            }

            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $filename = 'fixpro_backup_' . date('Y-m-d_His') . '.json';

            AuditLogService::custom('settings', 'backup-json', 'Backup data ke JSON');

            return response($json)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return redirect()->route('settings.index')->with('error', 'Gagal backup JSON: ' . $e->getMessage());
        }
    }

    /**
     * Restore data dari JSON
     */
    public function restoreJson(Request $request)
    {
        $request->validate([
            'json_file' => 'required|file|mimes:json,txt|max:51200',
        ]);

        try {
            $content = file_get_contents($request->file('json_file')->path());
            $data = json_decode($content, true);

            if (!$data || !is_array($data)) {
                return redirect()->route('settings.index')->with('error', 'File JSON tidak valid.');
            }

            DB::beginTransaction();
            $restored = 0;
            foreach ($data as $table => $rows) {
                if (Schema::hasTable($table) && is_array($rows)) {
                    if ($table !== 'roles') {
                        DB::table($table)->truncate();
                    }
                    foreach ($rows as $row) {
                        $row = is_object($row) ? (array) $row : $row;
                        if (is_array($row)) {
                            DB::table($table)->insert($row);
                            $restored++;
                        }
                    }
                }
            }
            DB::commit();

            AuditLogService::custom('settings', 'restore-json', "Restore data dari JSON: {$restored} records");
            return redirect()->route('settings.index')->with('success', "Restore berhasil! {$restored} records dipulihkan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('settings.index')->with('error', 'Gagal restore: ' . $e->getMessage());
        }
    }

    /**
     * Hard reset - hapus semua data transaksi
     */
    public function dataReset(Request $request)
    {
        $request->validate([
            'confirm_text' => 'required|string|in:HARD RESET',
        ]);

        try {
            DB::beginTransaction();
            $tables = ['servis', 'penjualan_sparepart', 'kas', 'jual_belis', 'chats', 'chat_rooms', 'audit_logs', 'sparepart_movements'];
            foreach ($tables as $table) {
                DB::table($table)->truncate();
            }
            \App\Models\Stok::query()->update(['stok' => 0]);
            \App\Models\SerialNumber::query()->update(['is_used' => false, 'used_at' => null, 'used_by_user_id' => null]);

            DB::commit();
            AuditLogService::custom('settings', 'data-reset', 'Hard reset! Semua data transaksi dihapus.');
            return redirect()->route('settings.index')->with('success', 'Hard reset berhasil!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('settings.index')->with('error', 'Gagal reset: ' . $e->getMessage());
        }
    }

    /**
     * Hapus akun default/bawaan (demo) yang dibuat oleh seeder.
     * Hanya Super Admin yang boleh. Akun sendiri & akun non-demo tidak ikut dihapus.
     */
    public function deleteDefaultAccounts(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return redirect()->route('settings.index')->with('error', 'Akses ditolak. Hanya Super Admin.');
        }

        $request->validate([
            'confirm_text' => 'required|string|in:HAPUS AKUN DEMO',
        ]);

        // Daftar email akun demo/bawaan dari seeder
        $demoEmails = [
            'admin@fixpro.id',
            'staff@fixpro.id',
        ];

        try {
            $userId = auth()->id();

            // Jangan hapus akun yang sedang login walau email-nya termasuk demo
            $deleted = \App\Models\User::whereIn('email', $demoEmails)
                ->where('id', '!=', $userId)
                ->get();

            if ($deleted->isEmpty()) {
                return redirect()->route('settings.index')->with('info', 'Tidak ada akun demo yang tersisa untuk dihapus.');
            }

            $names = $deleted->pluck('email')->implode(', ');
            $deleted->each(fn($u) => $u->delete());

            AuditLogService::custom('settings', 'delete-default-accounts', 'Hapus akun demo: ' . $names);
            return redirect()->route('settings.index')->with('success', 'Akun demo berhasil dihapus: ' . $names);
        } catch (\Exception $e) {
            return redirect()->route('settings.index')->with('error', 'Gagal hapus akun demo: ' . $e->getMessage());
        }
    }
}
