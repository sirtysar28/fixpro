<?php

namespace App\Http\Controllers;

use App\Models\Stok;
use App\Services\AuditLogService;
use App\Services\SparepartMovementService;
use App\Services\XlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StokController extends Controller
{
    /**
     * Pastikan stok milik cabang yang sedang login (Admin Cabang hanya cabang sendiri).
     *
     * Aturan multi-cabang (enterprise):
     * - Admin Cabang PUSAT → boleh kelola cabangnya + cabang anak yang ia buat (via switch cabang)
     * - Admin Cabang ANAK  → TERKUNCI ke cabangnya sendiri, tidak boleh ketuker dengan cabang lain
     */
    private function checkCabangAccess(Stok $stok): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) return;

        // Admin Cabang Anak: strict ke cabang sendiri (tidak bisa switch)
        if ($user->isAdminCabangAnak()) {
            if ($stok->cabang_id != $user->cabang_id) {
                abort(403, 'Admin Cabang Anak hanya bisa mengelola daftar sparepart cabang Anda sendiri.');
            }
            return;
        }

        // Admin ENTERPRISE (pusat): boleh mengelola seluruh cabang dalam grupnya
        // sendiri (pusat + semua cabang anak), sesuai cabang sparepart tersebut.
        if ($user->isEnterprise() && $user->isAdmin()) {
            $allowed = $user->getAllowedCabangIds();
            $stokCabang = $stok->cabang_id !== null ? (int) $stok->cabang_id : null;
            if ($stokCabang !== null && in_array($stokCabang, $allowed, true)) {
                return; // sparepart milik grup sendiri → boleh edit
            }
            // Kompatibilitas data lama: sparepart tanpa cabang (cabang_id NULL)
            // hanya boleh bila sedang aktif di cabang default (1)
            if ($stokCabang === null && (int) $user->getActiveCabangId() === 1) {
                return;
            }
            abort(403, 'Sparepart ini bukan milik grup cabang Anda.');
        }

        $cabangId = $user->getActiveCabangId();
        if ($stok->cabang_id != $cabangId) {
            // Kompatibilitas data lama: sparepart tanpa cabang saat aktif di cabang default
            if (!($stok->cabang_id === null && (int) $cabangId === 1)) {
                abort(403, 'Anda hanya bisa mengelola stok di cabang Anda sendiri.');
            }
        }
    }

    /**
     * Gate: halaman STOK tidak boleh campur antar toko.
     * Super Admin yang sedang di mode "Semua Cabang" wajib pilih toko dulu.
     * Return null = boleh lanjut (sudah ada cabang aktif).
     */
    private function requireCabangForStok(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();
        if ($cabangId === null) {
            return view('stok.pilih-cabang', ['redirectTo' => $request->fullUrl()]);
        }
        return null;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $cabangId = $user->getActiveCabangId();

        // Super Admin mode "Semua Cabang": jangan tampilkan stok campur antar toko
        if ($cabangId === null) {
            return view('stok.pilih-cabang', ['redirectTo' => $request->fullUrl()]);
        }

        // ===== Filter cabang/gudang (hanya utk user yang punya beberapa cabang boleh) =====
        $allowedCabangs = collect();
        if ($user->isSuperAdmin() || ($user->isEnterprise() && $user->isAdmin())) {
            $ids = $user->isSuperAdmin() ? null : $user->getAllowedCabangIds();
            $allowedCabangs = $ids
                ? \App\Models\Cabang::whereIn('id', $ids)->orderBy('nama')->get()
                : \App\Models\Cabang::orderBy('nama')->get();
        }
        $filterCabang = $cabangId;
        if ($request->filled('cabang') && $allowedCabangs->pluck('id')->contains((int) $request->cabang)) {
            $filterCabang = (int) $request->cabang;
        }

        // Stok SELALU milik cabang aktif / terpilih saja (tidak campur toko lain)
        $query = Stok::where('cabang_id', $filterCabang);

        // ===== Filter kata pencarian =====
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nama', 'like', "%$s%")->orWhere('kode', 'like', "%$s%")->orWhere('barcode', 'like', "%$s%")
                  ->orWhere('merk_hp', 'like', "%$s%")->orWhere('kategori', 'like', "%$s%");
            });
        }
        // ===== Filter kategori & merek =====
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }
        if ($request->filled('merk')) {
            $query->where('merk_hp', $request->merk);
        }

        // ===== Sorting (default nama asc) =====
        $sort = $request->input('sort', 'nama');
        $dir = strtolower($request->input('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $sortable = ['nama', 'kode', 'kategori', 'merk_hp', 'stok', 'modal', 'jual'];
        if (!in_array($sort, $sortable)) $sort = 'nama';
        $query->orderBy($sort, $dir)->orderBy('nama', 'asc');

        // ===== Jumlah data per halaman =====
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) $perPage = 20;

        $stoks = $query->paginate($perPage)->appends($request->query());

        // SIMPAN kondisi halaman terakhir (nomor halaman + filter + sort + per halaman)
        // supaya setelah Edit Barang → Simpan, user kembali ke kondisi yang sama.
        session(['stok.index_url' => $request->fullUrl()]);

        // Stats juga harus per cabang (ikut filter cabang terpilih)
        $statsQuery = Stok::where('cabang_id', $filterCabang);
        $totalJenis = (clone $statsQuery)->count();
        $stokLow = (clone $statsQuery)->where('stok', '>', 0)->where('stok', '<=', \DB::raw('min_alert'))->count();
        $stokHabis = (clone $statsQuery)->where('stok', 0)->count();

        // Opsi dropdown filter
        $kategoriList = Stok::where('cabang_id', $filterCabang)->distinct()->orderBy('kategori')->pluck('kategori');
        $merkList = Stok::where('cabang_id', $filterCabang)->whereNotNull('merk_hp')->where('merk_hp', '!=', '')->distinct()->orderBy('merk_hp')->pluck('merk_hp');

        return view('stok.index', compact('stoks', 'totalJenis', 'stokLow', 'stokHabis', 'allowedCabangs', 'filterCabang', 'kategoriList', 'merkList', 'sort', 'dir', 'perPage'));
    }

    public function create(Request $request)
    {
        // Wajib pilih toko dulu supaya barang baru tidak nyasar ke toko lain
        if ($gate = $this->requireCabangForStok($request)) return $gate;

        return view('stok.create');
    }

    /**
     * Normalisasi input angka dari form (stok, harga, min_alert).
     * Browser/JS lama bisa mengirim angka berformat titik ribuan ("1.500.000")
     * atau koma ("1500000,") yang membuat validasi gagal / nilai tersimpan salah.
     * Di sini kita bersihkan jadi digit murni SEBELUM validasi.
     */
    private function normalizeNumericInputs(Request $request): void
    {
        foreach (['stok', 'modal', 'jual', 'min_alert'] as $field) {
            if (!$request->has($field)) continue;
            $raw = trim((string) $request->input($field));
            if ($raw === '') continue; // biarkan aturan nullable/default
            // "1.500.000" / "1,500,000" / " 150000 " → "1500000"
            $clean = preg_replace('/[^\d]/', '', $raw);
            $request->merge([$field => $clean === '' ? '0' : $clean]);
        }
    }

    /**
     * Parse angka dari sel Excel/CSV yang bisa berformat Indonesia maupun Inggris.
     * "115.000" → 115000 (titik ribuan ID) | "115,000" → 115000 | "115.000,50" → 115000.5
     * "115,000.50" → 115000.5 | "115000" → 115000 | "0,5" → 0.5 | "115.5" → 115.5
     * FIX: sebelumnya (float)"115.000" = 115 → harga 115.000 tersimpan jadi 115.
     */
    private function parseNumberId($value): float
    {
        if (is_int($value) || is_float($value)) return (float) $value;
        $s = trim((string) ($value ?? ''));
        if ($s === '') return 0.0;
        // Buang "Rp", spasi biasa & spasi tak putus (nbsp)
        $s = str_replace(["\xC2\xA0", ' ', 'Rp', 'rp', 'RP'], '', $s);

        $hasDot = str_contains($s, '.');
        $hasComma = str_contains($s, ',');

        if ($hasDot && $hasComma) {
            // Separator terakhir = desimal (ID: 1.150.000,50 | EN: 1,150,000.50)
            if (strrpos($s, ',') > strrpos($s, '.')) {
                $s = str_replace('.', '', $s);  // titik = ribuan
                $s = str_replace(',', '.', $s); // koma = desimal
            } else {
                $s = str_replace(',', '', $s);  // koma = ribuan
            }
        } elseif ($hasDot) {
            // Titik saja: pola ribuan ID (115.000 / 1.150.000) → ribuan; selain itu desimal (115.5)
            if (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $s)) {
                $s = str_replace('.', '', $s);
            }
        } elseif ($hasComma) {
            // Koma saja: pola ribuan (115,000) → ribuan; selain itu desimal (0,5)
            if (preg_match('/^-?\d{1,3}(,\d{3})+$/', $s)) {
                $s = str_replace(',', '', $s);
            } else {
                $s = str_replace(',', '.', $s);
            }
        }
        return (float) $s;
    }

    public function store(Request $request)
    {
        // Wajib pilih toko dulu supaya barang baru tidak nyasar ke toko lain
        if ($gate = $this->requireCabangForStok($request)) return $gate;

        $this->normalizeNumericInputs($request);

        $cabangId = auth()->user()->getEffectiveCabangId();
        $namaBarang = trim((string) $request->input('nama'));
        $validated = $request->validate([
            // Kode boleh sama untuk barang berbeda — kombinasi Kode+Nama yang harus unik per cabang
            'kode' => [
                'required',
                Rule::unique('stoks', 'kode')->where(fn ($q) => $q
                    ->where('cabang_id', $cabangId)
                    ->whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower($namaBarang)])),
            ],
            'barcode' => 'nullable|string|unique:stoks,barcode,NULL,id,cabang_id,' . $cabangId,
            'nama' => 'required',
            'kategori' => 'required',
            'merk_hp' => 'nullable',
            'stok' => 'integer|min:0',
            'modal' => 'numeric|min:0',
            'jual' => 'numeric|min:0',
            'min_alert' => 'integer|min:0',
        ]);
        // Barang baru SELALU masuk ke cabang milik user:
        // - Admin Cabang Anak → terkunci ke cabangnya sendiri
        // - Enterprise pusat → cabang yang sedang aktif di-switch
        $user = auth()->user();
        $validated['cabang_id'] = $user->isAdminCabangAnak()
            ? ((int) $user->cabang_id ?: $user->getEffectiveCabangId())
            : $user->getActiveCabangId();
        // Safety net: jangan pernah simpan cabang_id 0 (tidak kelihatan di daftar stok toko mana pun)
        if (empty($validated['cabang_id'])) {
            $validated['cabang_id'] = $user->getEffectiveCabangId();
        }

        DB::beginTransaction();
        try {
            // Auto-generate barcode if empty
            if (empty($validated['barcode'])) {
                unset($validated['barcode']);
                $s = Stok::create($validated);
                $s->barcode = 'FXP' . str_pad($s->id, 7, '0', STR_PAD_LEFT);
                $s->save();
            } else {
                $s = Stok::create($validated);
            }
            // Catat stok awal ke kartu stok
            if ((int) ($validated['stok'] ?? 0) > 0) {
                SparepartMovementService::record($s, 'masuk', 'stok_awal', (int) $validated['stok'], [
                    'referensi'      => 'STOK-AWAL-' . $s->kode,
                    'harga_satuan'   => (float) ($validated['modal'] ?? 0),
                    'cabang_id'      => $validated['cabang_id'],
                    'catatan'        => 'Input barang baru',
                ]);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withInput()->with('error', 'Gagal menyimpan barang: ' . $e->getMessage());
        }

        AuditLogService::created('stok', "Menambahkan stok: {$validated['nama']} ({$validated['kode']})", $s);
        // Kembali ke kondisi daftar stok terakhir (halaman + filter tetap)
        return redirect()->to(session()->pull('stok.index_url', route('stok.index')))->with('success', 'Barang berhasil ditambahkan!');
    }

    public function edit(Stok $stok)
    {
        $this->checkCabangAccess($stok);
        return view('stok.edit', compact('stok'));
    }

    public function update(Request $request, Stok $stok)
    {
        $this->checkCabangAccess($stok);

        $this->normalizeNumericInputs($request);

        $namaBarang = trim((string) $request->input('nama'));
        $validated = $request->validate([
            // Kode boleh sama untuk barang berbeda — kombinasi Kode+Nama yang harus unik per cabang
            'kode' => [
                'required',
                Rule::unique('stoks', 'kode')->ignore($stok->id)->where(fn ($q) => $q
                    ->where('cabang_id', $stok->cabang_id)
                    ->whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower($namaBarang)])),
            ],
            'barcode' => 'nullable|string|unique:stoks,barcode,' . $stok->id . ',id,cabang_id,' . $stok->cabang_id,
            'nama' => 'required',
            'kategori' => 'required',
            'merk_hp' => 'nullable',
            'stok' => 'integer|min:0',
            'modal' => 'numeric|min:0',
            'jual' => 'numeric|min:0',
            'min_alert' => 'integer|min:0',
        ]);
        $oldStok = (int) $stok->stok;

        DB::beginTransaction();
        try {
            $stok->update($validated);

            // Catat perubahan stok manual ke kartu stok
            if (isset($validated['stok'])) {
                $newStok = (int) $validated['stok'];
                $diff = $newStok - $oldStok;
                if ($diff > 0) {
                    SparepartMovementService::record($stok, 'masuk', 'edit_stok', $diff, [
                        'referensi' => 'EDIT-' . $stok->kode,
                        'catatan'   => "Edit stok: {$oldStok} → {$newStok}",
                        'cabang_id' => $stok->cabang_id,
                    ]);
                } elseif ($diff < 0) {
                    SparepartMovementService::record($stok, 'keluar', 'edit_stok', abs($diff), [
                        'referensi' => 'EDIT-' . $stok->kode,
                        'catatan'   => "Edit stok: {$oldStok} → {$newStok}",
                        'cabang_id' => $stok->cabang_id,
                    ]);
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withInput()->with('error', 'Gagal mengupdate barang: ' . $e->getMessage());
        }

        AuditLogService::updated('stok', "Mengupdate stok: {$stok->nama}", $stok);
        // REVISI #7 & #8: kembali ke halaman & kondisi daftar stok TERAKHIR
        // (Stok → Halaman 2 → Edit → Simpan → tetap Halaman 2 + filter sama)
        return redirect()->to(session()->pull('stok.index_url', route('stok.index')))->with('success', 'Barang berhasil diupdate!');
    }

    public function destroy(Stok $stok)
    {
        $this->checkCabangAccess($stok);
        AuditLogService::deleted('stok', "Menghapus stok: {$stok->nama}", $stok);
        $stok->delete();
        return redirect()->to(session()->pull('stok.index_url', route('stok.index')))->with('success', 'Barang berhasil dihapus!');
    }

    /**
     * Quick update stok: +/- 1
     */
    public function quickUpdate(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:stoks,id',
            'delta' => 'required|integer',
        ]);

        $stok = Stok::find($request->id);
        $this->checkCabangAccess($stok);

        $newStok = $stok->stok + $request->delta;

        if ($newStok < 0) {
            return response()->json(['success' => false, 'message' => 'Stok tidak boleh kurang dari 0']);
        }

        $oldStok = $stok->stok;
        $stok->update(['stok' => $newStok]);
        AuditLogService::log('stok', 'update', "Quick update stok {$stok->nama}: {$oldStok} -> {$newStok}");

        // Catat pergerakan stok (Kartu Stok)
        if ($request->delta > 0) {
            SparepartMovementService::record($stok, 'masuk', 'adjustment_naik', abs((int) $request->delta), [
                'referensi' => 'ADJ-' . $stok->kode,
                'catatan'   => "Penyesuaian manual: {$oldStok} → {$newStok}",
                'cabang_id' => $stok->cabang_id,
            ]);
        } elseif ($request->delta < 0) {
            SparepartMovementService::record($stok, 'keluar', 'adjustment_turun', abs((int) $request->delta), [
                'referensi' => 'ADJ-' . $stok->kode,
                'catatan'   => "Penyesuaian manual: {$oldStok} → {$newStok}",
                'cabang_id' => $stok->cabang_id,
            ]);
        }

        return response()->json(['success' => true, 'message' => "Stok {$stok->nama} sekarang {$newStok}"]);
    }

    // ============================================================
    //  IMPORT / EXPORT EXCEL STOK (.xlsx Office Open XML — tanpa dependency)
    // ============================================================

    /**
     * Export seluruh stok cabang ke file Excel .xlsx (kompatibel semua office app).
     * Kolom: Nama Barang, Kode, Jumlah Stok, Harga Modal, Harga Jual, Kategori, Min Alert.
     */
    public function exportExcel()
    {
        $cabangId = auth()->user()->getActiveCabangId();
        // Export juga per toko saja — jangan campur antar toko
        $stoks = Stok::where('cabang_id', $cabangId)->orderBy('nama')->get();

        $w = new XlsxWriter();
        $s = $w->sheet('Stok');
        $s->widths([150, 100, 80, 100, 100, 90, 70]);
        $s->headerRow(['Nama Barang', 'Kode', 'Jumlah Stok', 'Harga Modal', 'Harga Jual', 'Kategori', 'Min Alert']);
        foreach ($stoks as $st) {
            $s->row([
                $st->nama,
                $st->kode,
                $st->stok,
                $st->modal,
                $st->jual,
                $st->kategori,
                $st->min_alert,
            ]);
        }

        $nama = 'Stok_FixPro_' . date('Y-m-d') . '.xlsx';
        return $w->download($nama);
    }

    /**
     * Download template Excel .xlsx kosong (1 baris contoh) untuk diisi & diimpor.
     */
    public function templateExcel()
    {
        $w = new XlsxWriter();
        $s = $w->sheet('Template');
        $s->widths([150, 100, 80, 100, 100, 90, 70]);
        $s->headerRow(['Nama Barang', 'Kode', 'Jumlah Stok', 'Harga Modal', 'Harga Jual', 'Kategori', 'Min Alert']);
        $s->row(['LCD iPhone 11', 'LCD-IP11', 10, 150000, 250000, 'LCD', 3]);
        return $w->download('Template_Stok_FixPro.xlsx');
    }

    /**
     * Import stok dari file Excel/CSV. Cocokkan berdasarkan Kode (per cabang).
     * - Kode sudah ada → update stok/modal/jual
     * - Kode baru → insert baru
     */
    public function importExcel(Request $request)
    {
        // Wajib pilih toko dulu — import tanpa cabang aktif bisa menimpa stok toko lain
        if ($gate = $this->requireCabangForStok($request)) return $gate;

        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx,csv,txt|max:5120',
        ]);

        $rows = $this->parseSpreadsheetFile($request->file('file')->getRealPath(), $request->file('file')->getClientOriginalExtension());
        if (empty($rows)) {
            return back()->with('error', 'File kosong atau format tidak dikenal. Gunakan template yang disediakan.');
        }

        // Validasi header minimal
        $header = array_map('strtolower', array_map('trim', $rows[0]));
        $colNama = array_search('nama barang', $header);
        $colKode = array_search('kode', $header);
        if ($colNama === false || $colKode === false) {
            return back()->with('error', 'Header tidak valid. Pastikan ada kolom "Nama Barang" dan "Kode". Download template untuk format yang benar.');
        }
        $colStok    = array_search('jumlah stok', $header); if ($colStok === false) $colStok = array_search('stok', $header);
        $colModal   = array_search('harga modal', $header); if ($colModal === false) $colModal = array_search('modal', $header);
        $colJual    = array_search('harga jual', $header); if ($colJual === false) $colJual = array_search('jual', $header);
        $colKategori= array_search('kategori', $header);
        $colMin     = array_search('min alert', $header); if ($colMin === false) $colMin = array_search('min_alert', $header);

        $cabangId = auth()->user()->getActiveCabangId();
        $inserted = 0; $updated = 0; $errors = [];
        $seen = []; // deteksi duplikat kode+nama di dalam file yang sama

        DB::beginTransaction();
        try {
            for ($i = 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                $nama = trim($r[$colNama] ?? '');
                $kode = trim($r[$colKode] ?? '');
                if ($nama === '' && $kode === '') continue; // skip baris kosong
                if ($nama === '' || $kode === '') {
                    $errors[] = "Baris " . ($i + 1) . ": Nama dan Kode wajib diisi.";
                    continue;
                }

                $stok    = $colStok !== false ? (int) round($this->parseNumberId($r[$colStok] ?? 0)) : null;
                $modal   = $colModal !== false ? $this->parseNumberId($r[$colModal] ?? 0) : null;
                $jual    = $colJual !== false ? $this->parseNumberId($r[$colJual] ?? 0) : null;
                $kategori= $colKategori !== false ? trim($r[$colKategori] ?? '') : null;
                $min     = $colMin !== false ? (int) round($this->parseNumberId($r[$colMin] ?? 0)) : null;

                // Duplikat persis (kode+nama sama) di dalam satu file → cukup sekali
                $key = mb_strtolower($kode) . '||' . mb_strtolower($nama);
                if (isset($seen[$key])) {
                    $errors[] = "Baris " . ($i + 1) . ": {$kode} - {$nama} duplikat (sudah ada di baris {$seen[$key]}).";
                    continue;
                }
                $seen[$key] = $i + 1;

                // Cocokkan berdasarkan KODE + NAMA (per cabang).
                // Kode boleh sama untuk barang berbeda (mis. kode tipe LCD "OG" untuk
                // banyak model HP) — yang membedakan barang adalah kombinasi kode+nama.
                $existing = Stok::where('kode', $kode)
                    ->where('cabang_id', $cabangId)
                    ->whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower($nama)])
                    ->first();

                if ($existing) {
                    $upd = [];
                    if ($stok !== null)    $upd['stok'] = max(0, $stok);
                    if ($modal !== null)   $upd['modal'] = max(0, $modal);
                    if ($jual !== null)    $upd['jual'] = max(0, $jual);
                    if ($kategori)         $upd['kategori'] = $kategori;
                    if ($min !== null)     $upd['min_alert'] = max(0, $min);
                    if ($upd) {
                        $oldStok = (int) $existing->stok;
                        $existing->update($upd);
                        $updated++;
                        // Catat perubahan stok dari import ke kartu stok
                        if ($stok !== null) {
                            $newStok = (int) max(0, $stok);
                            $diff = $newStok - $oldStok;
                            if ($diff > 0) {
                                SparepartMovementService::record($existing, 'masuk', 'import', $diff, [
                                    'referensi' => 'IMPORT-' . $existing->kode,
                                    'catatan'   => "Import Excel: {$oldStok} → {$newStok}",
                                    'cabang_id' => $existing->cabang_id,
                                ]);
                            } elseif ($diff < 0) {
                                SparepartMovementService::record($existing, 'keluar', 'import', abs($diff), [
                                    'referensi' => 'IMPORT-' . $existing->kode,
                                    'catatan'   => "Import Excel: {$oldStok} → {$newStok}",
                                    'cabang_id' => $existing->cabang_id,
                                ]);
                            }
                        }
                    }
                } else {
                    $newStokModel = Stok::create([
                        'nama'       => $nama,
                        'kode'       => $kode,
                        'barcode'    => 'FXP' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                        'stok'       => $stok ?? 0,
                        'modal'      => $modal ?? 0,
                        'jual'       => $jual ?? 0,
                        'kategori'   => $kategori ?: 'Import',
                        'min_alert'  => $min ?? 1,
                        'cabang_id'  => $cabangId,
                    ]);
                    // Catat stok awal hasil import
                    if (($stok ?? 0) > 0) {
                        SparepartMovementService::record($newStokModel, 'masuk', 'import', (int) $stok, [
                            'referensi'   => 'IMPORT-' . $kode,
                            'catatan'     => 'Barang baru dari Import Excel',
                            'cabang_id'   => $cabangId,
                        ]);
                    }
                    $inserted++;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }

        AuditLogService::log('stok', 'import', "Import stok: {$inserted} baru, {$updated} update");
        $msg = "Import selesai: {$inserted} barang baru, {$updated} diperbarui.";
        if ($errors) {
            $msg .= ' ' . count($errors) . ' baris dilewati: ' . implode(' ', array_slice($errors, 0, 5));
            if (count($errors) > 5) $msg .= ' …';
        }
        return back()->with('success', $msg);
    }

    /**
     * Parse file .xls (SpreadsheetML), .xlsx (tidak didukung tanpa lib), atau CSV.
     * Mendukung .xlsx (Office Open XML / zip), .xls (SpreadsheetML), HTML table, dan CSV.
     */
    private function parseSpreadsheetFile(string $path, string $ext): array
    {
        $ext = strtolower($ext);
        $content = @file_get_contents($path);

        // CSV / TXT → simple parse
        if (in_array($ext, ['csv', 'txt'])) {
            return $this->parseCsv($content ?: '');
        }

        // .xlsx (Office Open XML — arsip ZIP berisi XML)
        if (class_exists(\ZipArchive::class) && $ext === 'xlsx') {
            $rows = $this->parseXlsx($path);
            if (!empty($rows)) return $rows;
            // gagal ekstrak → lanjut ke fallback di bawah
        }

        // SpreadsheetML (.xls XML)
        if (str_contains($content, '<Workbook') || str_contains($content, '<Table')) {
            return $this->parseSpreadsheetXml($content);
        }

        // HTML table (Excel sering simpan sebagai HTML)
        if (str_contains($content, '<table') || str_contains($content, '<TABLE')) {
            return $this->parseHtmlTable($content);
        }

        // Fallback: coba CSV
        return $this->parseCsv($content ?: '');
    }

    /**
     * Baca file .xlsx asli (zip berisi XML). Ambil sheet pertama.
     * Mengembalikan array of rows (mirip parseSpreadsheetXml).
     */
    private function parseXlsx(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) return [];

        try {
            // 1. Baca shared strings (jika ada)
            $shared = [];
            $ssXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($ssXml !== false && preg_match_all('/<si\b[^>]*\/>|<si\b[^>]*>(.*?)<\/si>/s', $ssXml, $siMatches)) {
                foreach ($siMatches[1] as $si) {
                    // <si/> (string kosong) → '' agar index shared string tetap sejajar
                    if ($si === null) { $shared[] = ''; continue; }
                    // gabungkan semua <t> di dalam <si> (rich text)
                    $txt = '';
                    if (preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $si, $tMatches)) {
                        foreach ($tMatches[1] as $tPart) $txt .= $tPart;
                    }
                    $shared[] = html_entity_decode($txt, ENT_QUOTES, 'UTF-8');
                }
            }

            // 2. Cari file sheet pertama (sheet1.xml, atau via workbook.xml.rels)
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheetXml === false) {
                // cari nama sheet pertama via rels
                $wbXml = $zip->getFromName('xl/workbook.xml');
                $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
                if ($wbXml !== false && preg_match('/<sheet\b[^>]*\br:id="([^"]+)"/', $wbXml, $ridM)) {
                    $rid = $ridM[1];
                    if ($relsXml !== false && preg_match('/<Relationship\b[^>]*\bId="' . preg_quote($rid, '/') . '"[^>]*\bTarget="([^"]+)"/', $relsXml, $tgtM)) {
                        $sheetXml = $zip->getFromName('xl/' . ltrim($tgtM[1], '/'));
                    }
                }
            }
            if ($sheetXml === false) return [];

            // 3. Parse rows & cells
            // Regex row mendukung <row/> self-closing dan <row>...</row>
            if (!preg_match_all('/<row\b[^>]*\/>|<row\b[^>]*>(.*?)<\/row>/s', $sheetXml, $rowMatches)) return [];
            $rows = [];
            foreach ($rowMatches[1] as $rowInner) {
                // <row/> self-closing atau <row></row> → baris kosong (skip)
                if ($rowInner === '' || $rowInner === null) continue;

                $cells = []; // posisi kolom eksplisit (0-based) => nilai
                if (preg_match_all('/<c\b([^>]*?)(?:\/>|>(.*?)<\/c>)/s', $rowInner, $cellMatches, PREG_SET_ORDER)) {
                    $seq = -1; // fallback bila sel tanpa atribut r="A1"
                    foreach ($cellMatches as $cm) {
                        $attrs = $cm[1];
                        $inner = $cm[2] ?? '';

                        // Posisi kolom dari atribut r (mis. "B3" → kolom B = index 1)
                        // Penting: sel kosong di tengah baris tidak boleh menggeser kolom.
                        if (preg_match('/\br="([A-Za-z]+)\d*"/', $attrs, $rm)) {
                            $colIdx = $this->xlsxColToIndex($rm[1]);
                        } else {
                            $colIdx = ++$seq;
                        }

                        $t = '';
                        if (preg_match('/\bt="([^"]+)"/', $attrs, $tm)) $t = $tm[1];

                        if ($t === 'inlineStr') {
                            // nilai ada di <is><t>...</t></is>
                            $val = '';
                            if (preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $inner, $tParts)) {
                                foreach ($tParts[1] as $tp) $val .= $tp;
                            }
                            $cells[$colIdx] = html_entity_decode($val, ENT_QUOTES, 'UTF-8');
                        } elseif ($t === 's') {
                            // shared string by index — ambil <v> (sel bisa berisi <f> dulu)
                            $idx = null;
                            if (preg_match('/<v[^>]*>(.*?)<\/v>/s', $inner, $vm)) $idx = (int) $vm[1];
                            $cells[$colIdx] = $idx !== null ? ($shared[$idx] ?? '') : '';
                        } elseif ($t === 'str' || $t === 'e') {
                            // hasil formula string / error — ambil teks <v> apa adanya
                            $val = '';
                            if (preg_match('/<v[^>]*>(.*?)<\/v>/s', $inner, $vm)) $val = $vm[1];
                            $cells[$colIdx] = html_entity_decode($val, ENT_QUOTES, 'UTF-8');
                        } else {
                            // number (default) / boolean — ambil <v> meski didahului <f>
                            $val = '';
                            if (preg_match('/<v[^>]*>(.*?)<\/v>/s', $inner, $vm)) $val = trim($vm[1]);
                            $cells[$colIdx] = is_numeric($val) ? 0 + $val : $val;
                        }
                    }
                }

                // Rapikan ke array berindeks 0..maxCol agar kolom tidak bergeser
                $row = [];
                $maxCol = $cells ? max(array_keys($cells)) : -1;
                for ($j = 0; $j <= $maxCol; $j++) $row[$j] = $cells[$j] ?? '';
                $rows[] = $row;
            }
            return $rows;
        } finally {
            $zip->close();
        }
    }

    /**
     * Konversi huruf kolom Excel (A, B, … AA, AB, …) ke index 0-based.
     */
    private function xlsxColToIndex(string $letters): int
    {
        $idx = 0;
        foreach (str_split(strtoupper($letters)) as $ch) {
            $idx = $idx * 26 + (ord($ch) - 64); // A=1
        }
        return $idx - 1;
    }

    private function parseSpreadsheetXml(string $content): array
    {
        $rows = [];
        if (!preg_match_all('/<Row[^>]*>(.*?)<\/Row>/s', $content, $rowMatches)) return [];
        foreach ($rowMatches[1] as $rowXml) {
            $cells = [];
            if (preg_match_all('/<Cell[^>]*>(.*?)<\/Cell>/s', $rowXml, $cellMatches)) {
                foreach ($cellMatches[1] as $cellXml) {
                    $type = 'String';
                    if (preg_match('/ss:Type="(\w+)"/', $cellXml, $tm)) $type = $tm[1];
                    $val = '';
                    if (preg_match('/<Data[^>]*>(.*?)<\/Data>/s', $cellXml, $dm)) $val = $dm[1];
                    $val = html_entity_decode($val, ENT_QUOTES, 'UTF-8');
                    $cells[] = ($type === 'Number') ? (is_numeric($val) ? 0 + $val : $val) : $val;
                }
            }
            $rows[] = $cells;
        }
        return $rows;
    }

    private function parseHtmlTable(string $content): array
    {
        $rows = [];
        if (!preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $content, $rowMatches)) return [];
        foreach ($rowMatches[1] as $rowHtml) {
            $cells = [];
            if (preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $rowHtml, $cellMatches)) {
                foreach ($cellMatches[1] as $cellHtml) {
                    $val = trim(strip_tags($cellHtml));
                    $val = html_entity_decode($val, ENT_QUOTES, 'UTF-8');
                    $cells[] = is_numeric($val) ? 0 + $val : $val;
                }
            }
            if ($cells) $rows[] = $cells;
        }
        return $rows;
    }

    private function parseCsv(string $content): array
    {
        $rows = [];
        $lines = preg_split('/\r\n|\r|\n/', $content);
        foreach ($lines as $line) {
            if ($line === '') continue;
            // deteksi delimiter , atau ;
            $delim = substr_count($line, ';') > substr_count($line, ',') ? ';' : ',';
            $rows[] = str_getcsv($line, $delim, '"', '\\');
        }
        return $rows;
    }
}
