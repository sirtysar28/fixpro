<?php

namespace App\Services;

use App\Models\WaRoom;
use App\Models\WaMessage;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Fitur #9 — Integrasi WhatsApp & WhatsApp Web via Fonnte.
 *
 * Tanggung jawab:
 *  - kirim pesan (text / media) ke nomor manapun
 *  - ambil QR Code device untuk login WhatsApp Web
 *  - cek status device (connected / disconnected)
 *  - terima & persist webhook pesan masuk (dipanggil oleh controller)
 *
 * Catatan: API key diambil dari setting per-cabang: wa_api_key_{cabangId}
 *          fallback ke wa_api_key global.
 */
class WhatsAppService
{
    private const FONNTE_BASE = 'https://api.fonnte.com';

    /** Ambil API key aktif untuk cabang (atau global) */
    public function apiKey(?int $cabangId = null): string
    {
        $key = '';
        if ($cabangId) {
            $key = (string) (Setting::get("wa_api_key_{$cabangId}") ?? '');
        }
        if ($key === '') {
            $key = (string) (Setting::get('wa_api_key') ?? '');
        }
        return trim($key);
    }

    /** Cek apakah WA terhubung (API key terisi) */
    public function isEnabled(?int $cabangId = null): bool
    {
        return strlen($this->apiKey($cabangId)) >= 10;
    }

    /**
     * Kirim pesan teks.
     * @return array{success: bool, message_id: ?string, raw: mixed, error: ?string}
     */
    public function sendText(string $target, string $message, ?int $cabangId = null, bool $isAuto = false): array
    {
        $apiKey = $this->apiKey($cabangId);
        if ($apiKey === '') {
            return ['success' => false, 'message_id' => null, 'raw' => null, 'error' => 'API Key Fonnte belum dikonfigurasi.'];
        }

        $target = WaRoom::normalizeNumber($target);

        try {
            $resp = Http::withHeaders([
                'Authorization' => $apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(15)->post(self::FONNTE_BASE . '/send', [
                'target'      => $target,
                'message'     => $message,
                'countryCode' => '62',
            ]);

            $body = $resp->json();

            if ($resp->successful() && ($body['status'] ?? false) === true) {
                // Fonnte /send response format:
                //   "data": { "62xxx": { "id": "...", "status": "success" } }
                // atau (legacy): { "data": { "id": "..." } } / [ { "id": "..." } ]
                $msgId = null;
                $data = $body['data'] ?? null;
                if (is_array($data)) {
                    if (isset($data['id'])) {
                        $msgId = $data['id'];
                    } elseif (array_is_list($data)) {
                        $msgId = $data[0]['id'] ?? null;
                    } else {
                        // object keyed by phone number → ambil entry pertama
                        foreach ($data as $entry) {
                            if (is_array($entry) && !empty($entry['id'])) {
                                $msgId = $entry['id'];
                                break;
                            }
                        }
                    }
                }

                // Persist ke outbox
                $room = WaRoom::resolveRoom($target, null, $cabangId);
                WaMessage::create([
                    'room_id'      => $room->id,
                    'message_id'   => $msgId ? (string) $msgId : null,
                    'from_number'  => null,
                    'to_number'    => $target,
                    'direction'    => 'out',
                    'type'         => 'text',
                    'message'      => $message,
                    'status'       => 'sent',
                    'is_auto'      => $isAuto,
                    'received_at'  => now(),
                ]);

                $room->update([
                    'last_message'    => $message,
                    'last_direction'  => 'out',
                    'last_message_at' => now(),
                ]);

                return ['success' => true, 'message_id' => $msgId ? (string) $msgId : null, 'raw' => $body, 'error' => null];
            }

            // Fonnte error umum: device offline / device not found / saldo kurang
            $reason = $body['reason'] ?? ($body['message'] ?? null) ?? ('HTTP ' . $resp->status());
            $hint = '';
            $reasonLow = strtolower((string) $reason);
            if (str_contains($reasonLow, 'device') && (str_contains($reasonLow, 'offline') || str_contains($reasonLow, 'not found') || str_contains($reasonLow, 'disconnect'))) {
                $hint = ' — Device WhatsApp belum terhubung. Scan QR di menu WhatsApp Web terlebih dahulu.';
            } elseif (str_contains($reasonLow, 'saldo') || str_contains($reasonLow, 'balance') || str_contains($reasonLow, 'credit')) {
                $hint = ' — Saldo Fonnte tidak cukup. Top up di dashboard Fonnte.';
            }
            return ['success' => false, 'message_id' => null, 'raw' => $body, 'error' => $reason . $hint];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Fonnte sendText connection error: ' . $e->getMessage());
            return ['success' => false, 'message_id' => null, 'raw' => null, 'error' => 'Tidak dapat terhubung ke server Fonnte.'];
        } catch (\Exception $e) {
            Log::warning('Fonnte sendText error: ' . $e->getMessage());
            return ['success' => false, 'message_id' => null, 'raw' => null, 'error' => $e->getMessage()];
        }
    }

    /** Kirim pesan otomatis (invoice/tagihan/status servis). Pakai is_auto=true. */
    public function sendAuto(string $target, string $message, ?int $cabangId = null): array
    {
        return $this->sendText($target, $message, $cabangId, true);
    }

    /**
     * Ambil QR Code device (untuk login WhatsApp Web pertama kali).
     * @return array{success: bool, qr?: string, base64?: string, status?: string, message?: string}
     */
    public function getQrCode(?int $cabangId = null): array
    {
        $apiKey = $this->apiKey($cabangId);
        if ($apiKey === '') {
            return ['success' => false, 'connected' => false, 'message' => 'API Key Fonnte belum dikonfigurasi. Buka menu Pengaturan → isi API Key Fonnte.'];
        }

        // ===== Cek status device dulu: jika sudah terhubung, QR tidak diperlukan =====
        $device = $this->deviceStatus($cabangId);
        if (!empty($device['connected'])) {
            return [
                'success'   => false,
                'connected' => true,
                'message'   => 'Device WhatsApp sudah terhubung. QR tidak diperlukan lagi.',
            ];
        }

        try {
            // Fonnte /qr bersifat asynchronous: panggilan pertama biasanya hanya
            // MEMICU pembuatan QR dan merespons {"status":false,"reason":"process"}.
            // QR asli baru muncul beberapa detik kemudian. Karena itu kita retry
            // beberapa kali dengan jeda singkat sampai QR benar-benar siap.
            $maxAttempts = 4;
            $body = null;
            $httpStatus = 0;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                $resp = Http::withHeaders(['Authorization' => $apiKey])
                    ->timeout(25)->post(self::FONNTE_BASE . '/qr');

                $body = $resp->json();
                $httpStatus = $resp->status();

                // Sukses: dapat QR string
                if ($resp->successful() && ($body['status'] ?? false) === true) {
                    $qr = $body['qr'] ?? ($body['data']['qr'] ?? null);
                    $devStatus = $body['device_status'] ?? ($body['data']['device_status'] ?? null);

                    // QR siap ditampilkan
                    if (is_string($qr) && trim($qr) !== '') {
                        return [
                            'success'   => true,
                            'connected' => false,
                            'qr'        => $qr,
                            'base64'    => $body['qr_base64'] ?? null,
                            'status'    => $devStatus ?? 'unknown',
                            'message'   => 'QR berhasil diambil. Scan dengan WhatsApp di HP → Setelan → Perangkat tertaut → Tautkan perangkat.',
                        ];
                    }

                    // status true tapi QR kosong → device sudah terhubung
                    if ($devStatus === 'connected') {
                        return ['success' => false, 'connected' => true, 'message' => 'Device WhatsApp sudah terhubung. QR tidak diperlukan lagi.'];
                    }
                }

                // Masih diproses/loading → tunggu lalu coba lagi
                $reasonLow = strtolower((string) ($body['reason'] ?? ''));
                $stillProcessing = $resp->successful()
                    && (str_contains($reasonLow, 'process') || str_contains($reasonLow, 'loading') || $reasonLow === '');
                if ($attempt < $maxAttempts && $stillProcessing) {
                    sleep(3);
                    continue;
                }
                break;
            }

            // ===== Penanganan error yang informatif =====
            $reason = $body['reason'] ?? ($body['message'] ?? null);
            $reasonLow = strtolower((string) $reason);

            // Device sudah terhubung → Fonnte menolak get qr
            if ($httpStatus === 200 && (str_contains($reasonLow, 'connect') || str_contains($reasonLow, 'logged') || str_contains($reasonLow, 'sudah'))) {
                return [
                    'success'   => false,
                    'connected' => true,
                    'message'   => 'Device WhatsApp sudah terhubung. QR tidak diperlukan lagi.',
                ];
            }

            // QR masih dibuat Fonnte (process) sampai percobaan terakhir
            if (str_contains($reasonLow, 'process') || str_contains($reasonLow, 'loading')) {
                return [
                    'success'    => false,
                    'connected'  => false,
                    'processing' => true,
                    'message'    => 'QR sedang dibuat server Fonnte. Klik "Refresh QR" sekali lagi dalam beberapa detik.',
                    'http_status'=> $httpStatus,
                ];
            }

            // HTTP 401/403 / error lain → beri penjelasan yang bisa ditindaklanjuti
            $rawBody = trim((string) $resp->body());
            // Endpoint lama (/get_qr GET) sudah dihapus Fonnte → responsnya teks "Cannot GET ..."
            if (preg_match('/cannot\s+(get|post)/i', $rawBody)) {
                Log::warning('Fonnte /qr gagal', ['http' => $httpStatus, 'body' => $rawBody]);
                return ['success' => false, 'connected' => false, 'message' => 'Endpoint QR Fonnte tidak ditemukan (HTTP ' . $httpStatus . ' — ' . $rawBody . '). Kode di server kemungkinan masih versi lama; pastikan file WhatsAppService.php sudah diperbarui (POST /qr).', 'http_status' => $httpStatus];
            }

            $hint = '';
            if (in_array($httpStatus, [401, 403]) || str_contains($reasonLow, 'invalid') || str_contains($reasonLow, 'unauthorized')) {
                $hint = ' API Key Fonnte tidak valid/kedaluwarsa. Periksa kembali API Key di Pengaturan.';
            } elseif ($httpStatus === 404 || str_contains($reasonLow, 'not found') || str_contains($reasonLow, 'device not found')) {
                $hint = ' Device Fonnte tidak ditemukan (kemungkinan sudah dihapus/dihubungkan di tempat lain). Buka dashboard Fonnte untuk memastikan device aktif, lalu refresh QR lagi.';
            } elseif (str_contains($reasonLow, 'limit') || str_contains($reasonLow, 'quota')) {
                $hint = ' Kuota/batas API Fonnte tercapai.';
            }

            $message = $reason
                ? ('Fonnte: ' . $reason . $hint)
                : ('Fonnte merespons HTTP ' . $httpStatus . '.' . $hint);

            Log::warning('Fonnte /qr gagal', ['http' => $httpStatus, 'body' => $body]);

            return ['success' => false, 'connected' => false, 'message' => $message, 'http_status' => $httpStatus];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return ['success' => false, 'connected' => false, 'message' => 'Tidak dapat terhubung ke server Fonnte. Cek koneksi internet lalu coba lagi.'];
        } catch (\Exception $e) {
            return ['success' => false, 'connected' => false, 'message' => 'Gagal mengambil QR: ' . $e->getMessage()];
        }
    }

    /**
     * Cek status device (terhubung / terputus).
     */
    public function deviceStatus(?int $cabangId = null): array
    {
        $apiKey = $this->apiKey($cabangId);
        if ($apiKey === '') {
            return ['success' => false, 'connected' => false, 'message' => 'API Key belum dikonfigurasi.'];
        }

        try {
            $resp = Http::withHeaders(['Authorization' => $apiKey])
                ->timeout(15)->post(self::FONNTE_BASE . '/device');

            $body = $resp->json();
            if ($resp->successful() && ($body['status'] ?? false) === true) {
                $data = $body['data'] ?? null;
                $connected = 0;

                // Format terbaru Fonnte: { status:true, device_status:"connected" }
                if (isset($body['device_status'])) {
                    $connected = $body['device_status'] === 'connected' ? 1 : 0;
                    return [
                        'success'   => true,
                        'connected' => $connected > 0,
                        'devices'   => 1,
                        'raw'       => $body,
                    ];
                }

                if (is_array($data)) {
                    if (array_is_list($data)) {
                        // Format lama: array of devices
                        $connected = collect($data)->where('status', 'connected')->count();
                    } elseif (isset($data['device_status'])) {
                        // Format baru: object { device_status: "connected", ... }
                        $connected = $data['device_status'] === 'connected' ? 1 : 0;
                    } else {
                        // Object keyed by device name
                        foreach ($data as $dev) {
                            if (is_array($dev) && (($dev['status'] ?? null) === 'connected' || ($dev['device_status'] ?? null) === 'connected')) {
                                $connected++;
                            }
                        }
                    }
                }

                return [
                    'success'   => true,
                    'connected' => $connected > 0,
                    'devices'   => is_array($data) ? count($data) : 0,
                    'raw'       => $body,
                ];
            }
            $rawBody = trim((string) $resp->body());
            $reason  = $body['reason'] ?? ($body['message'] ?? null);
            if ($reason === null) {
                $reason = $rawBody !== '' ? ('HTTP ' . $resp->status() . ' — ' . $rawBody) : ('HTTP ' . $resp->status());
            }
            if (preg_match('/cannot\s+(get|post)/i', $rawBody)) {
                $reason .= ' — Endpoint Fonnte tidak ditemukan. Periksa apakah kode server sudah versi terbaru (POST /device).';
            }
            return [
                'success'   => true,
                'connected' => false,
                'message'   => $reason,
                'raw'       => $body,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'connected' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Proses webhook masuk dari Fonnte (dipanggil controller).
     * Payload Fonnte umumnya: { message, sender, name, device, filetype, url, filename, ... }
     * Idempotent: jika message_id sudah ada → skip.
     */
    public function handleWebhook(array $payload, ?int $cabangId = null): ?WaMessage
    {
        // Validasi sederhana: harus ada sender & message/url
        $sender = $payload['sender'] ?? ($payload['number'] ?? null);
        if (empty($sender)) return null;

        $msgId = $payload['id'] ?? ($payload['message_id'] ?? null);
        if ($msgId) {
            $existing = WaMessage::where('message_id', (string) $msgId)->first();
            if ($existing) return $existing; // anti duplikat
        }

        $sender = WaRoom::normalizeNumber((string) $sender);
        $name   = $payload['name'] ?? null;
        $text   = $payload['message'] ?? '';

        $type   = 'text';
        $media  = null;
        $caption = null;
        $filename = null;
        $mime = null;

        if (!empty($payload['filetype']) && $payload['filetype'] !== 'text') {
            $type = match ($payload['filetype']) {
                'image'        => 'image',
                'video'        => 'video',
                'audio'        => 'audio',
                'document'     => 'document',
                default        => 'document',
            };
            $media = $payload['url'] ?? null;
            $caption = $text ?: null;
            $filename = $payload['filename'] ?? null;
            $mime = $payload['mime'] ?? null;
        }

        $room = WaRoom::resolveRoom($sender, $name, $cabangId);

        $msg = WaMessage::create([
            'room_id'      => $room->id,
            'message_id'   => $msgId ? (string) $msgId : null,
            'from_number'  => $sender,
            'to_number'    => $payload['receiver'] ?? null,
            'direction'    => 'in',
            'type'         => $type,
            'message'      => $text,
            'media_url'    => $media,
            'caption'      => $caption,
            'filename'     => $filename,
            'mime'         => $mime,
            'status'       => 'delivered',
            'sender_id'    => $payload['sender'] ?? null,
            'device_id'    => $payload['device'] ?? null,
            'received_at'  => now(),
            'meta'         => $payload,
        ]);

        $room->update([
            'last_message'    => $type === 'text' ? $text : "[{$type}]",
            'last_direction'  => 'in',
            'last_message_at' => now(),
            'unread'          => ($room->unread ?? 0) + 1,
        ]);

        return $msg;
    }

    /** Verifikasi token webhook (token diset di Settings oleh Super Admin) */
    public function validateWebhookToken(?string $token): bool
    {
        $expected = (string) (Setting::get('wa_webhook_token') ?? '');
        if ($expected === '') return true; // tidak ada token → terima semua (mode dev)
        return hash_equals($expected, (string) $token);
    }
}
