<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\InvoiceRetur;
use App\Models\InvoiceReturItem;
use App\Models\InvoiceSparepart;
use App\Models\InvoiceSparepartItem;
use App\Models\InvoiceSparepartLog;
use App\Models\InvoiceSparepartPayment;
use App\Models\Kas;
use App\Models\PelangganGrosir;
use App\Models\Setting;
use App\Models\Stok;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\InvoicePriceService;
use App\Services\SparepartMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * INVOICE SPAREPART — PUSAT TRANSAKSI PENJUALAN FIXPRO
 * Retail + Grosir 1/2/3 + Reseller + Member + Harga Khusus dalam satu invoice.
 * Harga otomatis berdasarkan tipe pelanggan & qty. Piutang, retur, void, WA, cetak.
 */
class InvoiceSparepartController extends Controller
{
    private function checkCabangAccess(InvoiceSparepart $invoice): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) return;
        if ($user->isAdminCabangAnak()) {
            if ($invoice->cabang_id != $user->cabang_id && $invoice->sumber_cabang_id != $user->cabang_id) {
                abort(403, 'Invoice ini bukan milik cabang Anda.');
            }
            return;
        }
        if ($invoice->cabang_id != $user->getActiveCabangId()) {
            abort(403, 'Anda hanya bisa mengakses data cabang Anda sendiri.');
        }
    }

    /** Ambil daftar cabang sumber stok yang boleh dipakai user */
    private function allowedSumberCabang(): array
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            return Cabang::orderBy('nama')->get()->all();
        }
        if ($user->isEnterprise() && $user->isAdmin()) {
            $ids = $user->getAllowedCabangIds();
            return Cabang::whereIn('id', $ids)->orderBy('nama')->get()->all();
        }
        $cabang = Cabang::find($user->getActiveCabangId());
        return $cabang ? [$cabang] : [];
    }

    // ============================================================
    // BUKA INVOICE (POS)
    // ============================================================
    public function create(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();
        if ($cabangId === null) {
            return view('stok.pilih-cabang', ['redirectTo' => route('invoice.create')]);
        }

        // Produk cabang aktif + tier harga (harga spesifik cabang menang atas harga global)
        $stoks = Stok::where('cabang_id', $cabangId)->orderBy('nama')->get();
        $hargaMap = \App\Models\HargaGrosir::where(function ($q) use ($cabangId) {
            $q->where('cabang_id', $cabangId)->orWhereNull('cabang_id');
        })->where('aktif', true)->orderByDesc('cabang_id')->get()->keyBy('stok_id');

        // Pelanggan (tipe: Umum/Member/Grosir/Reseller/Distributor + limit piutang)
        $pelanggans = PelangganGrosir::where(function ($q) use ($cabangId) {
            $q->where('cabang_id', $cabangId)->orWhereNull('cabang_id');
        })->where('aktif', true)->orderBy('nama')->get();

        // Piutang outstanding per pelanggan (untuk info limit)
        $outstanding = InvoiceSparepart::whereIn('status', ['Piutang', 'Sebagian'])
            ->whereNull('void_pada')
            ->selectRaw('pelanggan_grosir_id, SUM(sisa) as total_sisa')
            ->whereNotNull('pelanggan_grosir_id')
            ->groupBy('pelanggan_grosir_id')
            ->pluck('total_sisa', 'pelanggan_grosir_id');

        $sumberCabangs = $this->allowedSumberCabang();
        $maxDiskonPersen = (float) (Setting::get('invoice_diskon_approval_persen') ?? 5);

        // Stats hari ini (modul invoice)
        $today = now()->format('Y-m-d');
        $omsetHariIni = (float) InvoiceSparepart::where('status', '!=', 'Dibatalkan')->whereDate('tanggal', $today)->sum('total');
        $piutangAktif = (float) InvoiceSparepart::whereIn('status', ['Piutang', 'Sebagian'])->sum('sisa');
        $jatuhTempo = InvoiceSparepart::whereIn('status', ['Piutang', 'Sebagian'])
            ->where('jatuh_tempo', '<', $today)->count();

        return view('invoice.create', compact(
            'stoks', 'hargaMap', 'pelanggans', 'outstanding', 'sumberCabangs',
            'maxDiskonPersen', 'omsetHariIni', 'piutangAktif', 'jatuhTempo'
        ));
    }

    /** Mapping produk + seluruh tier harga (untuk grid & API POS) */
    private function mapProduct(Stok $p, $hg = null): array
    {
        return [
            'id' => $p->id,
            'kode' => $p->kode,
            'barcode' => $p->barcode,
            'nama' => $p->nama,
            'kategori' => $p->kategori,
            'merk_hp' => $p->merk_hp,
            'harga_retail' => (float) $p->jual,
            'stok' => (int) $p->stok,
            'satuan' => $p->satuan ?? 'pcs',
            'tiers' => [
                'grosir1' => ['harga' => $hg ? (float) ($hg->harga_grosir1 ?: 0) : 0, 'min_qty' => $hg ? (int) $hg->min_qty_grosir1 : 5],
                'grosir2' => ['harga' => $hg ? (float) ($hg->harga_grosir2 ?: 0) : 0, 'min_qty' => $hg ? (int) $hg->min_qty_grosir2 : 10],
                'grosir3' => ['harga' => $hg ? (float) ($hg->harga_grosir3 ?: 0) : 0, 'min_qty' => $hg ? (int) $hg->min_qty_grosir3 : 20],
                'reseller' => ['harga' => $hg ? (float) ($hg->harga_reseller ?: 0) : 0, 'min_qty' => 1],
                'member' => ['harga' => $hg ? (float) ($hg->harga_member ?: 0) : 0, 'min_qty' => 1],
            ],
        ];
    }

    public function apiProduk(Request $request)
    {
        $cabangId = $request->get('cabang') ?? auth()->user()->getActiveCabangId();
        $q = trim((string) $request->get('q', ''));

        $query = Stok::where('cabang_id', $cabangId);
        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('nama', 'like', "%{$q}%")
                    ->orWhere('kode', 'like', "%{$q}%")
                    ->orWhere('barcode', $q)
                    ->orWhere('merk_hp', 'like', "%{$q}%");
            });
        }
        $stoks = $query->orderBy('nama')->limit(30)->get();

        // Harga khusus pelanggan (jika dipilih)
        $khusus = [];
        if ($request->filled('pelanggan')) {
            $khusus = \App\Models\HargaKhusus::where('pelanggan_grosir_id', $request->pelanggan)
                ->whereIn('stok_id', $stoks->pluck('id'))
                ->pluck('harga', 'stok_id')->all();
        }

        $hargaMap = \App\Models\HargaGrosir::where(function ($qq) use ($cabangId) {
            $qq->where('cabang_id', $cabangId)->orWhereNull('cabang_id');
        })->where('aktif', true)->get()->keyBy('stok_id');

        return response()->json([
            'products' => $stoks->map(function ($p) use ($hargaMap, $khusus) {
                $data = $this->mapProduct($p, $hargaMap->get($p->id));
                $data['tiers']['khusus'] = ['harga' => (float) ($khusus[$p->id] ?? 0), 'min_qty' => 1];
                return $data;
            }),
        ]);
    }

    /** Endpoint kecil: map harga khusus pelanggan (stok_id => harga) */
    public function apiHargaKhusus(Request $request)
    {
        $validated = $request->validate(['pelanggan_id' => 'required|exists:pelanggan_grosirs,id']);
        $map = \App\Models\HargaKhusus::where('pelanggan_grosir_id', $validated['pelanggan_id'])
            ->where('harga', '>', 0)
            ->pluck('harga', 'stok_id');
        return response()->json(['khusus' => $map]);
    }

    // ============================================================
    // SIMPAN INVOICE
    // ============================================================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.stok_id' => 'required|exists:stoks,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga_satuan' => 'required|numeric|min:0',
            'items.*.diskon' => 'nullable|numeric|min:0',
            'items.*.tipe_lcd' => 'nullable|string|max:60',
            'pelanggan_grosir_id' => 'nullable|exists:pelanggan_grosirs,id',
            'nama_pelanggan' => 'nullable|string|max:150',
            'no_wa' => 'nullable|string|max:30',
            'alamat' => 'nullable|string|max:500',
            'sumber_cabang_id' => 'nullable|exists:cabang,id',
            'metode_bayar' => 'required|in:Tunai,Transfer,QRIS,DP,Tempo',
            'bayar' => 'nullable|numeric|min:0',
            'metode_dp' => 'nullable|in:Tunai,Transfer,QRIS',
            'jatuh_tempo' => 'nullable|date',
            'diskon_total' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string|max:500',
            'approval_email' => 'nullable|string',
            'approval_password' => 'nullable|string',
        ]);

        $user = auth()->user();
        $cabangId = $user->getActiveCabangId() ?? $user->getEffectiveCabangId();
        $sumberCabangId = $validated['sumber_cabang_id'] ?? $cabangId;

        // Validasi kepemilikan stok & ketersediaan
        foreach ($validated['items'] as $item) {
            $stok = Stok::find($item['stok_id']);
            if (!$stok || (int) $stok->cabang_id !== (int) $sumberCabangId) {
                return response()->json(['success' => false, 'message' => "Barang ID {$item['stok_id']} tidak tersedia di cabang/gudang sumber stok."], 422);
            }
            if ((int) $stok->stok < (int) $item['qty']) {
                return response()->json(['success' => false, 'message' => "Stok {$stok->nama} tidak cukup. Tersedia: {$stok->stok}"], 422);
            }
        }

        $pelanggan = !empty($validated['pelanggan_grosir_id']) ? PelangganGrosir::find($validated['pelanggan_grosir_id']) : null;

        // ===== Hitung harga otomatis per item =====
        $preparedItems = [];
        $hargaChanges = [];
        $subtotal = 0;
        $diskonItemTotal = 0;

        foreach ($validated['items'] as $item) {
            $stok = Stok::find($item['stok_id']);
            $qty = (int) $item['qty'];
            $resolved = InvoicePriceService::resolve($stok, $qty, $pelanggan, (int) $sumberCabangId);
            $hargaClient = (float) $item['harga_satuan'];
            $jenis = $resolved['jenis'];

            // Kasir mengubah harga manual → catat riwayat perubahan harga
            if (abs($hargaClient - $resolved['harga']) > 0.01) {
                $hargaChanges[] = "{$stok->nama}: otomatis {$resolved['jenis']} Rp " . number_format($resolved['harga']) . " → manual Rp " . number_format($hargaClient);
                $jenis = 'manual';
            }

            $diskon = (float) ($item['diskon'] ?? 0);
            $itemSubtotal = max(0, $qty * $hargaClient - $diskon);
            $subtotal += $itemSubtotal;
            $diskonItemTotal += $diskon;

            $preparedItems[] = [
                'stok' => $stok,
                'qty' => $qty,
                'harga' => $hargaClient,
                'jenis' => $jenis,
                'diskon' => $diskon,
                'subtotal' => $itemSubtotal,
                'tipe_lcd' => $item['tipe_lcd'] ?? null,
            ];
        }

        $diskonTotal = (float) ($validated['diskon_total'] ?? 0);
        $total = max(0, $subtotal - $diskonTotal);

        // ===== Approval diskon =====
        $approvalOleh = null;
        $maxDiskonPersen = (float) (Setting::get('invoice_diskon_approval_persen') ?? 5);
        $totalDiskon = $diskonItemTotal + $diskonTotal;
        $diskonPersen = $subtotal > 0 ? ($totalDiskon / $subtotal) * 100 : 0;
        if ($totalDiskon > 0 && $diskonPersen > $maxDiskonPersen) {
            $approver = null;
            if (!empty($validated['approval_email']) && !empty($validated['approval_password'])) {
                $cand = User::where('email', $validated['approval_email'])->first();
                if ($cand && Hash::check($validated['approval_password'], $cand->password)
                    && ($cand->isAdmin() || $cand->isSuperAdmin())) {
                    $approver = $cand;
                }
            }
            if (!$approver) {
                return response()->json([
                    'success' => false,
                    'need_approval' => true,
                    'message' => "Diskon " . number_format($diskonPersen, 1) . "% melebihi batas {$maxDiskonPersen}% — wajib approval Admin (isi email & password Admin).",
                ], 422);
            }
            $approvalOleh = $approver->id;
        }

        // ===== Pembayaran & piutang =====
        $metode = $validated['metode_bayar'];
        $bayar = in_array($metode, ['Tunai', 'Transfer', 'QRIS']) ? $total : min((float) ($validated['bayar'] ?? 0), $total);
        $sisa = max(0, $total - $bayar);
        $status = $sisa <= 0 ? 'Lunas' : ($bayar > 0 ? 'Sebagian' : 'Piutang');
        $jatuhTempo = !empty($validated['jatuh_tempo']) ? $validated['jatuh_tempo'] : ($sisa > 0 ? now()->addDays(30)->format('Y-m-d') : null);

        // ===== Cek limit piutang pelanggan =====
        if ($sisa > 0 && $pelanggan && (float) $pelanggan->limit_piutang > 0) {
            $outstanding = (float) InvoiceSparepart::whereIn('status', ['Piutang', 'Sebagian'])
                ->where('pelanggan_grosir_id', $pelanggan->id)->sum('sisa');
            if ($outstanding + $sisa > (float) $pelanggan->limit_piutang) {
                return response()->json([
                    'success' => false,
                    'message' => "Limit piutang {$pelanggan->nama} terlampaui. Limit: Rp " . number_format($pelanggan->limit_piutang)
                        . ", piutang berjalan: Rp " . number_format($outstanding)
                        . ", invoice ini menambah Rp " . number_format($sisa) . ".",
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $noInvoice = InvoiceSparepart::generateNoInvoice();
            $invoice = InvoiceSparepart::create([
                'no_invoice' => $noInvoice,
                'cabang_id' => $cabangId,
                'sumber_cabang_id' => $sumberCabangId,
                'user_id' => $user->id,
                'pelanggan_grosir_id' => $pelanggan?->id,
                'nama_pelanggan' => $pelanggan?->nama ?? ($validated['nama_pelanggan'] ?? 'Umum'),
                'no_wa' => $pelanggan?->no_hp ?? ($validated['no_wa'] ?? null),
                'alamat' => $pelanggan?->alamat ?? ($validated['alamat'] ?? null),
                'tipe_pelanggan' => $pelanggan?->tipe ?? 'Umum',
                'tanggal' => now(),
                'subtotal' => $subtotal,
                'diskon_item' => $diskonItemTotal,
                'diskon_total' => $diskonTotal,
                'total' => $total,
                'dibayar' => 0,
                'sisa' => 0,
                'metode_bayar' => $metode,
                'status' => 'Lunas',
                'jatuh_tempo' => $jatuhTempo,
                'approval_diskon_oleh' => $approvalOleh,
                'catatan' => $validated['catatan'] ?? null,
            ]);

            foreach ($preparedItems as $pi) {
                $stok = $pi['stok'];
                InvoiceSparepartItem::create([
                    'invoice_sparepart_id' => $invoice->id,
                    'stok_id' => $stok->id,
                    'kode' => $stok->kode,
                    'nama' => $stok->nama,
                    'merk_hp' => $stok->merk_hp,
                    'tipe_lcd' => $pi['tipe_lcd'],
                    'qty' => $pi['qty'],
                    'harga_satuan' => $pi['harga'],
                    'jenis_harga' => $pi['jenis'],
                    'diskon' => $pi['diskon'],
                    'harga_modal' => (float) $stok->modal,
                    'subtotal' => $pi['subtotal'],
                ]);

                // Stok otomatis berkurang SESUAI cabang/gudang sumber
                $stok->decrement('stok', $pi['qty']);
                SparepartMovementService::record($stok, 'keluar', 'invoice_sparepart', $pi['qty'], [
                    'referensi' => $noInvoice,
                    'referensi_id' => $invoice->id,
                    'referensi_model' => $invoice,
                    'harga_satuan' => $pi['harga'],
                    'cabang_id' => $sumberCabangId,
                    'catatan' => 'Invoice sparepart',
                ]);
            }

            // Pembayaran awal
            if ($bayar > 0) {
                $metodePembayaran = in_array($metode, ['Tunai', 'Transfer', 'QRIS']) ? $metode : ($validated['metode_dp'] ?? 'Tunai');
                InvoiceSparepartPayment::create([
                    'invoice_sparepart_id' => $invoice->id,
                    'user_id' => $user->id,
                    'jumlah' => $bayar,
                    'metode' => $metodePembayaran,
                    'tanggal' => now(),
                    'catatan' => 'Pembayaran awal (' . $metode . ')',
                ]);
                $this->recordKas('masuk', $sumberCabangId, $bayar, $metodePembayaran, $noInvoice, 'Invoice ' . $noInvoice . ($metode === 'DP' ? ' (DP)' : ''));
                $invoice->applyPayment($bayar);
            } else {
                $invoice->update(['status' => 'Piutang', 'sisa' => $total]);
            }

            // Log pembuatan invoice + riwayat perubahan harga
            InvoiceSparepartLog::create([
                'invoice_sparepart_id' => $invoice->id,
                'user_id' => $user->id,
                'aksi' => 'create',
                'deskripsi' => "Invoice dibuat — {$status}, total Rp " . number_format($total)
                    . ($approvalOleh ? " (diskon di-approve oleh Admin #{$approvalOleh})" : ''),
                'data_baru' => ['total' => $total, 'diskon' => $diskonTotal, 'status' => $status],
            ]);
            if ($hargaChanges) {
                InvoiceSparepartLog::create([
                    'invoice_sparepart_id' => $invoice->id,
                    'user_id' => $user->id,
                    'aksi' => 'harga',
                    'deskripsi' => 'Perubahan harga manual: ' . implode('; ', $hargaChanges),
                ]);
            }

            DB::commit();
            AuditLogService::log('invoice_sparepart', 'create', "Invoice {$noInvoice} — total Rp " . number_format($total) . " ({$status})", $invoice);

            return response()->json([
                'success' => true,
                'message' => "Invoice {$noInvoice} berhasil dibuat!",
                'data' => [
                    'no_invoice' => $noInvoice,
                    'id' => $invoice->id,
                    'total' => $total,
                    'bayar' => $bayar,
                    'sisa' => $sisa,
                    'status' => $status,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan invoice: ' . $e->getMessage()], 500);
        }
    }

    /** Catat mutasi kas harian */
    private function recordKas(string $tipe, ?int $cabangId, float $jml, string $metode, string $ref, string $ket): void
    {
        $lastKas = Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
        $lastSaldo = $lastKas ? (float) $lastKas->saldo : 0;
        $newSaldo = $tipe === 'masuk' ? $lastSaldo + $jml : $lastSaldo - $jml;
        Kas::create([
            'tipe' => $tipe,
            'cabang_id' => $cabangId,
            'jml' => $jml,
            'kategori' => $tipe === 'masuk' ? 'Invoice Sparepart' : 'Pengembalian Invoice',
            'ket' => $ket,
            'metode' => $metode,
            'ref' => $ref,
            'waktu' => now(),
            'saldo' => $newSaldo,
        ]);
    }

    // ============================================================
    // RIWAYAT INVOICE
    // ============================================================
    public function riwayat(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();
        if ($cabangId === null) {
            return view('stok.pilih-cabang', ['redirectTo' => route('invoice.riwayat')]);
        }

        $query = InvoiceSparepart::with(['items', 'kasir', 'pelanggan'])
            ->where('cabang_id', $cabangId);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('no_invoice', 'like', "%$s%")->orWhere('nama_pelanggan', 'like', "%$s%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('metode')) $query->where('metode_bayar', $request->metode);
        if ($request->filled('tipe')) $query->where('tipe_pelanggan', $request->tipe);
        if ($request->filled('dari')) $query->whereDate('tanggal', '>=', $request->dari);
        if ($request->filled('sampai')) $query->whereDate('tanggal', '<=', $request->sampai);

        $invoices = $query->orderByDesc('tanggal')->paginate(25)->appends($request->query());

        // ===== Dashboard penjualan =====
        $base = InvoiceSparepart::where('cabang_id', $cabangId)->where('status', '!=', 'Dibatalkan');
        $today = now()->format('Y-m-d');
        $stats = [
            'penjualan_hari_ini' => (clone $base)->whereDate('tanggal', $today)->sum('total'),
            'total_invoice' => (clone $base)->count(),
            'retail' => (clone $base)->whereIn('tipe_pelanggan', ['Umum'])->sum('total'),
            'grosir' => (clone $base)->whereIn('tipe_pelanggan', ['Grosir', 'Distributor'])->sum('total'),
            'reseller' => (clone $base)->where('tipe_pelanggan', 'Reseller')->sum('total'),
            'member' => (clone $base)->where('tipe_pelanggan', 'Member')->sum('total'),
            'piutang' => InvoiceSparepart::where('cabang_id', $cabangId)->whereIn('status', ['Piutang', 'Sebagian'])->sum('sisa'),
            'jatuh_tempo' => InvoiceSparepart::where('cabang_id', $cabangId)->whereIn('status', ['Piutang', 'Sebagian'])
                ->where('jatuh_tempo', '<', $today)->count(),
            'pembayaran_masuk' => InvoiceSparepartPayment::whereDate('tanggal', $today)->whereHas('invoice', fn ($q) => $q->where('cabang_id', $cabangId))->sum('jumlah'),
        ];

        return view('invoice.riwayat', compact('invoices', 'stats'));
    }

    public function show(InvoiceSparepart $invoice)
    {
        $this->checkCabangAccess($invoice);
        $invoice->load(['items.stok', 'payments.user', 'logs.user', 'returs.items', 'kasir', 'cabang', 'sumberCabang', 'pelanggan', 'approvalDiskonOleh']);

        $waMessage = $this->buildWaMessage($invoice);
        $maxDiskonPersen = (float) (Setting::get('invoice_diskon_approval_persen') ?? 5);
        $waPhone = preg_replace('/[^0-9]/', '', $invoice->no_wa ?? '');
        if (str_starts_with($waPhone, '0')) $waPhone = '62' . substr($waPhone, 1);
        elseif (str_starts_with($waPhone, '+62')) $waPhone = substr($waPhone, 1);
        elseif ($waPhone && !str_starts_with($waPhone, '62')) $waPhone = '62' . $waPhone;

        return view('invoice.show', compact('invoice', 'waMessage', 'waPhone', 'maxDiskonPersen'));
    }

    // ============================================================
    // DISKON (ubah + approval + riwayat)
    // ============================================================
    public function updateDiskon(Request $request, InvoiceSparepart $invoice)
    {
        $this->checkCabangAccess($invoice);
        if ($invoice->isVoid()) {
            return response()->json(['success' => false, 'message' => 'Invoice sudah dibatalkan.'], 422);
        }

        $validated = $request->validate([
            'diskon_total' => 'required|numeric|min:0',
            'approval_email' => 'nullable|string',
            'approval_password' => 'nullable|string',
        ]);

        $diskonBaru = min((float) $validated['diskon_total'], (float) $invoice->subtotal + (float) $invoice->diskon_total);
        $totalBaru = max(0, (float) $invoice->subtotal - $diskonBaru);
        $netTotal = max(0, $totalBaru - (float) $invoice->total_retur);

        // Approval bila melebihi batas
        $maxDiskonPersen = (float) (Setting::get('invoice_diskon_approval_persen') ?? 5);
        $bruto = (float) $invoice->subtotal + (float) $invoice->diskon_total;
        $diskonPersen = $bruto > 0 ? (($diskonBaru + (float) $invoice->diskon_item) / $bruto) * 100 : 0;
        $approvalOleh = $invoice->approval_diskon_oleh;
        if ($diskonPersen > $maxDiskonPersen) {
            $approver = null;
            if (!empty($validated['approval_email']) && !empty($validated['approval_password'])) {
                $cand = User::where('email', $validated['approval_email'])->first();
                if ($cand && \Hash::check($validated['approval_password'], $cand->password)
                    && ($cand->isAdmin() || $cand->isSuperAdmin())) {
                    $approver = $cand;
                }
            }
            if (!$approver) {
                return response()->json(['success' => false, 'need_approval' => true,
                    'message' => "Diskon " . number_format($diskonPersen, 1) . "% melebihi batas {$maxDiskonPersen}% — wajib approval Admin."], 422);
            }
            $approvalOleh = $approver->id;
        }

        $oldDiskon = (float) $invoice->diskon_total;
        $sisaBaru = max(0, $netTotal - (float) $invoice->dibayar);
        $statusBaru = $sisaBaru <= 0 ? 'Lunas' : ((float) $invoice->dibayar > 0 ? 'Sebagian' : 'Piutang');

        $invoice->update([
            'diskon_total' => $diskonBaru,
            'total' => $totalBaru,
            'sisa' => $sisaBaru,
            'status' => $statusBaru,
            'approval_diskon_oleh' => $approvalOleh,
            'updated_by' => auth()->id(),
        ]);

        InvoiceSparepartLog::create([
            'invoice_sparepart_id' => $invoice->id,
            'user_id' => auth()->id(),
            'aksi' => 'diskon',
            'deskripsi' => "Diskon transaksi diubah: Rp " . number_format($oldDiskon) . " → Rp " . number_format($diskonBaru)
                . ($approvalOleh && $approvalOleh != $invoice->getOriginal('approval_diskon_oleh') ? " (di-approve Admin #{$approvalOleh})" : ""),
            'data_lama' => ['diskon_total' => $oldDiskon, 'total' => (float) $invoice->getOriginal('total')],
            'data_baru' => ['diskon_total' => $diskonBaru, 'total' => $totalBaru],
        ]);
        AuditLogService::log('invoice_sparepart', 'diskon', "Ubah diskon invoice {$invoice->no_invoice}: {$oldDiskon} → {$diskonBaru}", $invoice);

        return response()->json(['success' => true, 'message' => 'Diskon diperbarui.', 'data' => ['total' => $totalBaru, 'sisa' => $sisaBaru, 'status' => $statusBaru]]);
    }

    // ============================================================
    // VOID INVOICE
    // ============================================================
    public function void(Request $request, InvoiceSparepart $invoice)
    {
        $this->checkCabangAccess($invoice);
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Void invoice hanya boleh oleh Admin.'], 403);
        }
        if ($invoice->isVoid()) {
            return response()->json(['success' => false, 'message' => 'Invoice sudah dibatalkan.'], 422);
        }
        $validated = $request->validate(['alasan' => 'required|string|min:3|max:500']);

        DB::beginTransaction();
        try {
            // Kembalikan stok
            foreach ($invoice->items as $item) {
                if ($item->stok) {
                    $item->stok->increment('stok', $item->qty);
                    SparepartMovementService::record($item->stok, 'masuk', 'void_invoice', (int) $item->qty, [
                        'referensi' => 'VOID-' . $invoice->no_invoice,
                        'cabang_id' => $invoice->sumber_cabang_id,
                        'catatan' => 'Void invoice: ' . $validated['alasan'],
                    ]);
                }
            }
            // Balik kas semua pembayaran yang masuk
            foreach ($invoice->payments as $pay) {
                $this->recordKas('keluar', $invoice->sumber_cabang_id, (float) $pay->jumlah, $pay->metode,
                    'VOID-' . $invoice->no_invoice, 'Pembatalan invoice ' . $invoice->no_invoice);
            }

            $invoice->update([
                'status' => 'Dibatalkan',
                'alasan_void' => $validated['alasan'],
                'void_oleh' => $user->id,
                'void_pada' => now(),
                'updated_by' => $user->id,
            ]);

            InvoiceSparepartLog::create([
                'invoice_sparepart_id' => $invoice->id,
                'user_id' => $user->id,
                'aksi' => 'void',
                'deskripsi' => 'Invoice dibatalkan (void). Alasan: ' . $validated['alasan'],
            ]);
            AuditLogService::log('invoice_sparepart', 'void', "Void invoice {$invoice->no_invoice}. Alasan: {$validated['alasan']}", $invoice);

            DB::commit();
            return response()->json(['success' => true, 'message' => "Invoice {$invoice->no_invoice} dibatalkan. Stok & kas dikembalikan."]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal void: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // PEMBAYARAN & PIUTANG
    // ============================================================
    public function pembayaran(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();
        $payments = InvoiceSparepartPayment::with(['invoice', 'user'])
            ->whereHas('invoice', fn ($q) => $q->where('cabang_id', $cabangId))
            ->orderByDesc('tanggal');

        if ($request->filled('dari')) $payments->whereDate('tanggal', '>=', $request->dari);
        if ($request->filled('sampai')) $payments->whereDate('tanggal', '<=', $request->sampai);
        if ($request->filled('metode')) $payments->where('metode', $request->metode);

        $payments = $payments->paginate(25)->appends($request->query());
        $today = now()->format('Y-m-d');
        $masukHariIni = InvoiceSparepartPayment::whereDate('tanggal', $today)
            ->whereHas('invoice', fn ($q) => $q->where('cabang_id', $cabangId))->sum('jumlah');

        return view('invoice.pembayaran', compact('payments', 'masukHariIni'));
    }

    public function bayar(Request $request, InvoiceSparepart $invoice)
    {
        $this->checkCabangAccess($invoice);
        if ($invoice->isVoid()) {
            return response()->json(['success' => false, 'message' => 'Invoice sudah dibatalkan.'], 422);
        }
        $validated = $request->validate([
            'jumlah' => 'required|numeric|min:1',
            'metode' => 'required|in:Tunai,Transfer,QRIS',
            'catatan' => 'nullable|string|max:255',
        ]);

        $netTotal = max(0, (float) $invoice->total - (float) $invoice->total_retur);
        $sisaSekarang = max(0, $netTotal - (float) $invoice->dibayar);
        if ($sisaSekarang <= 0) {
            return response()->json(['success' => false, 'message' => 'Invoice ini sudah lunas.'], 422);
        }
        $jumlah = min((float) $validated['jumlah'], $sisaSekarang);

        DB::beginTransaction();
        try {
            InvoiceSparepartPayment::create([
                'invoice_sparepart_id' => $invoice->id,
                'user_id' => auth()->id(),
                'jumlah' => $jumlah,
                'metode' => $validated['metode'],
                'tanggal' => now(),
                'catatan' => $validated['catatan'] ?? null,
            ]);
            $this->recordKas('masuk', $invoice->sumber_cabang_id, $jumlah, $validated['metode'],
                $invoice->no_invoice, 'Pelunasan invoice ' . $invoice->no_invoice);
            $invoice->applyPayment($jumlah);

            InvoiceSparepartLog::create([
                'invoice_sparepart_id' => $invoice->id,
                'user_id' => auth()->id(),
                'aksi' => 'bayar',
                'deskripsi' => 'Pembayaran Rp ' . number_format($jumlah) . ' via ' . $validated['metode']
                    . ' — sisa Rp ' . number_format(max(0, $netTotal - (float) $invoice->dibayar)),
            ]);
            AuditLogService::log('invoice_sparepart', 'bayar', "Pelunasan invoice {$invoice->no_invoice} Rp " . number_format($jumlah), $invoice);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Pembayaran dicatat.', 'data' => ['sisa' => (float) $invoice->fresh()->sisa, 'status' => $invoice->fresh()->status]]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function piutang(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();
        $today = now()->format('Y-m-d');

        $query = InvoiceSparepart::with(['pelanggan', 'kasir'])
            ->where('cabang_id', $cabangId)
            ->whereIn('status', ['Piutang', 'Sebagian']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('no_invoice', 'like', "%$s%")->orWhere('nama_pelanggan', 'like', "%$s%");
            });
        }
        $filterJatuhTempo = $request->filled('jatuh_tempo');
        if ($filterJatuhTempo === 'lewat') $query->where('jatuh_tempo', '<', $today);

        $piutangs = $query->orderBy('jatuh_tempo')->paginate(25)->appends($request->query());

        $totalPiutang = InvoiceSparepart::where('cabang_id', $cabangId)->whereIn('status', ['Piutang', 'Sebagian'])->sum('sisa');
        $jumlahJatuhTempo = InvoiceSparepart::where('cabang_id', $cabangId)->whereIn('status', ['Piutang', 'Sebagian'])
            ->where('jatuh_tempo', '<', $today)->count();

        // Limit piutang per pelanggan
        $pelangganLimits = PelangganGrosir::where(function ($q) use ($cabangId) {
            $q->where('cabang_id', $cabangId)->orWhereNull('cabang_id');
        })->where('limit_piutang', '>', 0)->get()->map(function ($p) use ($cabangId) {
            $p->piutang_berjalan = (float) InvoiceSparepart::where('pelanggan_grosir_id', $p->id)
                ->whereIn('status', ['Piutang', 'Sebagian'])->sum('sisa');
            $p->persen_pakai = $p->limit_piutang > 0 ? min(100, ($p->piutang_berjalan / (float) $p->limit_piutang) * 100) : 0;
            return $p;
        });

        return view('invoice.piutang', compact('piutangs', 'totalPiutang', 'jumlahJatuhTempo', 'pelangganLimits', 'today'));
    }

    // ============================================================
    // RETUR
    // ============================================================
    public function returIndex(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();
        $returs = InvoiceRetur::with(['invoice', 'items', 'user'])
            ->where('cabang_id', $cabangId)
            ->orderByDesc('tanggal');

        if ($request->filled('search')) {
            $s = $request->search;
            $returs->where(function ($q) use ($s) {
                $q->where('no_retur', 'like', "%$s%")->orWhereHas('invoice', fn ($iq) => $iq->where('no_invoice', 'like', "%$s%"));
            });
        }
        $returs = $returs->paginate(25)->appends($request->query());

        return view('invoice.retur', compact('returs'));
    }

    public function returStore(Request $request, InvoiceSparepart $invoice)
    {
        $this->checkCabangAccess($invoice);
        if ($invoice->isVoid()) {
            return response()->json(['success' => false, 'message' => 'Invoice sudah dibatalkan.'], 422);
        }
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:invoice_sparepart_items,id',
            'items.*.qty' => 'required|integer|min:1',
            'alasan' => 'required|string|min:3|max:500',
        ]);

        // Hitung qty yang sudah diretur per item
        $sudahRetur = InvoiceReturItem::whereHas('retur', fn ($q) => $q->where('invoice_sparepart_id', $invoice->id))
            ->selectRaw('invoice_sparepart_item_id, SUM(qty) as total')
            ->groupBy('invoice_sparepart_item_id')
            ->pluck('total', 'invoice_sparepart_item_id');

        DB::beginTransaction();
        try {
            $retur = InvoiceRetur::create([
                'no_retur' => InvoiceRetur::generateNoRetur(),
                'invoice_sparepart_id' => $invoice->id,
                'user_id' => auth()->id(),
                'cabang_id' => $invoice->cabang_id,
                'tanggal' => now(),
                'total' => 0,
                'alasan' => $validated['alasan'],
            ]);

            $totalReturBaru = 0;
            foreach ($validated['items'] as $r) {
                $item = InvoiceSparepartItem::find($r['item_id']);
                if ($item->invoice_sparepart_id != $invoice->id) continue;
                $sudah = (int) ($sudahRetur[$item->id] ?? 0);
                $bisa = $item->qty - $sudah;
                $qty = min((int) $r['qty'], $bisa);
                if ($qty <= 0) continue;

                $hargaNet = (float) $item->subtotal / max(1, (int) $item->qty);
                $subtotalRetur = $qty * $hargaNet;

                InvoiceReturItem::create([
                    'invoice_retur_id' => $retur->id,
                    'invoice_sparepart_item_id' => $item->id,
                    'stok_id' => $item->stok_id,
                    'nama' => $item->nama,
                    'qty' => $qty,
                    'harga_satuan' => $hargaNet,
                    'subtotal' => $subtotalRetur,
                ]);

                // Stok kembali
                if ($item->stok) {
                    $item->stok->increment('stok', $qty);
                    SparepartMovementService::record($item->stok, 'masuk', 'retur_invoice', $qty, [
                        'referensi' => $retur->no_retur,
                        'cabang_id' => $invoice->sumber_cabang_id,
                        'catatan' => 'Retur invoice: ' . $validated['alasan'],
                    ]);
                }
                $totalReturBaru += $subtotalRetur;
            }

            if ($totalReturBaru <= 0) {
                throw new \Exception('Tidak ada item valid untuk diretur.');
            }

            $retur->update(['total' => $totalReturBaru]);

            // Update total retur, sisa, dan status invoice
            $totalReturAll = (float) $invoice->total_retur + $totalReturBaru;
            $netTotal = max(0, (float) $invoice->total - $totalReturAll);
            $dibayar = (float) $invoice->dibayar;
            $sisa = max(0, $netTotal - $dibayar);
            $status = $sisa <= 0 ? 'Lunas' : ($dibayar > 0 ? 'Sebagian' : 'Piutang');

            // Jika sudah overpaid setelah retur → kembalikan selisih via kas
            if ($dibayar > $netTotal) {
                $refund = $dibayar - $netTotal;
                $this->recordKas('keluar', $invoice->sumber_cabang_id, $refund, 'Tunai',
                    'RTN-' . $retur->no_retur, 'Refund retur ' . $retur->no_retur);
                $dibayar = $netTotal;
            }

            $invoice->update([
                'total_retur' => $totalReturAll,
                'dibayar' => $dibayar,
                'sisa' => max(0, $netTotal - $dibayar),
                'status' => $status,
                'updated_by' => auth()->id(),
            ]);

            InvoiceSparepartLog::create([
                'invoice_sparepart_id' => $invoice->id,
                'user_id' => auth()->id(),
                'aksi' => 'retur',
                'deskripsi' => "Retur {$retur->no_retur} — Rp " . number_format($totalReturBaru) . '. Alasan: ' . $validated['alasan'],
            ]);
            AuditLogService::log('invoice_sparepart', 'retur', "Retur invoice {$invoice->no_invoice} — Rp " . number_format($totalReturBaru), $invoice);

            DB::commit();
            return response()->json(['success' => true, 'message' => "Retur {$retur->no_retur} berhasil. Stok dikembalikan."]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal retur: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // CETAK: PDF A4 & THERMAL 58/80
    // ============================================================
    private function buildInvoiceData(InvoiceSparepart $invoice): array
    {
        $invoice->load(['items', 'payments', 'kasir', 'cabang', 'sumberCabang', 'pelanggan']);
        $cabangId = $invoice->cabang_id ?? 1;
        return [
            'invoice' => $invoice,
            'settings' => [
                'nama_toko' => Setting::get("nama_toko_{$cabangId}") ?? Setting::get('nama_toko') ?? ($invoice->cabang?->nama ?? 'FIXPRO'),
                'alamat' => Setting::get("alamat_{$cabangId}") ?? Setting::get('alamat') ?? '',
                'telp' => Setting::get("telp_{$cabangId}") ?? Setting::get('telp') ?? '',
                'tagline' => Setting::get("tagline_{$cabangId}") ?? Setting::get('tagline') ?? 'SMARTPHONE SERVICE CENTER',
            ],
        ];
    }

    public function pdf(InvoiceSparepart $invoice)
    {
        $this->checkCabangAccess($invoice);
        $data = $this->buildInvoiceData($invoice);
        $html = view('invoice.pdf-a4', $data)->render();

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, 'Invoice_' . $invoice->no_invoice . '.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function thermal(InvoiceSparepart $invoice, string $size = '80')
    {
        $this->checkCabangAccess($invoice);
        $size = in_array($size, ['58', '80']) ? $size : '80';
        $data = $this->buildInvoiceData($invoice);
        $data['thermal_width'] = $size;
        return view('invoice.thermal', $data);
    }

    // ============================================================
    // WHATSAPP: kirim invoice / tagihan / pengingat
    // ============================================================
    private function buildWaMessage(InvoiceSparepart $invoice): string
    {
        $cabangId = $invoice->cabang_id ?? 1;
        $namaToko = Setting::get("nama_toko_{$cabangId}") ?? Setting::get('nama_toko') ?? 'FIXPRO';
        $telp = Setting::get("telp_{$cabangId}") ?? Setting::get('telp') ?? '';

        $msg = "*INVOICE SPAREPART — " . $namaToko . "*\n";
        $msg .= "━━━━━━━━━━━━━━━━━\n";
        $msg .= "No. Invoice: *" . $invoice->no_invoice . "*\n";
        $msg .= "Tanggal: " . $invoice->tanggal->format('d/m/Y H:i') . "\n";
        $msg .= "Pelanggan: " . ($invoice->nama_pelanggan ?? 'Umum') . " (" . $invoice->tipe_pelanggan . ")\n";
        $msg .= "Kasir: " . ($invoice->kasir?->name ?? '-') . "\n\n";
        $msg .= "🧾 *Rincian*\n";
        foreach ($invoice->items as $it) {
            $msg .= "• " . $it->nama . "\n   " . $it->qty . " x Rp " . number_format($it->harga_satuan, 0, ',', '.')
                . ($it->diskon > 0 ? " (-Rp " . number_format($it->diskon, 0, ',', '.') . ")" : "")
                . " = Rp " . number_format($it->subtotal, 0, ',', '.') . "\n";
        }
        $msg .= "\nSubtotal: Rp " . number_format($invoice->subtotal + $invoice->diskon_total, 0, ',', '.') . "\n";
        if ($invoice->diskon_total > 0) $msg .= "Diskon: -Rp " . number_format($invoice->diskon_total, 0, ',', '.') . "\n";
        if ($invoice->total_retur > 0) $msg .= "Retur: -Rp " . number_format($invoice->total_retur, 0, ',', '.') . "\n";
        $msg .= "*TOTAL: Rp " . number_format($invoice->total, 0, ',', '.') . "*\n";
        $msg .= "Dibayar: Rp " . number_format($invoice->dibayar, 0, ',', '.') . "\n";
        if ((float) $invoice->sisa > 0) {
            $msg .= "🔴 *SISA (PIUTANG): Rp " . number_format($invoice->sisa, 0, ',', '.') . "*\n";
            if ($invoice->jatuh_tempo) $msg .= "Jatuh tempo: " . $invoice->jatuh_tempo->format('d/m/Y') . "\n";
        } else {
            $msg .= "✅ STATUS: LUNAS\n";
        }
        $msg .= "\n📎 Invoice PDF terlampir.\n";
        if ($telp) $msg .= "Info: " . $telp . "\n";
        $msg .= "\nTerima kasih! 🙏";
        return $msg;
    }

    public function wa(Request $request, InvoiceSparepart $invoice)
    {
        $this->checkCabangAccess($invoice);

        $phone = preg_replace('/[^0-9]/', '', $invoice->no_wa ?? '');
        if (empty($phone)) {
            return response()->json(['success' => false, 'message' => 'Nomor WhatsApp pelanggan tidak ada di invoice ini.']);
        }
        if (str_starts_with($phone, '0')) $phone = '62' . substr($phone, 1);
        elseif (!str_starts_with($phone, '62')) $phone = '62' . $phone;

        $waApiKey = Setting::get('wa_api_key_' . ($invoice->cabang_id ?? 1)) ?? Setting::get('wa_api_key');
        $message = $this->buildWaMessage($invoice);

        // Generate PDF invoice untuk lampiran
        try {
            $data = $this->buildInvoiceData($invoice);
            $html = view('invoice.pdf-a4', $data)->render();
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $filename = 'Invoice_' . $invoice->no_invoice . '.pdf';
            $dir = public_path('storage/invoice-pdf');
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            file_put_contents($dir . '/' . $filename, $dompdf->output());
            $pdfUrl = rtrim(config('app.url'), '/') . '/storage/invoice-pdf/' . $filename;
        } catch (\Exception $e) {
            Log::error('Gagal generate invoice PDF: ' . $e->getMessage());
            $pdfUrl = null;
        }

        if (empty($waApiKey)) {
            return response()->json([
                'success' => false, 'manual_mode' => true,
                'message' => 'API WhatsApp (Fonnte) belum dikonfigurasi — gunakan tombol Buka WhatsApp Manual.',
            ]);
        }

        try {
            $payload = [
                'target' => $phone,
                'message' => $message,
            ];
            if ($pdfUrl) {
                $payload['document'] = $pdfUrl;
                $payload['filename'] = 'Invoice_' . $invoice->no_invoice . '.pdf';
            }
            $resp = Http::withHeaders(['Authorization' => $waApiKey, 'Content-Type' => 'application/json'])
                ->timeout(30)->post('https://api.fonnte.com/send', $payload);
            $body = $resp->json();

            if ($resp->successful() && ($body['status'] ?? false) === true) {
                AuditLogService::log('whatsapp', 'send', "Kirim invoice {$invoice->no_invoice} ke {$phone}");
                return response()->json(['success' => true, 'message' => "Invoice {$invoice->no_invoice} terkirim ke WhatsApp pelanggan."]);
            }
            $reason = $body['reason'] ?? 'Unknown error';
            return response()->json(['success' => false, 'manual_mode' => true, 'message' => 'Gagal kirim WA: ' . $reason]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'manual_mode' => true, 'message' => 'Koneksi API WA gagal: ' . $e->getMessage()]);
        }
    }
}
