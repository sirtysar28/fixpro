<?php

namespace App\Http\Controllers;

use App\Models\PenjualanSparepart;
use App\Models\Stok;
use App\Models\Pelanggan;
use App\Models\User;
use App\Models\Cabang;
use App\Services\AuditLogService;
use App\Services\SparepartMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PenjualanSparepartController extends Controller
{
    /**
     * Pastikan data milik cabang user yang login
     */
    private function checkCabangAccess(PenjualanSparepart $penjualan): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) return;

        $cabangId = $user->getActiveCabangId();
        if ($penjualan->cabang_id != $cabangId) {
            abort(403, 'Anda hanya bisa mengakses data cabang Anda sendiri.');
        }
    }

    public function index(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();

        // Stok sparepart POS tidak boleh campur antar toko:
        // Super Admin mode "Semua Cabang" wajib pilih toko dulu
        if ($cabangId === null) {
            return view('stok.pilih-cabang', ['redirectTo' => route('penjualan-sparepart.index')]);
        }

        $query = PenjualanSparepart::with(['stok', 'pelanggan', 'user']);

        $query->where('cabang_id', $cabangId);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('kode', 'like', "%$s%")
                    ->orWhere('no_transaksi', 'like', "%$s%")
                    ->orWhereHas('stok', fn($q) => $q->where('nama', 'like', "%$s%"))
                    ->orWhereHas('pelanggan', fn($q) => $q->where('nama', 'like', "%$s%"));
            });
        }
        if ($request->filled('date')) {
            $query->whereDate('tanggal', $request->date);
        }
        if ($request->filled('metode')) {
            $query->where('metode_bayar', $request->metode);
        }

        $penjualans = $query->orderBy('created_at', 'desc')->paginate(25);

        // Laba bersih per item: total - diskon - modal (diskon ada di item pertama tiap transaksi)
        $penjualans->getCollection()->transform(function ($p) {
                $p->laba_bersih = ((float) $p->total - (float) ($p->diskon ?? 0)) - (float) ($p->modal_total ?? 0);
                return $p;
            });

        // Stats
        $today = now()->format('Y-m-d');
        $statsBase = PenjualanSparepart::where('cabang_id', $cabangId);
        $clone1 = (clone $statsBase)->where('status', '!=', 'Dibatalkan')->whereDate('tanggal', $today);
        $clone1All = (clone $clone1)->get();
        $omsetHariIni = $clone1All->sum('total');
        $diskonHariIni = $clone1All->unique('no_transaksi')->sum('diskon');
        $omsetBersihHariIni = $omsetHariIni - $diskonHariIni;
        $modalHariIni = $clone1All->sum('modal_total');
        $labaHariIni = $omsetBersihHariIni - $modalHariIni;
        $totalTransaksi = (clone $statsBase)->where('status', '!=', 'Dibatalkan')->whereDate('tanggal', $today)->count();

        $stoks = Stok::where('stok', '>', 0)
            ->where('cabang_id', $cabangId)
            ->orderBy('nama')->get();

        // Pelanggan dropdown: filter by cabang juga
        $pelanggansQuery = Pelanggan::query();
        $pelanggansQuery->where(function ($q) use ($cabangId) {
            $q->whereHas('servis', fn($sq) => $sq->where('cabang_id', $cabangId))
              ->orWhereHas('user', fn($sq) => $sq->where('cabang_id', $cabangId));
        });
        $pelanggans = $pelanggansQuery->orderBy('nama')->get();

        return view('penjualan-sparepart.index', compact(
            'penjualans', 'omsetHariIni', 'labaHariIni', 'totalTransaksi', 'stoks', 'pelanggans'
        ));
    }

    /**
     * API: Search product by barcode or code for POS (Fitur #12)
     * Mengembalikan status jelas: found / not_found / out_of_stock agar
     * client bisa memberi notifikasi yang tepat.
     */
    public function searchProduct(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $qtyDiminta = (int) $request->get('qty', 1);
        $cabangId = auth()->user()->getActiveCabangId();

        // Stok tidak boleh campur antar toko — wajib ada cabang aktif
        if ($cabangId === null) {
            return response()->json(['found' => false, 'status' => 'not_found', 'message' => 'Pilih toko/cabang terlebih dahulu']);
        }

        if ($q === '') {
            return response()->json(['found' => false, 'status' => 'not_found', 'message' => 'Kata kunci kosong']);
        }

        // Cari produk (termasuk yang stok 0) supaya bisa dibedakan
        $product = Stok::where('cabang_id', $cabangId)
            ->where(function ($query) use ($q) {
                $query->where('barcode', $q)
                    ->orWhere('kode', $q)
                    ->orWhere('nama', $q)
                    ->orWhere('nama', 'like', "%{$q}%");
            })
            ->orderByRaw("CASE WHEN barcode = ? OR kode = ? OR nama = ? THEN 0 ELSE 1 END", [$q, $q, $q])
            ->first();

        if (!$product) {
            return response()->json([
                'found'   => false,
                'status'  => 'not_found',
                'message' => 'Produk "' . $q . '" tidak ditemukan di cabang ini',
            ]);
        }

        // Produk ditemukan tetapi stok habis
        if ((int) $product->stok <= 0) {
            return response()->json([
                'found'    => true,
                'status'   => 'out_of_stock',
                'message'  => 'Stok ' . $product->nama . ' habis',
                'product'  => $this->mapProduct($product),
            ]);
        }

        // Produk ditemukan tetapi stok tidak mencukupi untuk qty diminta
        if ($qtyDiminta > 0 && (int) $product->stok < $qtyDiminta) {
            return response()->json([
                'found'    => true,
                'status'   => 'insufficient',
                'message'  => 'Stok ' . $product->nama . ' tidak mencukupi (sisa ' . $product->stok . ')',
                'product'  => $this->mapProduct($product),
            ]);
        }

        return response()->json([
            'found'   => true,
            'status'  => 'ok',
            'message' => 'Produk ditemukan',
            'product' => $this->mapProduct($product),
        ]);
    }

    /**
     * API: Real-time multi-result search untuk dropdown POS (Fitur #12)
     * GET /penjualan-sparepart/api/search-suggest?q=...
     */
    public function searchSuggest(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $cabangId = auth()->user()->getActiveCabangId();

        // Stok tidak boleh campur antar toko — wajib ada cabang aktif
        if ($cabangId === null) {
            return response()->json(['products' => [], 'message' => 'Pilih toko/cabang terlebih dahulu']);
        }

        if (mb_strlen($q) < 1) {
            return response()->json(['products' => []]);
        }

        $products = Stok::where('cabang_id', $cabangId)
            ->where(function ($query) use ($q) {
                $query->where('nama', 'like', "%{$q}%")
                    ->orWhere('kode', 'like', "%{$q}%")
                    ->orWhere('barcode', 'like', "%{$q}%")
                    ->orWhere('merk_hp', 'like', "%{$q}%");
            })
            ->orderByRaw("CASE WHEN stok > 0 THEN 0 ELSE 1 END")
            ->orderBy('nama')
            ->limit(15)
            ->get();

        return response()->json([
            'products' => $products->map(fn($p) => $this->mapProduct($p)),
        ]);
    }

    /** Mapping produk ke array JSON (menampilkan stok, modal, jual) */
    private function mapProduct(Stok $product): array
    {
        return [
            'id'          => $product->id,
            'kode'        => $product->kode,
            'barcode'     => $product->barcode,
            'sku'         => $product->kode, // kode = SKU
            'nama'        => $product->nama,
            'kategori'    => $product->kategori,
            'merk_hp'     => $product->merk_hp,
            'harga_jual'  => (float) $product->jual,
            'harga_modal' => (float) $product->modal,
            'stok'        => (int) $product->stok,
            'satuan'      => $product->satuan ?? 'pcs',
            'low_stock'   => (int) $product->stok <= (int) ($product->min_alert ?? 3),
        ];
    }

    /**
     * API: Get all products for POS grid
     */
    public function getProducts(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();
        $search = $request->get('search', '');
        $kategori = $request->get('kategori', '');

        // Stok tidak boleh campur antar toko — wajib ada cabang aktif
        if ($cabangId === null) {
            return response()->json(['products' => [], 'categories' => [], 'message' => 'Pilih toko/cabang terlebih dahulu']);
        }

        $query = Stok::where('cabang_id', $cabangId)->where('stok', '>', 0);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('merk_hp', 'like', "%{$search}%");
            });
        }
        if ($kategori) {
            $query->where('kategori', $kategori);
        }

        $products = $query->orderBy('nama')->limit(50)->get();

        $categories = Stok::where('cabang_id', $cabangId)
            ->where('stok', '>', 0)
            ->distinct()
            ->pluck('kategori')
            ->sort()
            ->values();

        return response()->json([
            'products' => $products->map(fn($p) => [
                'id' => $p->id,
                'kode' => $p->kode,
                'barcode' => $p->barcode,
                'nama' => $p->nama,
                'kategori' => $p->kategori,
                'merk_hp' => $p->merk_hp,
                'harga_jual' => (float) $p->jual,
                'harga_modal' => (float) $p->modal,
                'stok' => $p->stok,
                'satuan' => $p->satuan ?? 'pcs',
            ]),
            'categories' => $categories,
        ]);
    }

    /**
     * Store cart checkout (multi-item transaction)
     */
    public function storeCart(Request $request)
    {
        try {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.stok_id' => 'required|exists:stoks,id',
                'items.*.qty' => 'required|integer|min:1',
                'items.*.harga_satuan' => 'required|numeric|min:0',
                'metode_bayar' => 'required|in:Cash,Transfer,QRIS',
                'pelanggan_id' => 'nullable|exists:pelanggans,id',
                'nama_pelanggan' => 'nullable|string',
                'no_hp_pelanggan' => 'nullable|string',
                'catatan' => 'nullable|string',
                'diskon' => 'nullable|numeric|min:0',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . collect($e->errors())->flatten()->join(', ')
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 400);
        }

        $cabangId = auth()->user()->getActiveCabangId();
        // Super admin 'all' → fallback ke cabang miliknya supaya transaksi tidak yatim (cabang null)
        if ($cabangId === null) {
            $cabangId = auth()->user()->getEffectiveCabangId();
        }
        $noTransaksi = PenjualanSparepart::generateNoTransaksi();
        $diskon = $validated['diskon'] ?? 0;

        // Validate stock availability + kepemilikan cabang (jangan boleh jual stok toko lain)
        foreach ($validated['items'] as $item) {
            $stok = Stok::find($item['stok_id']);
            $stokMilikSendiri = $stok && (
                ($cabangId !== null && (int) ($stok->cabang_id ?? 0) === (int) $cabangId)
                || ($cabangId === null) // super admin 'all' boleh (akses penuh)
                || ($stok->cabang_id === null && (int) auth()->user()->getEffectiveCabangId() === 1)
            );
            if (!$stok || !$stokMilikSendiri) {
                return response()->json([
                    'success' => false,
                    'message' => $stok ? "Barang {$stok->nama} bukan milik cabang Anda." : "Barang tidak ditemukan."
                ], 403);
            }
            if ($stok->stok < $item['qty']) {
                return response()->json([
                    'success' => false,
                    'message' => "Stok tidak cukup untuk {$stok->nama}. Tersedia: {$stok->stok}"
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            // Handle pelanggan
            $pelangganId = $validated['pelanggan_id'] ?? null;
            if (!$pelangganId && !empty($validated['nama_pelanggan'])) {
                $noHp = $validated['no_hp_pelanggan'] ?? '-';
                $pelanggan = Pelanggan::where('no_hp', $noHp)->where('cabang_id', $cabangId)->first();
                if ($pelanggan) {
                    $pelanggan->update(['nama' => $validated['nama_pelanggan']]);
                } else {
                    $user = User::where('phone', $noHp)->first();
                    if (!$user) {
                        $email = $noHp . '@fixpro.local';
                        $counter = 1;
                        while (User::where('email', $email)->exists()) {
                            $email = $noHp . "_{$counter}@fixpro.local";
                            $counter++;
                        }
                        $user = User::create([
                            'name' => $validated['nama_pelanggan'],
                            'email' => $email,
                            'password' => Hash::make($noHp),
                            'phone' => $noHp,
                            'role_id' => 3,
                            'cabang_id' => $cabangId ?? 1,
                            'is_active' => true,
                            'is_permanent' => false,
                            'login_expires_at' => now()->addMonth(),
                        ]);
                    }
                    $pelanggan = Pelanggan::create([
                        'user_id' => $user->id,
                        'nama' => $validated['nama_pelanggan'],
                        'no_hp' => $noHp,
                        'cabang_id' => $cabangId ?? 1,
                    ]);
                }
                $pelangganId = $pelanggan->id;
            }

            $totalKeseluruhan = 0;
            $totalModal = 0;
            $createdIds = [];

            foreach ($validated['items'] as $idx => $item) {
                $stok = Stok::find($item['stok_id']);
                $total = $item['qty'] * $item['harga_satuan'];
                $modalTotal = $item['qty'] * $stok->modal;

                $penjualan = PenjualanSparepart::create([
                    'stok_id' => $stok->id,
                    'pelanggan_id' => $pelangganId,
                    'cabang_id' => $cabangId,
                    'user_id' => auth()->id(),
                    'kode' => PenjualanSparepart::generateKode(),
                    'no_transaksi' => $noTransaksi,
                    'qty' => $item['qty'],
                    'harga_satuan' => $item['harga_satuan'],
                    'total' => $total,
                    'modal_total' => $modalTotal,
                    'diskon' => ($idx === 0) ? $diskon : 0,
                    'metode_bayar' => $validated['metode_bayar'],
                    'catatan' => $validated['catatan'],
                    'tanggal' => now()->format('Y-m-d'),
                ]);

                $stok->decrement('stok', $item['qty']);
                $totalKeseluruhan += $total;
                $totalModal += $modalTotal;
                $createdIds[] = $penjualan->id;

                // Catat pergerakan stok (Kartu Stok)
                SparepartMovementService::record($stok, 'keluar', 'penjualan', (int) $item['qty'], [
                    'referensi'      => $noTransaksi,
                    'referensi_id'   => $penjualan->id,
                    'referensi_model'=> $penjualan,
                    'harga_satuan'   => $item['harga_satuan'],
                    'metode'         => $validated['metode_bayar'],
                    'cabang_id'      => $cabangId,
                ]);
            }

            // Apply discount
            $totalSetelahDiskon = $totalKeseluruhan - $diskon;

            // Auto-catat ke Kas Harian
            $lastKas = \App\Models\Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
            $lastSaldo = $lastKas ? $lastKas->saldo : 0;
            $newSaldo = $lastSaldo + $totalSetelahDiskon;

            \App\Models\Kas::create([
                'tipe' => 'masuk',
                'cabang_id' => $cabangId,
                'jml' => $totalSetelahDiskon,
                'kategori' => 'Penjualan Sparepart',
                'ket' => "POS {$noTransaksi}: " . count($validated['items']) . " item" . ($diskon > 0 ? " (Diskon: Rp " . number_format($diskon) . ")" : ""),
                'metode' => $validated['metode_bayar'],
                'ref' => $noTransaksi,
                'waktu' => now(),
                'saldo' => $newSaldo,
            ]);

            DB::commit();

            AuditLogService::log('penjualan_sparepart', 'create', "POS {$noTransaksi}: " . count($validated['items']) . " item, Total: Rp " . number_format($totalSetelahDiskon));

            return response()->json([
                'success' => true,
                'message' => "Transaksi {$noTransaksi} berhasil!",
                'data' => [
                    'no_transaksi' => $noTransaksi,
                    'total' => $totalSetelahDiskon,
                    'items_count' => count($validated['items']),
                    'ids' => $createdIds,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store single item sale (backward compat)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'stok_id' => 'required|exists:stoks,id',
            'pelanggan_id' => 'nullable|exists:pelanggans,id',
            'qty' => 'required|integer|min:1',
            'harga_satuan' => 'required|numeric|min:0',
            'metode_bayar' => 'required|in:Cash,Transfer,QRIS',
            'catatan' => 'nullable|string',
            'nama_pelanggan' => 'nullable|string',
            'no_hp_pelanggan' => 'nullable|string',
        ]);

        $stok = Stok::findOrFail($validated['stok_id']);

        // Guard: hanya boleh jual stok milik cabang sendiri (jangan kurangi stok toko lain)
        $cabangJual = auth()->user()->getActiveCabangId() ?? auth()->user()->getEffectiveCabangId();
        if ((int) ($stok->cabang_id ?? 0) !== (int) $cabangJual
            && !($stok->cabang_id === null && (int) auth()->user()->getEffectiveCabangId() === 1)) {
            return back()->with('error', "Barang {$stok->nama} bukan milik cabang Anda.")->withInput();
        }

        if ($stok->stok < $validated['qty']) {
            return back()->with('error', "Stok tidak cukup! Tersedia: {$stok->stok}")->withInput();
        }

        DB::beginTransaction();
        try {
            $pelangganId = $validated['pelanggan_id'] ?? null;
            if (!$pelangganId && !empty($validated['nama_pelanggan'])) {
                $noHp = $validated['no_hp_pelanggan'] ?? '-';
                $cabangId2 = auth()->user()->getEffectiveCabangId();
                $pelanggan = Pelanggan::where('no_hp', $noHp)->where('cabang_id', $cabangId2)->first();
                if ($pelanggan) {
                    $pelanggan->update(['nama' => $validated['nama_pelanggan']]);
                } else {
                    $user = User::where('phone', $noHp)->first();
                    if (!$user) {
                        $email = $noHp . '@fixpro.local';
                        $counter = 1;
                        while (User::where('email', $email)->exists()) {
                            $email = $noHp . "_{$counter}@fixpro.local";
                            $counter++;
                        }
                        $user = User::create([
                            'name' => $validated['nama_pelanggan'],
                            'email' => $email,
                            'password' => Hash::make($noHp),
                            'phone' => $noHp,
                            'role_id' => 3,
                            'cabang_id' => $cabangId2,
                            'is_active' => true,
                            'is_permanent' => false,
                            'login_expires_at' => now()->addMonth(),
                        ]);
                    }
                    $pelanggan = Pelanggan::create([
                        'user_id' => $user->id,
                        'nama' => $validated['nama_pelanggan'],
                        'no_hp' => $noHp,
                        'cabang_id' => $cabangId2,
                    ]);
                }
                $pelangganId = $pelanggan->id;
            }

            $noTransaksi = PenjualanSparepart::generateNoTransaksi();
            $total = $validated['qty'] * $validated['harga_satuan'];
            $modalTotal = $validated['qty'] * $stok->modal;

            $penjualan = PenjualanSparepart::create([
                'stok_id' => $stok->id,
                'pelanggan_id' => $pelangganId,
                'cabang_id' => auth()->user()->getEffectiveCabangId(),
                'user_id' => auth()->id(),
                'kode' => PenjualanSparepart::generateKode(),
                'no_transaksi' => $noTransaksi,
                'qty' => $validated['qty'],
                'harga_satuan' => $validated['harga_satuan'],
                'total' => $total,
                'modal_total' => $modalTotal,
                'metode_bayar' => $validated['metode_bayar'],
                'catatan' => $validated['catatan'],
                'tanggal' => now()->format('Y-m-d'),
            ]);

            $stok->decrement('stok', $validated['qty']);

            // Catat pergerakan stok (Kartu Stok)
            SparepartMovementService::record($stok, 'keluar', 'penjualan', (int) $validated['qty'], [
                'referensi'      => $noTransaksi,
                'referensi_id'   => $penjualan->id,
                'referensi_model'=> $penjualan,
                'harga_satuan'   => $validated['harga_satuan'],
                'metode'         => $validated['metode_bayar'],
                'cabang_id'      => auth()->user()->getActiveCabangId(),
            ]);

            $cabangId = auth()->user()->getActiveCabangId();
            $lastKas = \App\Models\Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
            $lastSaldo = $lastKas ? $lastKas->saldo : 0;
            $newSaldo = $lastSaldo + $total;
            \App\Models\Kas::create([
                'tipe' => 'masuk',
                'cabang_id' => $cabangId,
                'jml' => $total,
                'kategori' => 'Penjualan Sparepart',
                'ket' => "Penjualan {$penjualan->kode}: {$stok->nama} x{$validated['qty']}",
                'metode' => $validated['metode_bayar'],
                'ref' => $penjualan->kode,
                'waktu' => now(),
                'saldo' => $newSaldo,
            ]);

            DB::commit();
            AuditLogService::log('penjualan_sparepart', 'create', "Penjualan sparepart {$penjualan->kode}: {$stok->nama} x{$validated['qty']} = Rp " . number_format($total));
            return redirect()->route('penjualan-sparepart.index')->with('success', "Penjualan {$penjualan->kode} berhasil! Stok {$stok->nama} dikurangi {$validated['qty']}.", $penjualan);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage())->withInput();
        }
    }

    public function show(PenjualanSparepart $penjualan_sparepart)
    {
        $this->checkCabangAccess($penjualan_sparepart);
        $penjualan_sparepart->load(['stok', 'pelanggan', 'user', 'cabang']);

        // Load other items in the same transaction
        $siblings = collect([]);
        if ($penjualan_sparepart->no_transaksi) {
            $siblings = PenjualanSparepart::with('stok')
                ->where('no_transaksi', $penjualan_sparepart->no_transaksi)
                ->where('id', '!=', $penjualan_sparepart->id)
                ->get();
        }

        return view('penjualan-sparepart.show', compact('penjualan_sparepart', 'siblings'));
    }

    public function batal(Request $request, PenjualanSparepart $penjualan_sparepart)
    {
        $this->checkCabangAccess($penjualan_sparepart);

        $user = auth()->user();

        if (!$user->isAdmin() && !$user->isStaff() && !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses untuk membatalkan transaksi.'], 403);
        }

        if ($penjualan_sparepart->status === 'Dibatalkan') {
            return response()->json(['success' => false, 'message' => 'Transaksi ini sudah dibatalkan.'], 400);
        }

        $request->validate([
            'alasan' => 'required|string|min:3|max:500',
        ]);

        $penjualan_sparepart->stok?->increment('stok', $penjualan_sparepart->qty);

        // Catat pergerakan stok (Kartu Stok): pembatalan menambah stok kembali
        if ($penjualan_sparepart->stok) {
            SparepartMovementService::record($penjualan_sparepart->stok, 'masuk', 'batal_penjualan', (int) $penjualan_sparepart->qty, [
                'referensi'      => 'BATAL-' . $penjualan_sparepart->kode,
                'referensi_id'   => $penjualan_sparepart->id,
                'referensi_model'=> $penjualan_sparepart,
                'metode'         => $penjualan_sparepart->metode_bayar,
                'cabang_id'      => $penjualan_sparepart->cabang_id,
                'catatan'        => 'Pembatalan: ' . $request->alasan,
            ]);
        }

        if ($penjualan_sparepart->total > 0) {
            $cabangId = $penjualan_sparepart->cabang_id;
            $lastKas = \App\Models\Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
            $lastSaldo = $lastKas ? $lastKas->saldo : 0;
            $newSaldo = $lastSaldo - $penjualan_sparepart->total;
            \App\Models\Kas::create([
                'tipe' => 'keluar',
                'cabang_id' => $cabangId,
                'jml' => $penjualan_sparepart->total,
                'kategori' => 'Pembatalan Penjualan',
                'ket' => "Pembatalan {$penjualan_sparepart->kode}",
                'metode' => $penjualan_sparepart->metode_bayar ?? 'Cash',
                'ref' => 'BATAL-' . $penjualan_sparepart->kode,
                'waktu' => now(),
                'saldo' => $newSaldo,
            ]);
        }

        $penjualan_sparepart->update([
            'status' => 'Dibatalkan',
            'alasan_pembatalan' => $request->alasan,
            'dibatalkan_oleh' => $user->id,
            'dibatalkan_pada' => now(),
        ]);

        AuditLogService::log('penjualan_sparepart', 'batal', "Membatalkan penjualan sparepart {$penjualan_sparepart->kode}. Alasan: {$request->alasan}", $penjualan_sparepart);

        return response()->json(['success' => true, 'message' => "Transaksi {$penjualan_sparepart->kode} berhasil dibatalkan."]);
    }

    public function destroy(PenjualanSparepart $penjualan_sparepart)
    {
        $this->checkCabangAccess($penjualan_sparepart);
        $penjualan_sparepart->stok->increment('stok', $penjualan_sparepart->qty);
        AuditLogService::log('penjualan_sparepart', 'delete', "Menghapus penjualan sparepart {$penjualan_sparepart->kode}, stok dikembalikan");
        $penjualan_sparepart->delete();
        return redirect()->route('penjualan-sparepart.index')->with('success', 'Transaksi dihapus, stok dikembalikan.');
    }

    /**
     * Hapus banyak penjualan sparepart sekaligus (stok dikembalikan).
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids)) $ids = [$ids];
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return redirect()->route('penjualan-sparepart.index')->with('error', 'Tidak ada item yang dipilih untuk dihapus.');
        }

        $items = PenjualanSparepart::whereIn('id', $ids)->get();
        $count = 0;
        foreach ($items as $penjualan) {
            // cek akses cabang
            try { $this->checkCabangAccess($penjualan); } catch (\Exception $e) { continue; }
            if ($penjualan->stok) {
                $penjualan->stok->increment('stok', $penjualan->qty);
            }
            AuditLogService::log('penjualan_sparepart', 'delete', "Menghapus penjualan sparepart {$penjualan->kode}, stok dikembalikan");
            $penjualan->delete();
            $count++;
        }

        return redirect()->route('penjualan-sparepart.index')
            ->with('success', $count . ' transaksi berhasil dihapus (stok dikembalikan).');
    }
}
