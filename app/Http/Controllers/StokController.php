<?php

namespace App\Http\Controllers;

use App\Models\Stok;
use App\Services\AuditLogService;
use App\Services\SparepartMovementService;
use App\Services\XlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // Stok SELALU milik cabang aktif saja (tidak campur toko lain)
        $query = Stok::where('cabang_id', $cabangId);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nama', 'like', "%$s%")->orWhere('kode', 'like', "%$s%")->orWhere('barcode', 'like', "%$s%");
            });
        }
        $stoks = $query->orderBy('nama')->paginate(20);

        // Stats juga harus per cabang
        $statsQuery = Stok::where('cabang_id', $cabangId);
        $totalJenis = $statsQuery->count();
        $stokLow = (clone $statsQuery)->where('stok', '>', 0)->where('stok', '<=', \DB::raw('min_alert'))->count();
        $stokHabis = (clone $statsQuery)->where('stok', 0)->count();

        return view('stok.index', compact('stoks', 'totalJenis', 'stokLow', 'stokHabis'));
    }

    public function create(Request $request)
    {
        // Wajib pilih toko dulu supaya barang baru tidak nyasar ke toko lain
        if ($gate = $this->requireCabangForStok($request)) return $gate;

        return view('stok.create');
    }

    public function store(Request $request)
    {
        // Wajib pilih toko dulu supaya barang baru tidak nyasar ke toko lain
        if ($gate = $this->requireCabangForStok($request)) return $gate;

        $cabangId = auth()->user()->getEffectiveCabangId();
        $validated = $request->validate([
            'kode' => 'required|unique:stoks,kode,NULL,id,cabang_id,' . $cabangId,
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
            ? (int) $user->cabang_id
            : $user->getActiveCabangId();
        // Auto-generate barcode if empty
        if (empty($validated['barcode'])) {
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
        AuditLogService::created('stok', "Menambahkan stok: {$validated['nama']} ({$validated['kode']})", $s);
        return redirect()->route('stok.index')->with('success', 'Barang berhasil ditambahkan!');
    }

    public function edit(Stok $stok)
    {
        $this->checkCabangAccess($stok);
        return view('stok.edit', compact('stok'));
    }

    public function update(Request $request, Stok $stok)
    {
        $this->checkCabangAccess($stok);

        $validated = $request->validate([
            'kode' => 'required|unique:stoks,kode,' . $stok->id . ',id,cabang_id,' . $stok->cabang_id,
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

        AuditLogService::updated('stok', "Mengupdate stok: {$stok->nama}", $stok);
        return redirect()->route('stok.index')->with('success', 'Barang berhasil diupdate!');
    }

    public function destroy(Stok $stok)
    {
        $this->checkCabangAccess($stok);
        AuditLogService::deleted('stok', "Menghapus stok: {$stok->nama}", $stok);
        $stok->delete();
        return redirect()->route('stok.index')->with('success', 'Barang berhasil dihapus!');
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

        DB::beginTransaction();
        try {
            for ($i = 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                $nama = trim($r[$colNama] ?? '');
                $kode = trim($r[$colKode] ?? '');
                if ($nama === '' || $kode === '') continue; // skip baris kosong

                $stok    = $colStok !== false ? (int) round((float)($r[$colStok] ?? 0)) : null;
                $modal   = $colModal !== false ? (float) ($r[$colModal] ?? 0) : null;
                $jual    = $colJual !== false ? (float) ($r[$colJual] ?? 0) : null;
                $kategori= $colKategori !== false ? trim($r[$colKategori] ?? '') : null;
                $min     = $colMin !== false ? (int) round((float)($r[$colMin] ?? 0)) : null;

                $existing = Stok::where('kode', $kode)
                    ->where('cabang_id', $cabangId)
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
                    // validasi unik kode per cabang
                    if (Stok::where('kode', $kode)->where('cabang_id', $cabangId)->exists()) {
                        $errors[] = "Baris {$i}: kode {$kode} duplikat.";
                        continue;
                    }
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
        if ($errors) $msg .= ' ' . count($errors) . ' baris dilewati.';
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
            if ($ssXml !== false && preg_match_all('/<si\b[^>]*>(.*?)<\/si>/s', $ssXml, $siMatches)) {
                foreach ($siMatches[1] as $si) {
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
            $rows = [];
            if (!preg_match_all('/<row\b[^>]*>(.*?)<\/row>/s', $sheetXml, $rowMatches)) return [];
            foreach ($rowMatches[1] as $rowInner) {
                $cells = [];
                if (preg_match_all('/<c\b([^>]*)>(?:<(?:v|is)[^>]*>(.*?)<\/(?:v|is)>)?/s', $rowInner, $cellMatches, PREG_SET_ORDER)) {
                    foreach ($cellMatches as $cm) {
                        $attrs = $cm[1];
                        $raw = $cm[2] ?? '';
                        $t = '';
                        if (preg_match('/\bt="([^"]+)"/', $attrs, $tm)) $t = $tm[1];

                        if ($t === 'inlineStr') {
                            // nilai ada di <is><t>...</t></is> (diambil raw oleh regex di atas sebagai isi <is>)
                            $val = '';
                            if (preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $raw, $tParts)) {
                                foreach ($tParts[1] as $tp) $val .= $tp;
                            }
                            $cells[] = html_entity_decode($val, ENT_QUOTES, 'UTF-8');
                        } elseif ($t === 's') {
                            // shared string by index
                            $idx = (int) $raw;
                            $cells[] = $shared[$idx] ?? '';
                        } else {
                            // number (default) atau boolean
                            $val = trim($raw);
                            $cells[] = is_numeric($val) ? 0 + $val : $val;
                        }
                    }
                }
                if (!empty($cells)) $rows[] = $cells;
            }
            return $rows;
        } finally {
            $zip->close();
        }
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
