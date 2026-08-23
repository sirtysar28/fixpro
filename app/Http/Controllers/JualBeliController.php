<?php

namespace App\Http\Controllers;

use App\Models\JualBeli;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class JualBeliController extends Controller
{
    /** Daftar merk HP populer untuk dropdown */
    private const MERK_HP = [
        'Apple iPhone', 'Samsung', 'Xiaomi', 'Oppo', 'Vivo', 'Realme',
        'Infinix', 'Tecno', 'Poco', 'Nokia', 'Huawei', 'OnePlus', 'Google Pixel',
        'Asus', 'Honor', 'Motorola', 'Lainnya',
    ];

    /**
     * Pastikan data milik cabang user yang login
     */
    private function checkCabangAccess(JualBeli $jualBeli): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) return;

        $cabangId = $user->getActiveCabangId();
        if ($jualBeli->cabang_id != $cabangId) {
            abort(403, 'Anda hanya bisa mengakses data cabang Anda sendiri.');
        }
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $cabangId = $user->getActiveCabangId();

        $query = JualBeli::with(['cabang', 'user']);
        if ($cabangId !== null) {
            $query->where('cabang_id', $cabangId);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('hp', 'like', "%$s%")
                    ->orWhere('imei', 'like', "%$s%")
                    ->orWhere('imei2', 'like', "%$s%")
                    ->orWhere('serial_number', 'like', "%$s%")
                    ->orWhere('pelanggan', 'like', "%$s%")
                    ->orWhere('kode', 'like', "%$s%")
                    ->orWhere('merk', 'like', "%$s%")
                    ->orWhere('model', 'like', "%$s%");
            });
        }
        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }
        if ($request->filled('status_unit')) {
            $query->where('status_unit', $request->status_unit);
        }
        if ($request->filled('metode')) {
            $query->where('metode_bayar', $request->metode);
        }
        if ($request->filled('date')) {
            $query->whereDate('tanggal', $request->date);
        }

        $items = $query->orderBy('created_at', 'desc')->paginate(20);

        // Hitung estimasi laba untuk setiap item (jika belum ada)
        $items->getCollection()->transform(function ($item) {
            $item->estimasi_laba_calc = $item->hitungEstimasiLaba();
            return $item;
        });

        // Stats today
        $today = now()->format('Y-m-d');
        $statsBase = JualBeli::query();
        if ($cabangId !== null) {
            $statsBase->where('cabang_id', $cabangId);
        }
        $cloneStats = fn($tipe) => (clone $statsBase)->where('status', '!=', 'Dibatalkan')->where('tipe', $tipe)->whereDate('tanggal', $today);
        $totalJual = $cloneStats('jual')->sum('harga');
        $totalBeli = $cloneStats('beli')->sum('harga');
        $totalTransaksi = (clone $statsBase)->where('status', '!=', 'Dibatalkan')->whereDate('tanggal', $today)->count();

        // Stok unit siap jual (status_unit Ready Dijual & tipe beli)
        $stokUnit = (clone $statsBase)->where('status', '!=', 'Dibatalkan')
            ->where('tipe', 'beli')->where('status_unit', 'Ready Dijual')->count();

        return view('jualbeli.index', compact('items', 'totalJual', 'totalBeli', 'totalTransaksi', 'stokUnit'));
    }

    public function create()
    {
        $merkList = self::MERK_HP;
        return view('jualbeli.create', compact('merkList'));
    }

    /** Aturan validasi untuk semua field baru (dipakai store & update) */
    private function validationRules(): array
    {
        return [
            'tanggal'             => 'required|date',
            'tipe'                => 'required|in:beli,jual',
            'hp'                  => 'nullable',
            'imei'                => 'nullable|max:20',
            'imei2'               => 'nullable|max:20',
            'serial_number'       => 'nullable|max:60',
            'merk'                => 'nullable|max:60',
            'model'               => 'nullable|max:80',
            'warna'               => 'nullable|max:40',
            'ram'                 => 'nullable|max:20',
            'kapasitas'           => 'nullable|max:20',
            'battery_health'      => 'nullable|integer|min:0|max:100',
            'harga'               => 'nullable|numeric|min:0',
            'harga_beli'          => 'nullable|numeric|min:0',
            'harga_jual'          => 'nullable|numeric|min:0',
            'metode_bayar'        => 'required|in:Cash,Transfer,QRIS',
            'pelanggan'           => 'nullable',
            'no_hp_pelanggan'     => 'nullable',
            'kondisi'             => 'nullable|in:Second,Mulus,Pemilik',
            'kelengkapan'         => 'nullable',
            'catatan'             => 'nullable',
            'checklist_kondisi'   => 'nullable|array',
            'status_pemeriksaan'  => 'nullable|in:Normal,Rusak,Belum Dicek',
            'status_unit'         => 'nullable|in:Ready Dijual,Booking,Sedang Diservis,Terjual,Retur',
            'garansi'             => 'nullable|in:Tanpa Garansi,Garansi 7 Hari,Garansi 30 Hari,Garansi 90 Hari',
            'foto_depan'          => 'nullable|image|max:4096',
            'foto_belakang'       => 'nullable|image|max:4096',
            'foto_samping'        => 'nullable|image|max:4096',
            'foto_imei'           => 'nullable|image|max:4096',
        ];
    }

    /** Gabungkan input harga & hitung modal_total, estimasi_laba, nama hp */
    private function prepareData(array $validated, ?JualBeli $existing = null): array
    {
        $tipe = $validated['tipe'];

        // Sinkronkan kolom `harga` lama dengan harga yang relevan agar laporan tetap jalan.
        if ($tipe === 'jual') {
            $hargaJual = (float) ($validated['harga_jual'] ?? $validated['harga'] ?? 0);
            $validated['harga'] = $hargaJual;
            $validated['harga_jual'] = $hargaJual;
        } else { // beli
            $hargaBeli = (float) ($validated['harga_beli'] ?? $validated['harga'] ?? 0);
            $validated['harga'] = $hargaBeli;
            $validated['harga_beli'] = $hargaBeli;
        }

        // Modal total = total biaya modal (default = harga_beli)
        $modalTotal = (float) ($validated['modal_total'] ?? $validated['harga_beli'] ?? ($existing?->modal_total ?? 0));
        if ($tipe === 'beli') {
            $modalTotal = $modalTotal > 0 ? $modalTotal : (float) ($validated['harga_beli'] ?? 0);
        }
        $validated['modal_total'] = $modalTotal;

        // Estimasi laba
        $hargaJualFinal = (float) ($validated['harga_jual'] ?? 0);
        $validated['estimasi_laba'] = $hargaJualFinal > 0 ? ($hargaJualFinal - $modalTotal) : null;

        // Auto-build nama HP bila kosong
        if (empty($validated['hp'])) {
            $parts = array_filter([
                $validated['merk'] ?? null,
                $validated['model'] ?? null,
            ]);
            $validated['hp'] = implode(' ', $parts) ?: 'HP';
        }

        // Checklist: pastikan format array
        if (!empty($validated['checklist_kondisi']) && is_array($validated['checklist_kondisi'])) {
            $validated['checklist_kondisi'] = $validated['checklist_kondisi'];
        }

        // Garansi → hitung garansi_hingga
        $garansi = $validated['garansi'] ?? 'Tanpa Garansi';
        $validated['garansi_hingga'] = JualBeli::hitungGaransiHingga($garansi);

        // Default status unit saat beli → Ready Dijual; saat jual → Terjual
        if (empty($validated['status_unit'])) {
            $validated['status_unit'] = $tipe === 'jual' ? 'Terjual' : 'Ready Dijual';
        }

        return $validated;
    }

    /** Upload satu foto unit, return path relatif */
    private function uploadFoto(Request $request, string $field): ?string
    {
        if ($request->hasFile($field) && $request->file($field)->isValid()) {
            return $request->file($field)->store('jualbeli-foto', 'public');
        }
        return null;
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules());
        $validated = $this->prepareData($validated);

        $cabangId = auth()->user()->getActiveCabangId();
        $validated['kode'] = JualBeli::generateKode();
        $validated['cabang_id'] = $cabangId;
        $validated['user_id'] = auth()->id();

        // Upload foto
        foreach (['foto_depan', 'foto_belakang', 'foto_samping', 'foto_imei'] as $f) {
            $path = $this->uploadFoto($request, $f);
            if ($path) $validated[$f] = $path;
        }

        // Riwayat harga awal
        $validated['riwayat_harga'] = [[
            'tanggal'    => now()->toDateTimeString(),
            'harga_beli' => (float) ($validated['harga_beli'] ?? 0),
            'harga_jual' => (float) ($validated['harga_jual'] ?? 0),
            'keterangan' => 'Input awal unit',
            'user_id'    => auth()->id(),
        ]];

        $jualBeli = JualBeli::create($validated);

        // Auto-catat ke Kas
        $lastKas = \App\Models\Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
        $lastSaldo = $lastKas ? $lastKas->saldo : 0;
        $tipe = $validated['tipe'];
        $nominal = (float) $validated['harga'];
        $newSaldo = $tipe === 'jual' ? $lastSaldo + $nominal : $lastSaldo - $nominal;

        \App\Models\Kas::create([
            'tipe' => $tipe === 'jual' ? 'masuk' : 'keluar',
            'cabang_id' => $cabangId,
            'jml' => $nominal,
            'kategori' => $tipe === 'jual' ? 'Jual HP Second' : 'Beli HP Second',
            'ket' => "{$validated['kode']}: {$validated['hp']}" . ($validated['pelanggan'] ? " — {$validated['pelanggan']}" : ''),
            'metode' => $validated['metode_bayar'],
            'ref' => $validated['kode'],
            'waktu' => now(),
            'saldo' => $newSaldo,
        ]);

        AuditLogService::log('jual_beli', 'create', "Transaksi {$validated['tipe']}: {$validated['hp']} — Rp " . number_format($nominal));

        return redirect()->route('jualbeli.index')->with('success', "Transaksi {$validated['kode']} berhasil! ({$validated['metode_bayar']})");
    }

    public function edit(JualBeli $jualBeli)
    {
        $this->checkCabangAccess($jualBeli);
        $merkList = self::MERK_HP;
        return view('jualbeli.edit', compact('jualBeli', 'merkList'));
    }

    public function update(Request $request, JualBeli $jualBeli)
    {
        $this->checkCabangAccess($jualBeli);
        $validated = $request->validate($this->validationRules());

        // Cek apakah harga berubah → catat ke riwayat
        $hargaBeliLama = (float) ($jualBeli->harga_beli ?? 0);
        $hargaJualLama = (float) ($jualBeli->harga_jual ?? 0);

        $validated = $this->prepareData($validated, $jualBeli);

        $hargaBeliBaru = (float) ($validated['harga_beli'] ?? $hargaBeliLama);
        $hargaJualBaru = (float) ($validated['harga_jual'] ?? $hargaJualLama);
        if ($hargaBeliBaru != $hargaBeliLama || $hargaJualBaru != $hargaJualLama) {
            $jualBeli->pushRiwayatHarga($hargaBeliBaru, $hargaJualBaru, 'Perubahan harga');
            $validated['riwayat_harga'] = $jualBeli->riwayat_harga;
        }

        // Upload foto baru (hapus yang lama jika diganti)
        foreach (['foto_depan', 'foto_belakang', 'foto_samping', 'foto_imei'] as $f) {
            $path = $this->uploadFoto($request, $f);
            if ($path) {
                if ($jualBeli->{$f} && Storage::disk('public')->exists($jualBeli->{$f})) {
                    Storage::disk('public')->delete($jualBeli->{$f});
                }
                $validated[$f] = $path;
            } else {
                unset($validated[$f]); // jangan timpa path lama
            }
        }

        $jualBeli->update($validated);
        AuditLogService::log('jual_beli', 'update', "Update transaksi {$jualBeli->kode}: {$validated['hp']}");
        return redirect()->route('jualbeli.index')->with('success', 'Transaksi berhasil diupdate!');
    }

    public function batal(Request $request, JualBeli $jualBeli)
    {
        $this->checkCabangAccess($jualBeli);

        if ($jualBeli->status === 'Dibatalkan') {
            return response()->json(['success' => false, 'message' => 'Sudah dibatalkan.'], 400);
        }

        $request->validate(['alasan' => 'required|string|min:3']);

        // Koreksi Kas
        if ($jualBeli->harga > 0) {
            $cabangId = $jualBeli->cabang_id;
            $lastKas = \App\Models\Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
            $lastSaldo = $lastKas ? $lastKas->saldo : 0;

            $isJual = $jualBeli->tipe === 'jual';
            \App\Models\Kas::create([
                'tipe' => $isJual ? 'keluar' : 'masuk',
                'cabang_id' => $cabangId,
                'jml' => $jualBeli->harga,
                'kategori' => 'Pembatalan ' . ($isJual ? 'Jual HP' : 'Beli HP'),
                'ket' => "BATAL {$jualBeli->kode}: {$jualBeli->hp}",
                'metode' => $jualBeli->metode_bayar ?? 'Cash',
                'ref' => 'BATAL-' . $jualBeli->kode,
                'waktu' => now(),
                'saldo' => $isJual ? $lastSaldo - $jualBeli->harga : $lastSaldo + $jualBeli->harga,
            ]);
        }

        $jualBeli->update([
            'status' => 'Dibatalkan',
            'alasan_pembatalan' => $request->alasan,
        ]);

        AuditLogService::log('jual_beli', 'batal', "Batalkan {$jualBeli->kode}: {$jualBeli->hp}");
        return response()->json(['success' => true, 'message' => "Transaksi {$jualBeli->kode} dibatalkan."]);
    }

    public function destroy(JualBeli $jualBeli)
    {
        $this->checkCabangAccess($jualBeli);
        $this->hapusFotoUnit($jualBeli);
        AuditLogService::log('jual_beli', 'delete', "Hapus transaksi: {$jualBeli->hp}");
        $jualBeli->delete();
        return redirect()->route('jualbeli.index')->with('success', 'Transaksi berhasil dihapus!');
    }

    /** Hapus semua foto unit dari storage */
    private function hapusFotoUnit(JualBeli $jualBeli): void
    {
        foreach (['foto_depan', 'foto_belakang', 'foto_samping', 'foto_imei'] as $f) {
            if ($jualBeli->{$f} && Storage::disk('public')->exists($jualBeli->{$f})) {
                Storage::disk('public')->delete($jualBeli->{$f});
            }
        }
    }

    /**
     * Hapus banyak transaksi jual beli sekaligus.
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids)) $ids = [$ids];
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return redirect()->route('jualbeli.index')->with('error', 'Tidak ada item yang dipilih untuk dihapus.');
        }

        $items = JualBeli::whereIn('id', $ids)->get();
        $count = 0;
        foreach ($items as $jualBeli) {
            try { $this->checkCabangAccess($jualBeli); } catch (\Exception $e) { continue; }
            $this->hapusFotoUnit($jualBeli);
            AuditLogService::log('jual_beli', 'delete', "Hapus transaksi: {$jualBeli->hp}");
            $jualBeli->delete();
            $count++;
        }

        return redirect()->route('jualbeli.index')
            ->with('success', $count . ' transaksi berhasil dihapus!');
    }
}
