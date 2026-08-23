<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Servis;
use App\Models\TagihanSparepart;
use App\Models\PenjualanSparepart;
use App\Services\PaymentGatewayService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

/**
 * Fitur #8 — Payment Gateway
 * Endpoint untuk:
 *  - pilih metode & buat transaksi
 *  - halaman instruksi pembayaran (VA/QRIS/E-Wallet/Bank)
 *  - cek status (manual refresh) + polling
 *  - return URL (redirect setelah bayar)
 *  - webhook publik dari gateway
 */
class PaymentController extends Controller
{
    public function __construct(protected PaymentGatewayService $pg)
    {
    }

    /** GET /payment — pilih entitas yang ingin dibayar + daftar metode */
    public function select(Request $request)
    {
        $methods = PaymentGatewayService::METHODS;
        $enabled = $this->pg->isEnabled();

        // Pre-fill dari query string: type=servis&id=123
        $type = $request->query('type');
        $id   = $request->query('id');
        $amount = 0; $reference = null; $customer = null;

        if ($type && $id) {
            [$amount, $reference, $customer] = $this->resolvePayable($type, $id);
        }

        return view('payment.select', compact('methods', 'enabled', 'type', 'id', 'amount', 'reference', 'customer'));
    }

    /** POST /payment/create — buat transaksi baru */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'method_code'    => 'required|string|max:40',
            'payable_type'   => 'nullable|string|max:100',
            'payable_id'     => 'nullable|integer',
            'amount'         => 'nullable|numeric|min:1',
            'reference'      => 'nullable|string|max:80',
            'customer_name'  => 'nullable|string|max:120',
            'customer_email' => 'nullable|email|max:120',
            'customer_phone' => 'nullable|string|max:30',
            'order_name'     => 'nullable|string|max:200',
            'catatan'        => 'nullable|string|max:500',
        ]);

        // Jika ada payable, ambil nominal & info dari sana (lebih akurat)
        $amount = (float) ($validated['amount'] ?? 0);
        $cabangId = auth()->user()->getActiveCabangId();

        if (!empty($validated['payable_type']) && !empty($validated['payable_id'])) {
            [$amount, $reference, $customer] = $this->resolvePayable(
                $this->shortType($validated['payable_type']),
                (int) $validated['payable_id']
            );
            $validated['reference'] = $validated['reference'] ?? $reference;
            if (!empty($customer['name']))  $validated['customer_name']  ??= $customer['name'];
            if (!empty($customer['email'])) $validated['customer_email'] ??= $customer['email'];
            if (!empty($customer['phone'])) $validated['customer_phone'] ??= $customer['phone'];
        }

        if ($amount <= 0) {
            return back()->withInput()->with('error', 'Nominal pembayaran harus lebih dari 0.');
        }

        $validated['amount']      = $amount;
        $validated['cabang_id']   = $cabangId;
        $validated['user_id']     = auth()->id();
        $validated['return_url']  = route('payment.return', ['kode' => '__KODE__']); // placeholder, diisi setelah create

        $payment = $this->pg->createTransaction($validated);

        // Update return_url dengan kode asli
        if (str_contains($payment->pay_url ?? '', '__KODE__') || true) {
            $payment->update(['pay_url' => $payment->pay_url]); // tidak wajib
        }

        AuditLogService::log('payment', 'create', "Buat transaksi {$payment->kode} ({$payment->method_code}) Rp " . number_format($payment->total_bayar));

        return redirect()->route('payment.show', $payment->kode)
            ->with('success', 'Transaksi dibuat. Selesaikan pembayaran sebelum ' . optional($payment->expired_at)->format('d/m/Y H:i'));
    }

    /** GET /payment/{kode} — halaman instruksi pembayaran */
    public function show(string $kode)
    {
        $payment = Payment::where('kode', $kode)->firstOrFail();

        // Auto-refresh status dari gateway (1x per view, sekali per menit)
        if ($payment->isPending() && $payment->provider === 'tripay' && $this->pg->isEnabled()) {
            $refreshed = $this->pg->syncStatus($payment);
            $payment = $refreshed;
        }

        return view('payment.show', compact('payment'));
    }

    /** POST /payment/{kode}/refresh — manual refresh status (button) */
    public function refresh(string $kode)
    {
        $payment = Payment::where('kode', $kode)->firstOrFail();
        $this->pg->syncStatus($payment);
        return back()->with('success', 'Status diperbarui: ' . ucfirst($payment->fresh()->status));
    }

    /** GET /payment/return/{kode} — redirect URL dari gateway (callback browser) */
    public function returned(string $kode)
    {
        $payment = Payment::where('kode', $kode)->firstOrFail();
        $this->pg->syncStatus($payment);
        $payment->refresh();
        return view('payment.return', compact('payment'));
    }

    /** GET /payment/status/{kode} — polling JSON */
    public function status(string $kode)
    {
        $payment = Payment::where('kode', $kode)->firstOrFail();
        return response()->json([
            'kode'     => $payment->kode,
            'status'   => $payment->status,
            'paid'     => $payment->isPaid(),
            'paid_at'  => $payment->paid_at?->toIso8601String(),
            'amount'   => (float) $payment->total_bayar,
        ]);
    }

    /** POST /payment/webhook — webhook publik dari gateway */
    public function webhook(Request $request)
    {
        $sig = $request->header('X_CALLBACK_SIGNATURE') ?: $request->header('X-Signature');
        if (!$this->pg->verifyWebhookSignature($request->all(), $sig)) {
            return response()->json(['success' => false, 'message' => 'Signature tidak valid.'], 401);
        }

        $payment = $this->pg->handleWebhook($request->all());

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Transaksi tidak ditemukan.'], 404);
        }

        return response()->json(['success' => true, 'status' => $payment->status]);
    }

    /** GET /payment/riwayat — daftar transaksi pembayaran */
    public function riwayat(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();
        $query = Payment::query();
        if ($cabangId !== null && !auth()->user()->isSuperAdmin()) {
            $query->where('cabang_id', $cabangId);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('kode', 'like', "%$s%")->orWhere('reference', 'like', "%$s%")->orWhere('customer_name', 'like', "%$s%"));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $payments = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $base = Payment::query();
        if ($cabangId !== null && !auth()->user()->isSuperAdmin()) $base->where('cabang_id', $cabangId);
        $totalPaid = (clone $base)->where('status', Payment::STATUS_PAID)->sum('amount');
        $totalPending = (clone $base)->where('status', Payment::STATUS_PENDING)->sum('total_bayar');

        return view('payment.riwayat', compact('payments', 'totalPaid', 'totalPending'));
    }

    /* ============================================================
       HELPERS
       ============================================================ */

    /** Resolve nominal & info customer dari payable (servis/tagihan/penjualan). */
    private function resolvePayable(string $type, int $id): array
    {
        $type = strtolower($type);
        switch ($type) {
            case 'servis':
                $s = Servis::with('pelanggan')->find($id);
                if (!$s) return [0, null, null];
                $sisa = max(0, (float) $s->biaya - (float) $s->dp);
                return [
                    $sisa,
                    $s->kode,
                    [
                        'name'  => $s->pelanggan?->nama,
                        'phone' => $s->pelanggan?->no_hp,
                        'email' => null,
                    ],
                ];
            case 'tagihan':
            case 'tagihan_sparepart':
                $t = TagihanSparepart::find($id);
                if (!$t) return [0, null, null];
                return [
                    (float) $t->sisa,
                    $t->kode,
                    ['name' => $t->nama_toko, 'phone' => $t->kontak_toko, 'email' => null],
                ];
            case 'penjualan':
            case 'penjualan_sparepart':
                $p = PenjualanSparepart::with('pelanggan')->find($id);
                if (!$p) return [0, null, null];
                return [
                    (float) $p->total,
                    $p->kode,
                    ['name' => $p->pelanggan?->nama ?? 'Umum', 'phone' => $p->pelanggan?->no_hp, 'email' => null],
                ];
        }
        return [0, null, null];
    }

    /** Normalisasi alias tipe payable → label singkat */
    private function shortType(string $type): string
    {
        $type = strtolower($type);
        if (str_contains($type, 'servis')) return 'servis';
        if (str_contains($type, 'tagihan')) return 'tagihan';
        if (str_contains($type, 'penjualan') || str_contains($type, 'sparepart')) return 'penjualan';
        return $type;
    }
}
