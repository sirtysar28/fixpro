<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fitur #8 — Payment Gateway (provider-agnostic).
 *
 * Mendukung: Virtual Account, QRIS, E-Wallet, Transfer Bank.
 * Default provider: Tripay (mendukung semua metode di atas untuk pasar Indonesia).
 *
 * Konfigurasi (Settings, super admin):
 *  - pg_provider      : tripay / midtrans / xendit / manual  (saat ini full impl: tripay, manual)
 *  - pg_mode          : production / sandbox
 *  - pg_api_key       : API key publik (untuk create transaction)
 *  - pg_private_key   : Private key (untuk hit server-side & verifikasi signature)
 *  - pg_merchant_code : kode merchant (Tripay)
 *  - pg_webhook_token : token verifikasi webhook
 */
class PaymentGatewayService
{
    private const TRIPAY_BASE_SANDBOX    = 'https://tripay.co.id/api-sandbox';
    private const TRIPAY_BASE_PRODUCTION = 'https://tripay.co.id/api';

    /** Daftar metode pembayaran yang didukung, dikelompokkan untuk UI. */
    public const METHODS = [
        'va'      => ['VA_BCA', 'VA_BNI', 'VA_BRI', 'VA_MANDIRI', 'VA_PERMATA', 'VA_CIMB'],
        'qris'    => ['QRIS'],
        'ewallet' => ['EWALLET_OVO', 'EWALLET_DANA', 'EWALLET_LINKAJA', 'EWALLET_SHOPEEPAY'],
        'bank'    => ['BANK_BCA', 'BANK_MANDIRI', 'BANK_BNI', 'BANK_BRI'],
        'retail'  => ['RETAIL_ALFAMART', 'RETAIL_INDOMARET'],
    ];

    /* ============================================================
       CONFIG
       ============================================================ */

    public function provider(): string
    {
        return strtolower((string) (Setting::get('pg_provider') ?? 'tripay'));
    }

    public function mode(): string
    {
        return strtolower((string) (Setting::get('pg_mode') ?? 'sandbox')) === 'production'
            ? 'production' : 'sandbox';
    }

    public function apiKey(): string     { return trim((string) (Setting::get('pg_api_key') ?? '')); }
    public function privateKey(): string { return trim((string) (Setting::get('pg_private_key') ?? '')); }
    public function merchantCode(): string { return trim((string) (Setting::get('pg_merchant_code') ?? '')); }
    public function webhookToken(): string { return trim((string) (Setting::get('pg_webhook_token') ?? '')); }

    public function isEnabled(): bool
    {
        return $this->provider() !== 'manual' && strlen($this->apiKey()) >= 5 && strlen($this->privateKey()) >= 5;
    }

    private function baseUrl(): string
    {
        return $this->mode() === 'production' ? self::TRIPAY_BASE_PRODUCTION : self::TRIPAY_BASE_SANDBOX;
    }

    /* ============================================================
       CREATE TRANSACTION
       ============================================================ */

    /**
     * Buat transaksi pembayaran.
     *
     * @param array $params {
     *     method_code: 'VA_BCA' dst,
     *     amount:      nominal (sudah termasuk biaya layanan? — sebaiknya dikenakan ke customer),
     *     reference:   ref internal (mis. SVC-..., TRX-...),
     *     customer_name, customer_email, customer_phone,
     *     return_url, notify_url (webhook),
     *     payable_type, payable_id, cabang_id, user_id, catatan
     * }
     */
    public function createTransaction(array $params): Payment
    {
        $provider = $this->provider();
        $method   = $params['method_code'];
        $amount   = (float) $params['amount'];

        if ($amount <= 0) {
            throw new \DomainException('Nominal pembayaran harus > 0.');
        }

        // Hitung fee customer (biaya admin) sesuai metode (estimasi Tripay)
        $feeCustomer = $this->estimateFee($method, $amount);

        $payment = Payment::create([
            'kode'           => Payment::generateKode(),
            'reference'      => $params['reference'] ?? null,
            'provider'       => $provider,
            'method_code'    => $method,
            'payable_type'   => $params['payable_type'] ?? null,
            'payable_id'     => $params['payable_id'] ?? null,
            'cabang_id'      => $params['cabang_id'] ?? null,
            'user_id'        => $params['user_id'] ?? auth()->id(),
            'customer_name'  => $params['customer_name'] ?? null,
            'customer_email' => $params['customer_email'] ?? null,
            'customer_phone' => $params['customer_phone'] ?? null,
            'amount'         => $amount,
            'fee_customer'   => $feeCustomer,
            'fee_merchant'   => 0,
            'total_bayar'    => $amount + $feeCustomer,
            'status'         => Payment::STATUS_PENDING,
            'catatan'        => $params['catatan'] ?? null,
        ]);

        if ($provider === 'manual' || !$this->isEnabled()) {
            // Mode manual: tidak panggil gateway, admin yang verifikasi manual.
            $payment->update([
                'instructions' => $this->manualInstructions($method, $payment->total_bayar),
                'expired_at'   => now()->addDay(),
            ]);
            return $payment->fresh();
        }

        // Tripay create transaction
        $merchantRef = $payment->kode;
        $signature = hash_hmac('sha256', $this->merchantCode() . $merchantRef . $this->amountInCents($payment->total_bayar), $this->privateKey());

        $payload = [
            'method'         => $this->mapMethodToProvider($method),
            'merchant_ref'   => $merchantRef,
            'amount'         => (int) $this->amountInCents($payment->total_bayar),
            'customer_name'  => $payment->customer_name ?? 'Pelanggan',
            'customer_email' => $payment->customer_email ?? 'no-reply@fixpro.id',
            'customer_phone' => $payment->customer_phone ?? '08000000000',
            'order_items'    => [[
                'name'     => $params['order_name'] ?? ('Pembayaran ' . $merchantRef),
                'price'    => (int) $this->amountInCents($payment->total_bayar),
                'quantity' => 1,
            ]],
            'return_url'  => $params['return_url'] ?? route('payment.return', $payment->kode),
            'expire_at'   => now()->addDay()->format('Y-m-d H:i:s'),
            'signature'   => $signature,
        ];

        try {
            $resp = Http::withHeaders(['Authorization' => 'Bearer ' . $this->apiKey()])
                ->timeout(20)
                ->post($this->baseUrl() . '/transaction/create', $payload);

            $body = $resp->json();

            if ($resp->successful() && (($body['success'] ?? false) === true) && isset($body['data'])) {
                $d = $body['data'];
                $payment->update([
                    'provider_ref' => $d['reference'] ?? null,
                    'pay_url'      => $d['checkout_url'] ?? ($d['pay_url'] ?? null),
                    'va_number'    => $d['pay_code'] ?? ($d['va_number'] ?? null),
                    'qr_string'    => $d['qr_string'] ?? null,
                    'instructions' => $d['instructions'] ?? null,
                    'expired_at'   => isset($d['expired_time']) ? now()->setTimestamp((int) $d['expired_time']) : now()->addDay(),
                    'raw_response' => $body,
                ]);
            } else {
                $reason = $body['message'] ?? ('HTTP ' . $resp->status());
                $payment->update([
                    'status'      => Payment::STATUS_FAILED,
                    'raw_response'=> $body,
                    'catatan'     => 'Gagal create transaksi: ' . $reason,
                ]);
                Log::warning('Tripay create gagal', ['payment' => $payment->kode, 'resp' => $body]);
            }
        } catch (\Exception $e) {
            $payment->update([
                'status'  => Payment::STATUS_FAILED,
                'catatan' => 'Exception: ' . $e->getMessage(),
            ]);
            Log::warning('Tripay create exception', ['payment' => $payment->kode, 'err' => $e->getMessage()]);
        }

        return $payment->fresh();
    }

    /* ============================================================
       DETAIL / STATUS
       ============================================================ */

    /** Ambil status terbaru dari gateway & sync ke DB. */
    public function syncStatus(Payment $payment): Payment
    {
        if (!$this->isEnabled() || !$payment->provider_ref) return $payment;

        try {
            $resp = Http::withHeaders(['Authorization' => 'Bearer ' . $this->apiKey()])
                ->timeout(15)
                ->get($this->baseUrl() . '/transaction/detail', [
                    'reference' => $payment->provider_ref,
                ]);

            $body = $resp->json();
            if ($resp->successful() && ($body['success'] ?? false) && isset($body['data'])) {
                $d = $body['data'];
                $status = $this->mapStatus($d['status'] ?? '');
                $payment->update([
                    'status'          => $status,
                    'paid_at'         => $status === Payment::STATUS_PAID ? ($d['paid_at'] ?? now()) : $payment->paid_at,
                    'diterima'        => ($d['total_fee'] ?? null) ? ($payment->total_bayar - (float) $d['total_fee']) : $payment->diterima,
                    'raw_response'    => $body,
                ]);

                if ($status === Payment::STATUS_PAID) {
                    $this->onPaid($payment);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Tripay detail gagal: ' . $e->getMessage());
        }

        return $payment->fresh();
    }

    /** Verifikasi signature webhook Tripay */
    public function verifyWebhookSignature(array $payload, ?string $signature): bool
    {
        $token = $this->webhookToken();
        // Tripay: callback mengirim X_CALLBACK_SIGNATURE = HMAC sha256 dari json body pakai private key
        if ($token !== '' && $signature && hash_equals($token, $signature)) return true;
        $calc = hash_hmac('sha256', json_encode($payload), $this->privateKey());
        return $signature && hash_equals($calc, $signature);
    }

    /** Proses callback/webhook */
    public function handleWebhook(array $payload): ?Payment
    {
        $ref = $payload['reference'] ?? ($payload['merchant_ref'] ?? null);
        if (!$ref) return null;

        // Cari payment by provider_ref (reference gateway) atau kode (merchant_ref)
        $payment = Payment::where('provider_ref', $ref)->orWhere('kode', $ref)->first();
        if (!$payment) return null;

        $status = $this->mapStatus($payload['status'] ?? '');
        $payment->update([
            'status'          => $status,
            'paid_at'         => $status === Payment::STATUS_PAID ? now() : $payment->paid_at,
            'diterima'        => ($payload['total_received'] ?? null) ? (float) $payload['total_received'] : $payment->diterima,
            'webhook_payload' => $payload,
        ]);

        if ($status === Payment::STATUS_PAID) {
            $this->onPaid($payment);
        }

        return $payment->fresh();
    }

    /* ============================================================
       SIDE EFFECTS saat pembayaran LUNAS
       ============================================================ */

    private function onPaid(Payment $payment): void
    {
        if (!$payment->payable_type || !$payment->payable_id) return;

        try {
            $payable = $payment->payable_type::find($payment->payable_id);
            if (!$payable) return;

            // Servis: catat pembayaran DP/lunas
            if ($payment->payable_type === \App\Models\Servis::class) {
                $this->applyServisPayment($payment, $payable);
            } elseif ($payment->payable_type === \App\Models\TagihanSparepart::class) {
                $this->applyTagihanPayment($payment, $payable);
            } elseif ($payment->payable_type === \App\Models\PenjualanSparepart::class) {
                $payable->update(['metode_bayar' => 'Online (' . $payment->method_code . ')']);
            } elseif ($payment->payable_type === \App\Models\ActivationRequest::class) {
                $this->applyActivationPayment($payment, $payable);
            }
        } catch (\Exception $e) {
            Log::warning('onPaid side-effect error: ' . $e->getMessage());
        }
    }

    private function applyServisPayment(Payment $payment, \App\Models\Servis $servis): void
    {
        $bayar = (float) $payment->amount;

        $cabangId = $payment->cabang_id ?? $servis->cabang_id;
        $lastKas = \App\Models\Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
        $saldo = $lastKas ? (float) $lastKas->saldo : 0;

        \App\Models\Kas::create([
            'tipe'      => 'masuk',
            'cabang_id' => $cabangId,
            'jml'       => $bayar,
            'kategori'  => 'Pembayaran Servis Online',
            'ket'       => "Pembayaran online {$payment->kode} untuk servis {$servis->kode} ({$payment->method_code})",
            'ref'       => $payment->kode,
            'metode'    => 'Online',
            'waktu'     => now(),
            'saldo'     => $saldo + $bayar,
        ]);
    }

    private function applyTagihanPayment(Payment $payment, \App\Models\TagihanSparepart $tagihan): void
    {
        $bayar = (float) $payment->amount;
        if ($bayar <= 0) return;

        $tagihan->increment('dibayar', $bayar);
        $tagihan->decrement('sisa', $bayar);
        if ($tagihan->fresh()->sisa <= 0) {
            $tagihan->update(['status' => 'Lunas', 'sisa' => 0]);
        } else {
            $tagihan->update(['status' => 'Sebagian']);
        }
    }

    private function applyActivationPayment(Payment $payment, \App\Models\ActivationRequest $req): void
    {
        // Tandai sudah dibayar, tunggu approve super admin untuk extend expiry
        $req->update(['nominal_bayar' => $payment->amount]);
    }

    /* ============================================================
       HELPERS
       ============================================================ */

    /** Estimasi fee customer per metode (di-override saat gateway return nilai asli). */
    public function estimateFee(string $methodCode, float $amount): float
    {
        return match (Payment::methodGroup($methodCode)) {
            'va'      => 4000,            // VA bank umumnya flat
            'qris'    => max(500, $amount * 0.007), // 0.7% QRIS
            'ewallet' => $amount * 0.02,  // 2% e-wallet
            'bank'    => 5000,            // Manual bank transfer
            'retail'  => 7500,            // Gerai retail
            default   => 2000,
        };
    }

    /** Mapping method internal (FIXPRO) → kode metode Tripay */
    private function mapMethodToProvider(string $method): string
    {
        return [
            'VA_BCA'             => 'BCVA',
            'VA_BNI'             => 'BNIVA',
            'VA_BRI'             => 'BRIVA',
            'VA_MANDIRI'         => 'MANDIRIVA',
            'VA_PERMATA'         => 'PERMATAVA',
            'VA_CIMB'            => 'CIMBVA',
            'QRIS'               => 'QRIS',
            'EWALLET_OVO'        => 'OVOPAY',
            'EWALLET_DANA'       => 'DANAPAY',
            'EWALLET_LINKAJA'    => 'LINKAJA',
            'EWALLET_SHOPEEPAY'  => 'SHOPEEPAY',
            'BANK_BCA'           => 'BCA',
            'BANK_MANDIRI'       => 'MANDIRI',
            'BANK_BNI'           => 'BNI',
            'BANK_BRI'           => 'BRI',
            'RETAIL_ALFAMART'    => 'ALFAMART',
            'RETAIL_INDOMARET'   => 'INDOMARET',
        ][$method] ?? 'QRIS';
    }

    /** Mapping status Tripay → status internal */
    private function mapStatus(string $providerStatus): string
    {
        $providerStatus = strtolower($providerStatus);
        return match ($providerStatus) {
            'paid', 'settlement', 'capture', 'success' => Payment::STATUS_PAID,
            'expired'                                   => Payment::STATUS_EXPIRED,
            'failed', 'cancel', 'deny', 'void'          => Payment::STATUS_FAILED,
            'refund', 'refunded'                        => Payment::STATUS_REFUNDED,
            default                                     => Payment::STATUS_PENDING,
        };
    }

    /** Konversi nominal → integer sen (Tripay menerima integer, tanpa desimal). */
    private function amountInCents(float $n): float
    {
        return round($n); // rupiah penuh, tanpa desimal
    }

    private function manualInstructions(string $method, float $total): array
    {
        $grp = Payment::methodGroup($method);
        if ($grp === 'va') {
            return [
                'Hubungi admin via WhatsApp untuk mendapatkan nomor Virtual Account.',
                'Nominal yang harus ditransfer: Rp ' . number_format($total, 0, ',', '.'),
                'Pembayaran akan diverifikasi otomatis setelah dana masuk.',
            ];
        }
        if ($grp === 'qris') {
            return [
                'Scan kode QRIS di toko / minta QR ke admin.',
                'Nominal: Rp ' . number_format($total, 0, ',', '.'),
                'Status terverifikasi otomatis setelah pembayaran berhasil.',
            ];
        }
        return [
            'Transfer ke rekening toko (hubungi admin untuk nomor rekening).',
            'Nominal: Rp ' . number_format($total, 0, ',', '.'),
            'Konfirmasi pembayaran ke admin untuk verifikasi manual.',
        ];
    }
}
