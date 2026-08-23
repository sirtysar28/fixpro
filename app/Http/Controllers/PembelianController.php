<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\PembelianPayment;
use App\Models\PembelianReturn;
use App\Models\Stok;
use App\Models\Kas;
use App\Services\AuditLogService;
use App\Services\SparepartMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembelianController extends Controller
{
    /**
     * Modul Pembelian Supplier — Final
     *
     * Alur: Supplier → Pembelian → Item Barang → Total → Pembayaran → Hutang → Stok → Nota → Riwayat
     * Retur: Pembelian → Retur → Stok Berkurang → Nilai Pembelian Berkurang → Hutang Disesuaikan
     */

    public function index(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();

        // ===== Filter & Pencarian (no. transaksi, supplier, produk, tanggal, status, jatuh tempo) =====
        $query = Pembelian::with(['cabang', 'user', 'editor']);
        if ($cabangId !== null) {
            $query->where('cabang_id', $cabangId);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('kode', 'like', "%$s%")
                  ->orWhere('supplier_nama', 'like', "%$s%")
                  ->orWhere('supplier_kontak', 'like', "%$s%")
                  ->orWhere('items', 'like', "%$s%"); // cari nama produk di JSON items
            });
        }
        if ($request->filled('status')) {
            $st = $request->status;
            // 'Hutang' mencakup status lama 'Belum Dibayar' & baru 'Hutang'
            if ($st === 'Hutang') {
                $query->whereIn('status', ['Hutang', 'Belum Dibayar']);
            } else {
                $query->where('status', $st);
            }
        }
        if ($request->filled('status_transaksi')) {
            $query->where('status_transaksi', $request->status_transaksi);
        }
        if ($request->filled('dari')) {
            $query->whereDate('tanggal', '>=', $request->dari);
        }
        if ($request->filled('sampai')) {
            $query->whereDate('tanggal', '<=', $request->sampai);
        }
        if ($request->filled('jatuh_tempo')) {
            if ($request->jatuh_tempo === 'lewat') {
                $query->where('sisa', '>', 0)->whereNotNull('tanggal_jatuh_tempo')
                      ->where('tanggal_jatuh_tempo', '<', now()->format('Y-m-d'));
            } elseif ($request->jatuh_tempo === '7hari') {
                $query->where('sisa', '>', 0)->whereNotNull('tanggal_jatuh_tempo')
                      ->whereBetween('tanggal_jatuh_tempo', [now()->format('Y-m-d'), now()->addDays(7)->format('Y-m-d')]);
            }
        }

        $items = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        // ===== Dashboard pembelian =====
        $base = Pembelian::query();
        if ($cabangId !== null) $base->where('cabang_id', $cabangId);
        $base->where('status_transaksi', '!=', 'Draft')->where('status', '!=', 'Dibatalkan');

        $totalPembelian   = (clone $base)->sum('total');
        $totalTransaksi   = (clone $base)->count();
        $totalHutang      = (clone $base)->where('sisa', '>', 0)->sum('sisa');
        $pembelianBulanIni = (clone $base)->whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year)->sum('total');

        // Pembayaran hutang bulan ini (dari riwayat pembayaran)
        $payBase = PembelianPayment::query();
        if ($cabangId !== null) $payBase->where('cabang_id', $cabangId);
        $pembayaranBulanIni = (clone $payBase)
            ->whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year)
            ->sum('jumlah');

        // Hutang jatuh tempo (sudah lewat atau ≤ 7 hari)
        $jatuhTempo = (clone $base)->where('sisa', '>', 0)
            ->whereNotNull('tanggal_jatuh_tempo')
            ->where('tanggal_jatuh_tempo', '<=', now()->addDays(7)->format('Y-m-d'))
            ->orderBy('tanggal_jatuh_tempo', 'asc')
            ->get();

        // Supplier paling sering dibeli
        $topSuppliers = (clone $base)
            ->select('supplier_nama', DB::raw('COUNT(*) as jumlah_transaksi'), DB::raw('SUM(total) as total_nilai'))
            ->groupBy('supplier_nama')
            ->orderByDesc('jumlah_transaksi')
            ->limit(5)
            ->get();

        // Produk paling banyak dibeli (agregasi dari JSON items)
        $topProducts = $this->topProducts((clone $base)->orderByDesc('id')->limit(500)->get());

        // Grafik pembelian & pembayaran hutang 12 bulan terakhir
        $grafikPembelian = (clone $base)
            ->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as bulan, SUM(total) as nilai")
            ->where('tanggal', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('bulan')->orderBy('bulan')->pluck('nilai', 'bulan');
        $grafikPembayaran = (clone $payBase)
            ->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as bulan, SUM(jumlah) as nilai")
            ->where('tanggal', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('bulan')->orderBy('bulan')->pluck('nilai', 'bulan');

        $labels12 = [];
        $dataPembelian = [];
        $dataPembayaran = [];
        for ($i = 11; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $labels12[] = now()->subMonths($i)->translatedFormat('M y');
            $dataPembelian[] = (float) ($grafikPembelian[$key] ?? 0);
            $dataPembayaran[] = (float) ($grafikPembayaran[$key] ?? 0);
        }

        return view('pembelian.index', compact(
            'items', 'totalPembelian', 'totalHutang', 'totalTransaksi',
            'pembelianBulanIni', 'pembayaranBulanIni', 'jatuhTempo',
            'topSuppliers', 'topProducts', 'labels12', 'dataPembelian', 'dataPembayaran'
        ));
    }

    /**
     * Halaman Hutang Supplier: daftar hutang, jatuh tempo, riwayat pembayaran.
     */
    public function hutang(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();

        $query = Pembelian::with(['cabang', 'payments.user'])
            ->whereIn('status', ['Hutang', 'Belum Dibayar', 'Sebagian'])
            ->where('sisa', '>', 0)
            ->where('status_transaksi', '!=', 'Draft');
        if ($cabangId !== null) $query->where('cabang_id', $cabangId);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('kode', 'like', "%$s%")->orWhere('supplier_nama', 'like', "%$s%");
            });
        }
        if ($request->filled('kategori_jatuh_tempo')) {
            if ($request->kategori_jatuh_tempo === 'lewat') {
                $query->whereNotNull('tanggal_jatuh_tempo')->where('tanggal_jatuh_tempo', '<', now()->format('Y-m-d'));
            } elseif ($request->kategori_jatuh_tempo === '7hari') {
                $query->whereNotNull('tanggal_jatuh_tempo')
                      ->whereBetween('tanggal_jatuh_tempo', [now()->format('Y-m-d'), now()->addDays(7)->format('Y-m-d')]);
            } elseif ($request->kategori_jatuh_tempo === 'belum') {
                $query->whereNull('tanggal_jatuh_tempo');
            }
        }

        $hutangs = $query->orderBy('tanggal_jatuh_tempo', 'asc')->orderBy('tanggal', 'asc')->paginate(20)->withQueryString();

        // Riwayat pembayaran hutang terbaru
        $riwayatBase = PembelianPayment::with(['pembelian:id,kode,supplier_nama', 'user']);
        if ($cabangId !== null) $riwayatBase->where('cabang_id', $cabangId);
        $riwayatPembayaran = (clone $riwayatBase)->orderByDesc('tanggal')->orderByDesc('id')->limit(15)->get();

        // Riwayat retur terbaru
        $returBase = PembelianReturn::with(['pembelian:id,kode,supplier_nama', 'user']);
        if ($cabangId !== null) $returBase->where('cabang_id', $cabangId);
        $riwayatRetur = (clone $returBase)->orderByDesc('tanggal')->orderByDesc('id')->limit(15)->get();

        $totalHutang = (clone $query)->sum('sisa');

        return view('pembelian.hutang', compact('hutangs', 'riwayatPembayaran', 'riwayatRetur', 'totalHutang'));
    }

    public function create()
    {
        $cabangId = auth()->user()->getActiveCabangId();

        // Stok sparepart tidak boleh campur antar toko:
        // Super Admin mode "Semua Cabang" wajib pilih toko dulu
        if ($cabangId === null) {
            return view('stok.pilih-cabang', ['redirectTo' => route('pembelian.create')]);
        }

        $stoks = Stok::where('cabang_id', $cabangId)->orderBy('nama')->get();

        // Saran supplier dari pembelian sebelumnya (datalist)
        $suppliers = Pembelian::query();
        if ($cabangId !== null) $suppliers->where('cabang_id', $cabangId);
        $suppliers = $suppliers->select('supplier_nama', 'supplier_kontak', 'supplier_alamat')
            ->distinct()->orderBy('supplier_nama')->get()->unique('supplier_nama')->take(50);

        // Nomor pembelian berikutnya (preview otomatis)
        $kodePreview = Pembelian::generateKode();

        return view('pembelian.create', compact('stoks', 'suppliers', 'kodePreview'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_nama'      => 'required|string|max:255',
            'supplier_kontak'    => 'nullable|string|max:50',
            'supplier_alamat'    => 'nullable|string|max:500',
            'tanggal'            => 'required|date',
            'tanggal_jatuh_tempo'=> 'nullable|date|after_or_equal:tanggal',
            'metode_bayar'       => 'required|in:Cash,Transfer,QRIS',
            'status_transaksi'   => 'nullable|in:Draft,Diproses,Selesai',
            'diskon_persen'      => 'nullable|numeric|min:0|max:100',
            'diskon_nominal'     => 'nullable|numeric|min:0',
            'biaya_tambahan'     => 'nullable|numeric|min:0',
            'ongkir'             => 'nullable|numeric|min:0',
            'dibayar'            => 'nullable|numeric|min:0',
            'catatan'            => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.stok_id'    => 'nullable|exists:stoks,id',
            'items.*.nama'       => 'required|string|max:255',
            'items.*.kode'       => 'nullable|string|max:100',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.harga_beli' => 'required|numeric|min:0',
            'items.*.harga_jual' => 'nullable|numeric|min:0',
            'items.*.diskon_item'=> 'nullable|numeric|min:0', // diskon per item (Rp)
        ]);

        // Validasi stok: produk existing harus milik cabang aktif
        $cabangId = auth()->user()->getActiveCabangId();
        foreach ($validated['items'] as $i => $it) {
            if (!empty($it['stok_id'])) {
                $milik = Stok::where('id', $it['stok_id'])
                    ->when($cabangId !== null, fn($q) => $q->where('cabang_id', $cabangId))
                    ->exists();
                if (!$milik) {
                    // Kompatibilitas data lama: barang tanpa cabang (NULL) hanya untuk cabang default 1
                    $milik = $cabangId === null
                        || Stok::where('id', $it['stok_id'])->whereNull('cabang_id')->exists() && (int) auth()->user()->getEffectiveCabangId() === 1;
                }
                if (!$milik) {
                    return back()->withInput()->with('error', "Produk pada baris #" . ($i + 1) . " tidak ditemukan di cabang Anda.");
                }
            }
            if ((float) ($it['diskon_item'] ?? 0) > ((int) $it['qty'] * (float) $it['harga_beli'])) {
                return back()->withInput()->with('error', "Diskon item pada baris #" . ($i + 1) . " melebihi subtotal baris.");
            }
        }

        DB::beginTransaction();
        try {
            // ===== Perhitungan: subtotal → diskon transaksi → biaya tambahan + ongkir → total =====
            $itemsData = [];
            $subtotal = 0;
            foreach ($validated['items'] as $it) {
                $qty    = (int) $it['qty'];
                $beli   = (float) $it['harga_beli'];
                $jual   = (float) ($it['harga_jual'] ?? 0);
                $dItem  = (float) ($it['diskon_item'] ?? 0); // diskon per item (Rp)
                $sub    = max(0, $qty * $beli - $dItem);
                $subtotal += $sub;

                $itemsData[] = [
                    'stok_id'     => $it['stok_id'] ?? null,
                    'nama'        => $it['nama'],
                    'kode'        => $it['kode'] ?? null,
                    'qty'         => $qty,
                    'harga_beli'  => $beli,
                    'harga_jual'  => $jual,
                    'diskon_item' => $dItem,
                    'subtotal'    => $sub,
                ];
            }

            $diskonPersen   = (float) ($validated['diskon_persen'] ?? 0);
            $diskonNominal  = (float) ($validated['diskon_nominal'] ?? 0);
            $biayaTambahan  = (float) ($validated['biaya_tambahan'] ?? 0);
            $ongkir         = (float) ($validated['ongkir'] ?? 0);
            $diskonHitung   = ($subtotal * $diskonPersen / 100) + $diskonNominal;
            $total          = max(0, $subtotal - $diskonHitung) + $biayaTambahan + $ongkir;

            // ===== Pembayaran / hutang otomatis =====
            $statusTransaksi = $validated['status_transaksi'] ?? 'Selesai';
            $dibayar = (float) ($validated['dibayar'] ?? 0);
            if ($statusTransaksi === 'Draft') {
                $dibayar = 0; // draft belum boleh dibayar
            }
            if ($dibayar > $total) $dibayar = $total;
            $sisa = $total - $dibayar;

            if ($sisa <= 0.01) {
                $status = 'Lunas';
                $sisa = 0;
            } elseif ($dibayar > 0) {
                $status = 'Sebagian';
            } else {
                $status = 'Hutang';
            }

            $kode = Pembelian::generateKode();

            $pembelian = Pembelian::create([
                'kode'                => $kode,
                'cabang_id'           => $cabangId,
                'user_id'             => auth()->id(),
                'supplier_nama'       => $validated['supplier_nama'],
                'supplier_kontak'     => $validated['supplier_kontak'] ?? null,
                'supplier_alamat'     => $validated['supplier_alamat'] ?? null,
                'tanggal'             => $validated['tanggal'],
                'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'] ?? null,
                'subtotal'            => $subtotal,
                'diskon_persen'       => $diskonPersen,
                'diskon_nominal'      => $diskonNominal,
                'biaya_tambahan'      => $biayaTambahan,
                'ongkir'              => $ongkir,
                'total'               => $total,
                'total_retur'         => 0,
                'dibayar'             => $dibayar,
                'sisa'                => $sisa,
                'status'              => $status,
                'status_transaksi'    => $statusTransaksi,
                'metode_bayar'        => $validated['metode_bayar'],
                'items'               => $itemsData,
                'catatan'             => $validated['catatan'] ?? null,
            ]);

            // ===== Update stok otomatis (Draft belum menambah stok) =====
            if ($statusTransaksi !== 'Draft') {
                $this->applyStok($pembelian, $itemsData, $cabangId, $kode, $validated);

                // Riwayat pembayaran awal + kas keluar
                if ($dibayar > 0) {
                    PembelianPayment::create([
                        'pembelian_id' => $pembelian->id,
                        'cabang_id'    => $cabangId,
                        'user_id'      => auth()->id(),
                        'tanggal'      => $validated['tanggal'],
                        'jumlah'       => $dibayar,
                        'metode'       => $validated['metode_bayar'],
                        'ref_kode'     => 'AWAL-' . $kode,
                        'catatan'      => 'Pembayaran saat pembelian',
                    ]);
                    $this->recordKas($cabangId, 'keluar', $dibayar, $validated['metode_bayar'], $kode, "Pembelian {$kode} dari {$validated['supplier_nama']}");
                }
            }

            DB::commit();

            AuditLogService::log('pembelian', 'create', "Pembelian {$kode} dari {$validated['supplier_nama']} [{$statusTransaksi}], Total: Rp " . number_format($total) . ($sisa > 0 ? " (Hutang Rp " . number_format($sisa) . ")" : ''), $pembelian);

            $msg = $statusTransaksi === 'Draft'
                ? "Draft pembelian {$kode} tersimpan. Stok belum berubah — klik \"Proses\" saat barang datang."
                : "Pembelian {$kode} berhasil! Stok bertambah otomatis." . ($sisa > 0 ? " Sisa hutang: Rp " . number_format($sisa) . '.' : '');

            return redirect()->route('pembelian.show', $pembelian)->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan pembelian: ' . $e->getMessage());
        }
    }

    public function show(Pembelian $pembelian)
    {
        $this->checkCabangAccess($pembelian);
        $pembelian->load(['cabang', 'user', 'editor', 'payments.user', 'returns.user']);

        // Sisa qty tiap item yang masih bisa diretur
        $returable = [];
        foreach ($pembelian->items ?? [] as $it) {
            if (!empty($it['stok_id'])) {
                $sudahRetur = $pembelian->qtyRetur($it['stok_id']);
                if ($it['qty'] - $sudahRetur > 0) {
                    $returable[] = [
                        'stok_id'    => $it['stok_id'],
                        'nama'       => $it['nama'],
                        'harga_beli' => $it['harga_beli'],
                        'sisa_qty'   => $it['qty'] - $sudahRetur,
                    ];
                }
            }
        }

        return view('pembelian.show', compact('pembelian', 'returable'));
    }

    /**
     * Nota pembelian (cetak): logo & identitas toko, supplier, daftar barang,
     * total, dibayar, sisa hutang, status, tanda tangan.
     */
    public function nota(Pembelian $pembelian)
    {
        $this->checkCabangAccess($pembelian);
        $cabang = $pembelian->cabang;
        $cabangId = $cabang?->id ?? 1;

        $settings = [
            'nama_toko' => \App\Models\Setting::get("nama_toko_{$cabangId}") ?? \App\Models\Setting::get('nama_toko') ?? ($cabang?->nama ?? 'FIXPRO'),
            'alamat'    => \App\Models\Setting::get("alamat_{$cabangId}") ?? \App\Models\Setting::get('alamat') ?? '',
            'telp'      => \App\Models\Setting::get("telp_{$cabangId}") ?? \App\Models\Setting::get('telp') ?? '',
            'logo'      => \App\Models\Setting::get("logo_{$cabangId}") ?? \App\Models\Setting::get('logo'),
        ];

        return view('pembelian.nota', compact('pembelian', 'cabang', 'settings'));
    }

    /**
     * Edit pembelian:
     * - Draft → edit penuh (barang & perhitungan), stok belum diterapkan
     * - Diproses/Selesai → edit data header saja (supplier, tanggal, catatan, dst.)
     */
    public function edit(Pembelian $pembelian)
    {
        $this->checkCabangAccess($pembelian);

        if ($pembelian->isDibatalkan()) {
            return back()->with('error', 'Pembelian yang dibatalkan tidak bisa diedit.');
        }

        $cabangId = auth()->user()->getActiveCabangId();

        // Stok sparepart tidak boleh campur antar toko:
        // Super Admin mode "Semua Cabang" wajib pilih toko dulu
        if ($cabangId === null) {
            return view('stok.pilih-cabang', ['redirectTo' => route('pembelian.edit', $pembelian)]);
        }

        $stoks = Stok::where('cabang_id', $cabangId)->orderBy('nama')->get();

        return view('pembelian.edit', compact('pembelian', 'stoks'));
    }

    public function update(Request $request, Pembelian $pembelian)
    {
        $this->checkCabangAccess($pembelian);

        if ($pembelian->isDibatalkan()) {
            return back()->with('error', 'Pembelian yang dibatalkan tidak bisa diedit.');
        }

        // ===== Edit header (selalu boleh) =====
        $validated = $request->validate([
            'supplier_nama'      => 'required|string|max:255',
            'supplier_kontak'    => 'nullable|string|max:50',
            'supplier_alamat'    => 'nullable|string|max:500',
            'tanggal'            => 'required|date',
            'tanggal_jatuh_tempo'=> 'nullable|date|after_or_equal:tanggal',
            'metode_bayar'       => 'required|in:Cash,Transfer,QRIS',
            'catatan'            => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $old = $pembelian->only(array_keys($validated));

            if ($pembelian->isDraft()) {
                // ===== Draft: edit penuh termasuk item & perhitungan =====
                $full = $request->validate([
                    'diskon_persen'   => 'nullable|numeric|min:0|max:100',
                    'diskon_nominal'  => 'nullable|numeric|min:0',
                    'biaya_tambahan'  => 'nullable|numeric|min:0',
                    'ongkir'          => 'nullable|numeric|min:0',
                    'dibayar'         => 'nullable|numeric|min:0',
                    'items'           => 'required|array|min:1',
                    'items.*.stok_id' => 'nullable|exists:stoks,id',
                    'items.*.nama'    => 'required|string|max:255',
                    'items.*.kode'    => 'nullable|string|max:100',
                    'items.*.qty'     => 'required|integer|min:1',
                    'items.*.harga_beli'  => 'required|numeric|min:0',
                    'items.*.harga_jual'  => 'nullable|numeric|min:0',
                    'items.*.diskon_item' => 'nullable|numeric|min:0',
                ]);

                $itemsData = [];
                $subtotal = 0;
                foreach ($full['items'] as $it) {
                    $qty  = (int) $it['qty'];
                    $beli = (float) $it['harga_beli'];
                    $dIt  = (float) ($it['diskon_item'] ?? 0);
                    $sub  = max(0, $qty * $beli - $dIt);
                    $subtotal += $sub;
                    $itemsData[] = [
                        'stok_id'     => $it['stok_id'] ?? null,
                        'nama'        => $it['nama'],
                        'kode'        => $it['kode'] ?? null,
                        'qty'         => $qty,
                        'harga_beli'  => $beli,
                        'harga_jual'  => (float) ($it['harga_jual'] ?? 0),
                        'diskon_item' => $dIt,
                        'subtotal'    => $sub,
                    ];
                }

                $diskonPersen  = (float) ($full['diskon_persen'] ?? 0);
                $diskonNominal = (float) ($full['diskon_nominal'] ?? 0);
                $biayaTambahan = (float) ($full['biaya_tambahan'] ?? 0);
                $ongkir        = (float) ($full['ongkir'] ?? 0);
                $total = max(0, $subtotal - ($subtotal * $diskonPersen / 100) - $diskonNominal) + $biayaTambahan + $ongkir;

                $validated = array_merge($validated, [
                    'subtotal'       => $subtotal,
                    'diskon_persen'  => $diskonPersen,
                    'diskon_nominal' => $diskonNominal,
                    'biaya_tambahan' => $biayaTambahan,
                    'ongkir'         => $ongkir,
                    'total'          => $total,
                    'dibayar'        => 0,
                    'sisa'           => $total,
                    'status'         => 'Hutang',
                    'items'          => $itemsData,
                ]);
            }

            $validated['diedit_oleh'] = auth()->id();
            $validated['diedit_pada'] = now();

            $pembelian->update($validated);

            DB::commit();

            AuditLogService::log('pembelian', 'update', "Edit pembelian {$pembelian->kode}" . ($pembelian->isDraft() ? ' (draft, item diubah)' : ' (data header)'), $pembelian, $old, $validated);

            return redirect()->route('pembelian.show', $pembelian)->with('success', "Pembelian {$pembelian->kode} diperbarui.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    /**
     * Proses Draft → Diproses: barang datang, stok bertambah otomatis.
     */
    public function proses(Request $request, Pembelian $pembelian)
    {
        $this->checkCabangAccess($pembelian);

        if (!$pembelian->isDraft()) {
            return back()->with('error', 'Hanya pembelian berstatus Draft yang bisa diproses.');
        }

        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'dibayar' => 'nullable|numeric|min:0',
            ]);

            $itemsData = $pembelian->items ?? [];
            $this->applyStok($pembelian, $itemsData, $pembelian->cabang_id, $pembelian->kode, [
                'metode_bayar' => $pembelian->metode_bayar,
                'supplier_nama'=> $pembelian->supplier_nama,
                'tanggal'      => $pembelian->tanggal->format('Y-m-d'),
            ]);

            $dibayar = min((float) ($validated['dibayar'] ?? 0), (float) $pembelian->total);
            $sisa = max(0, (float) $pembelian->total - $dibayar);
            $status = $sisa <= 0.01 ? 'Lunas' : ($dibayar > 0 ? 'Sebagian' : 'Hutang');

            $pembelian->update([
                'status_transaksi' => 'Diproses',
                'dibayar'          => $dibayar,
                'sisa'             => $sisa,
                'status'           => $status,
            ]);

            if ($dibayar > 0) {
                PembelianPayment::create([
                    'pembelian_id' => $pembelian->id,
                    'cabang_id'    => $pembelian->cabang_id,
                    'user_id'      => auth()->id(),
                    'tanggal'      => now()->format('Y-m-d'),
                    'jumlah'       => $dibayar,
                    'metode'       => $pembelian->metode_bayar,
                    'ref_kode'     => 'AWAL-' . $pembelian->kode,
                    'catatan'      => 'Pembayaran saat proses pembelian',
                ]);
                $this->recordKas($pembelian->cabang_id, 'keluar', $dibayar, $pembelian->metode_bayar, $pembelian->kode, "Pembelian {$pembelian->kode} dari {$pembelian->supplier_nama}");
            }

            DB::commit();

            AuditLogService::log('pembelian', 'proses', "Proses draft {$pembelian->kode}: stok masuk otomatis", $pembelian);

            return back()->with('success', "Pembelian {$pembelian->kode} diproses. Stok bertambah otomatis.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses: ' . $e->getMessage());
        }
    }

    /**
     * Tandai transaksi selesai.
     */
    public function selesaikan(Pembelian $pembelian)
    {
        $this->checkCabangAccess($pembelian);

        if ($pembelian->isDibatalkan() || $pembelian->isDraft()) {
            return back()->with('error', 'Transaksi ini tidak bisa diselesaikan.');
        }

        $pembelian->update(['status_transaksi' => 'Selesai']);
        AuditLogService::log('pembelian', 'selesai', "Tandai selesai {$pembelian->kode}", $pembelian);

        return back()->with('success', "Pembelian {$pembelian->kode} ditandai selesai.");
    }

    /**
     * Bayar hutang supplier: bayar sebagian / lunas, dengan metode & tanggal pembayaran.
     */
    public function bayarHutang(Request $request, Pembelian $pembelian)
    {
        $this->checkCabangAccess($pembelian);

        $request->validate([
            'jumlah' => 'required|numeric|min:1|max:' . $pembelian->sisaHutang(),
            'metode' => 'required|in:Cash,Transfer,QRIS',
            'tanggal_bayar' => 'nullable|date',
        ]);

        if ($pembelian->isDibatalkan() || $pembelian->isDraft()) {
            return back()->with('error', 'Transaksi ini tidak bisa dibayar.');
        }
        if ($pembelian->sisaHutang() <= 0) {
            return back()->with('error', 'Tidak ada sisa hutang.');
        }

        DB::beginTransaction();
        try {
            $jumlah = (float) $request->jumlah;
            $tanggal = $request->tanggal_bayar ?? now()->format('Y-m-d');

            // Riwayat pembayaran
            PembelianPayment::create([
                'pembelian_id' => $pembelian->id,
                'cabang_id'    => $pembelian->cabang_id,
                'user_id'      => auth()->id(), // siapa yang menerima pembayaran
                'tanggal'      => $tanggal,
                'jumlah'       => $jumlah,
                'metode'       => $request->metode,
                'ref_kode'     => 'BAYAR-' . $pembelian->kode . '-' . str_pad((string) ($pembelian->payments()->count() + 1), 2, '0', STR_PAD_LEFT),
                'catatan'      => $request->catatan,
            ]);

            $pembelian->increment('dibayar', $jumlah);
            $pembelian->decrement('sisa', $jumlah);
            $pembelian->refresh();

            if ($pembelian->sisaHutang() <= 0.01) {
                $pembelian->update(['status' => 'Lunas', 'sisa' => 0]);
            } else {
                $pembelian->update(['status' => 'Sebagian']);
            }

            $this->recordKas($pembelian->cabang_id, 'keluar', $jumlah, $request->metode, 'BAYAR-' . $pembelian->kode, "Pembayaran hutang {$pembelian->kode} ke {$pembelian->supplier_nama}");

            DB::commit();

            AuditLogService::log('pembelian', 'bayar', "Bayar hutang {$pembelian->kode}: Rp " . number_format($jumlah) . " ({$request->metode}). Sisa: Rp " . number_format($pembelian->fresh()->sisaHutang()), $pembelian);

            return back()->with('success', "Pembayaran Rp " . number_format($jumlah) . " berhasil. Sisa hutang: Rp " . number_format($pembelian->fresh()->sisaHutang()) . '.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    /**
     * Retur pembelian:
     * - stok otomatis berkurang
     * - nilai pembelian berkurang (total_retur)
     * - hutang supplier disesuaikan (kelebihan yang sudah dibayar → refund kas)
     */
    public function retur(Request $request, Pembelian $pembelian)
    {
        $this->checkCabangAccess($pembelian);

        $request->validate([
            'stok_id'     => 'required',
            'qty'         => 'required|integer|min:1',
            'harga_retur' => 'nullable|numeric|min:0',
            'alasan'      => 'nullable|string|max:500',
        ]);

        if ($pembelian->isDibatalkan() || $pembelian->isDraft()) {
            return back()->with('error', 'Retur hanya untuk transaksi yang sudah diproses.');
        }

        DB::beginTransaction();
        try {
            // Cari item di pembelian ini
            $target = null;
            foreach ($pembelian->items ?? [] as $it) {
                if ((string) ($it['stok_id'] ?? '') === (string) $request->stok_id) {
                    $target = $it;
                    break;
                }
            }
            if (!$target) {
                return back()->with('error', 'Item tidak ditemukan pada pembelian ini.');
            }

            // Qty retur tidak boleh melebihi sisa qty yang bisa diretur
            $sudahRetur = $pembelian->qtyRetur((int) $request->stok_id);
            $bisaRetur  = (int) $target['qty'] - $sudahRetur;
            if ($request->qty > $bisaRetur) {
                return back()->with('error', "Qty retur melebihi sisa yang bisa diretur ({$bisaRetur}).");
            }

            $stok = Stok::where('id', $request->stok_id)
                ->when($pembelian->cabang_id, fn($q) => $q->where('cabang_id', $pembelian->cabang_id))
                ->first();
            if (!$stok) {
                return back()->with('error', 'Stok tidak ditemukan di cabang ini.');
            }
            if ($stok->stok < $request->qty) {
                return back()->with('error', "Stok {$stok->nama} saat ini hanya {$stok->stok}, tidak cukup untuk retur {$request->qty}.");
            }

            $hargaRetur = (float) ($request->harga_retur ?: $target['harga_beli']);
            $nilaiRetur = $request->qty * $hargaRetur;

            // ===== Stok berkurang =====
            $stok->decrement('stok', $request->qty);

            $kodeRetur = 'RETUR-' . $pembelian->kode . '-' . str_pad((string) ($pembelian->returns()->count() + 1), 2, '0', STR_PAD_LEFT);

            SparepartMovementService::record($stok, 'keluar', 'retur_pembelian', (int) $request->qty, [
                'referensi'      => $kodeRetur,
                'referensi_id'   => $pembelian->id,
                'referensi_model'=> $pembelian,
                'harga_satuan'   => $hargaRetur,
                'pelaku_nama'    => $pembelian->supplier_nama,
                'cabang_id'      => $pembelian->cabang_id,
                'catatan'        => $request->alasan ? 'Retur: ' . $request->alasan : 'Retur ke supplier',
            ]);

            // ===== Riwayat retur =====
            PembelianReturn::create([
                'pembelian_id' => $pembelian->id,
                'cabang_id'    => $pembelian->cabang_id,
                'user_id'      => auth()->id(),
                'kode'         => $kodeRetur,
                'stok_id'      => $stok->id,
                'nama_barang'  => $stok->nama,
                'qty'          => (int) $request->qty,
                'harga_retur'  => $hargaRetur,
                'nilai'        => $nilaiRetur,
                'alasan'       => $request->alasan,
                'tanggal'      => now()->format('Y-m-d'),
            ]);

            // ===== Nilai pembelian berkurang & hutang disesuaikan =====
            $sisaSebelum  = $pembelian->sisaHutang();             // hutang sebelum retur
            $potongHutang = min($nilaiRetur, $sisaSebelum);       // bagian yang mengurangi hutang
            $refundKas    = $nilaiRetur - $potongHutang;          // kelebihan (sudah dibayar) → refund

            $pembelian->increment('total_retur', $nilaiRetur);
            if ($refundKas > 0) {
                $pembelian->decrement('dibayar', $refundKas);      // uang kembali dari supplier
            }
            $pembelian->refresh();

            $sisaBaru  = max(0, $pembelian->totalAkhir() - (float) $pembelian->dibayar);

            $statusBaru = 'Hutang';
            if ($sisaBaru <= 0.01) {
                $statusBaru = 'Lunas';
                $sisaBaru = 0;
            } elseif ((float) $pembelian->dibayar > 0) {
                $statusBaru = 'Sebagian';
            }
            $pembelian->update(['sisa' => $sisaBaru, 'status' => $statusBaru]);

            // Refund ke kas (uang kembali dari supplier untuk bagian yang sudah dibayar)
            if ($refundKas > 0) {
                $this->recordKas($pembelian->cabang_id, 'masuk', $refundKas, 'Cash', $kodeRetur, "Refund retur {$pembelian->kode}: {$stok->nama} x{$request->qty}" . ($request->alasan ? " — {$request->alasan}" : ''));
            }

            AuditLogService::log('pembelian', 'retur', "Retur {$kodeRetur}: {$stok->nama} x{$request->qty}, nilai Rp " . number_format($nilaiRetur) . " (hutang turun Rp " . number_format($potongHutang) . ", refund kas Rp " . number_format($refundKas) . ")", $pembelian);

            DB::commit();

            return back()->with('success', "Retur {$kodeRetur} berhasil! {$stok->nama} x{$request->qty} dikembalikan. Nilai pembelian turun Rp " . number_format($nilaiRetur) . ($refundKas > 0 ? ", refund ke kas Rp " . number_format($refundKas) : '') . '.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal retur: ' . $e->getMessage());
        }
    }

    /**
     * Batalkan pembelian dengan proses yang benar:
     * - Draft → langsung dibatalkan (stok belum masuk)
     * - Diproses/Selesai → seluruh stok dikembalikan (dikurangi qty retur),
     *   seluruh pembayaran dikembalikan ke kas (refund).
     */
    public function batal(Request $request, Pembelian $pembelian)
    {
        $this->checkCabangAccess($pembelian);

        if ($pembelian->isDibatalkan()) {
            return back()->with('error', 'Sudah dibatalkan.');
        }

        $request->validate(['alasan' => 'required|string|min:3']);

        DB::beginTransaction();
        try {
            if (!$pembelian->isDraft()) {
                // Kembalikan stok (dikurangi qty yang sudah diretur)
                foreach ($pembelian->items ?? [] as $it) {
                    if (!empty($it['stok_id'])) {
                        $returQty = (int) $pembelian->returns()->where('stok_id', $it['stok_id'])->sum('qty');
                        $kembali  = max(0, (int) $it['qty'] - $returQty);
                        if ($kembali <= 0) continue;

                        $stok = Stok::find($it['stok_id']);
                        if ($stok && $stok->cabang_id == $pembelian->cabang_id) {
                            $stok->decrement('stok', $kembali);

                            SparepartMovementService::record($stok, 'keluar', 'batal_pembelian', $kembali, [
                                'referensi'      => 'BATAL-' . $pembelian->kode,
                                'referensi_id'   => $pembelian->id,
                                'referensi_model'=> $pembelian,
                                'harga_satuan'   => (float) ($it['harga_beli'] ?? 0),
                                'pelaku_nama'    => $pembelian->supplier_nama,
                                'cabang_id'      => $pembelian->cabang_id,
                                'catatan'        => 'Pembatalan pembelian: ' . $request->alasan,
                            ]);
                        }
                    }
                }

                // Seluruh pembayaran yang masih tercatat dibayar dikembalikan ke kas
                // (proses pembatalan yang benar). Pakai kolom "dibayar" saat ini —
                // bukan total riwayat — karena retur mungkin sudah pernah me-refund sebagian.
                $totalDibayar = (float) $pembelian->dibayar;
                if ($totalDibayar > 0) {
                    $this->recordKas($pembelian->cabang_id, 'masuk', $totalDibayar, $pembelian->metode_bayar, 'BATAL-' . $pembelian->kode, "Pembatalan pembelian {$pembelian->kode}: seluruh pembayaran dikembalikan ke kas");
                }
            }

            $pembelian->update([
                'status'           => 'Dibatalkan',
                'status_transaksi' => 'Dibatalkan',
                'sisa'             => 0,
                'catatan'          => ($pembelian->catatan ? $pembelian->catatan . "\n" : '') . '[BATAL] ' . $request->alasan,
            ]);

            DB::commit();

            AuditLogService::log('pembelian', 'batal', "Batalkan {$pembelian->kode}: {$pembelian->supplier_nama}. Alasan: {$request->alasan}", $pembelian);

            return back()->with('success', "Pembelian {$pembelian->kode} dibatalkan. Stok & pembayaran dikembalikan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    /**
     * Hapus permanen — HANYA untuk transaksi Dibatalkan.
     * Transaksi yang sudah memiliki pembayaran TIDAK BOLEH dihapus
     * tanpa proses pembatalan yang benar (tombol Batalkan).
     */
    public function destroy(Pembelian $pembelian)
    {
        $this->checkCabangAccess($pembelian);

        if ($pembelian->status !== 'Dibatalkan') {
            return back()->with('error', 'Transaksi harus dibatalkan dulu (agar stok & kas kembali) sebelum dihapus.');
        }
        if ($pembelian->payments()->exists()) {
            return back()->with('error', 'Transaksi sudah memiliki pembayaran. Proses pembatalan yang benar sudah mengembalikan dana — riwayat pembayaran tetap disimpan untuk audit.');
        }

        AuditLogService::log('pembelian', 'delete', "Hapus permanen {$pembelian->kode}", $pembelian);
        $pembelian->delete();
        return redirect()->route('pembelian.index')->with('success', 'Pembelian dihapus permanen.');
    }

    // ==================== HELPERS ====================

    private function checkCabangAccess(Pembelian $pembelian): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) return;

        // Admin Cabang Anak: strict ke cabang sendiri
        if ($user->isAdminCabangAnak()) {
            if ($pembelian->cabang_id != $user->cabang_id) {
                abort(403, 'Admin Cabang Anak hanya bisa mengakses data cabang Anda sendiri.');
            }
            return;
        }

        // Admin ENTERPRISE (pusat): boleh mengakses seluruh cabang dalam grupnya sendiri
        if ($user->isEnterprise() && $user->isAdmin()) {
            if (!in_array((int) $pembelian->cabang_id, $user->getAllowedCabangIds(), true)) {
                abort(403, 'Pembelian ini bukan milik grup cabang Anda.');
            }
            return;
        }

        $cabangId = $user->getActiveCabangId();
        if ($pembelian->cabang_id != $cabangId) {
            abort(403, 'Anda hanya bisa mengakses data cabang Anda sendiri.');
        }
    }

    /**
     * Terapkan stok masuk + update harga modal + kartu stok.
     * Dipakai saat store (non-draft) dan saat proses draft.
     */
    private function applyStok(Pembelian $pembelian, array $itemsData, ?int $cabangId, string $kode, array $validated): void
    {
        foreach ($itemsData as $idx => $it) {
            $stok = $this->resolveStok($it, $cabangId);
            if ($stok) {
                $stok->increment('stok', $it['qty']);
                // Perbarui harga modal (HPP) ke harga beli terbaru
                $stok->modal = $it['harga_beli'];
                // Optional: update harga jual kalau diisi & lebih tinggi dari modal
                if (!empty($it['harga_jual']) && $it['harga_jual'] >= $it['harga_beli']) {
                    $stok->jual = $it['harga_jual'];
                }
                $stok->save();
                // simpan kembali stok_id (penting untuk barang baru) ke itemsData
                $itemsData[$idx]['stok_id'] = $stok->id;
                $itemsData[$idx]['kode']    = $stok->kode;

                // Catat pergerakan stok (Kartu Stok) dengan nomor referensi transaksi
                SparepartMovementService::record($stok, 'masuk', 'pembelian', (int) $it['qty'], [
                    'referensi'      => $kode,
                    'referensi_id'   => $pembelian->id,
                    'referensi_model'=> $pembelian,
                    'harga_satuan'   => $it['harga_beli'],
                    'pelaku_nama'    => $validated['supplier_nama'] ?? $pembelian->supplier_nama,
                    'metode'         => $validated['metode_bayar'] ?? $pembelian->metode_bayar,
                    'cabang_id'      => $cabangId,
                ]);
            }
        }

        // simpan itemsData (dengan stok_id terbaru) ke record pembelian
        $pembelian->items = $itemsData;
        $pembelian->save();
    }

    /**
     * Agregasi produk paling banyak dibeli dari JSON items.
     */
    private function topProducts($pembelians)
    {
        $agg = [];
        foreach ($pembelians as $p) {
            foreach ($p->items ?? [] as $it) {
                $key = $it['kode'] ?: $it['nama'];
                if (!isset($agg[$key])) {
                    $agg[$key] = ['nama' => $it['nama'], 'kode' => $it['kode'] ?? '-', 'qty' => 0, 'nilai' => 0];
                }
                $agg[$key]['qty'] += (int) $it['qty'];
                $agg[$key]['nilai'] += (float) ($it['subtotal'] ?? 0);
            }
        }
        return collect(array_values($agg))->sortByDesc('qty')->take(5)->values();
    }

    /**
     * Cari stok existing by id, atau by kode, atau buat baru.
     */
    private function resolveStok(array $it, ?int $cabangId): ?Stok
    {
        // 1. By stok_id eksplisit
        if (!empty($it['stok_id'])) {
            $stok = Stok::where('id', $it['stok_id'])
                ->when($cabangId !== null, fn($q) => $q->where('cabang_id', $cabangId))
                ->first();
            if ($stok) return $stok;
        }

        // 2. By kode/barcode (unique per cabang)
        if (!empty($it['kode'])) {
            $stok = Stok::where(function ($q) use ($it) {
                    $q->where('kode', $it['kode'])->orWhere('barcode', $it['kode']);
                })
                ->when($cabangId !== null, fn($q) => $q->where('cabang_id', $cabangId))
                ->first();
            if ($stok) return $stok;
        }

        // 3. Buat baru (barang baru dari supplier)
        $kode = $it['kode'] ?? ('BR-' . now()->format('ymd') . '-' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT));
        $stok = Stok::create([
            'kode'      => $kode,
            'barcode'   => 'FXP' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
            'nama'      => $it['nama'],
            'kategori'  => 'Supplier',
            'stok'      => 0,
            'modal'     => $it['harga_beli'] ?? 0,
            'jual'      => $it['harga_jual'] ?? ($it['harga_beli'] ?? 0),
            'satuan'    => 'pcs',
            'min_alert' => 1,
            'cabang_id' => $cabangId,
        ]);

        // simpan id baru ke referensi item supaya retur bisa pakai
        $it['stok_id'] = $stok->id;
        return $stok;
    }

    private function recordKas(?int $cabangId, string $tipe, float $jml, string $metode, string $ref, string $ket): void
    {
        $lastKas = Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
        $lastSaldo = $lastKas ? $lastKas->saldo : 0;
        $newSaldo = $tipe === 'masuk' ? $lastSaldo + $jml : $lastSaldo - $jml;
        Kas::create([
            'tipe'      => $tipe,
            'cabang_id' => $cabangId,
            'jml'       => $jml,
            'kategori'  => $tipe === 'keluar' ? 'Pembelian Supplier' : 'Refund Pembelian',
            'ket'       => $ket,
            'metode'    => $metode,
            'ref'       => $ref,
            'waktu'     => now(),
            'saldo'     => $newSaldo,
        ]);
    }
}
