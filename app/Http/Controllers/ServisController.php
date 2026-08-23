<?php

namespace App\Http\Controllers;

use App\Models\Servis;
use App\Models\Pelanggan;
use App\Models\Teknisi;
use App\Models\Stok;
use App\Models\User;
use App\Models\Setting;
use App\Models\Kas;
use App\Services\AuditLogService;
use App\Services\SparepartMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServisController extends Controller
{
    public function index(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();
        $query = Servis::with(['pelanggan', 'teknisi', 'cabang']);
        if ($cabangId !== null) {
            $query->where('cabang_id', $cabangId);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('kode', 'like', "%$s%")
                    ->orWhere('perangkat', 'like', "%$s%")
                    ->orWhere('imei', 'like', "%$s%")
                    ->orWhereHas('pelanggan', fn ($q) => $q->where('nama', 'like', "%$s%")->orWhere('no_hp', 'like', "%$s%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('sumber')) {
            $query->where('sumber', $request->sumber);
        }

        $servis = $query->orderBy('created_at', 'desc')->paginate(20);
        return view('servis.index', compact('servis'));
    }

    public function create()
    {
        $user = auth()->user();
        $cabangId = $user->getActiveCabangId();

        // Sparepart TIDAK boleh campur antar toko:
        // Super Admin mode "Semua Cabang" wajib pilih toko dulu
        if ($cabangId === null) {
            return view('stok.pilih-cabang', ['redirectTo' => route('servis.create')]);
        }

        // Pelanggan dari cabang ini: cabang_id di tabel pelanggan ATAU pernah servis di cabang ini
        $pelanggansQuery = Pelanggan::query();
        $pelanggansQuery->where(function ($q) use ($cabangId) {
            $q->where('cabang_id', $cabangId)
              ->orWhereHas('servis', fn($sq) => $sq->where('cabang_id', $cabangId))
              ->orWhereHas('user', fn($sq) => $sq->where('cabang_id', $cabangId));
        });
        $pelanggans = $pelanggansQuery->orderBy('nama')->get();

        $teknisis = Teknisi::where('aktif', true);
        $teknisis = $teknisis->where('cabang_id', $cabangId)->get();

        $spareparts = Stok::where('stok', '>', 0)
            ->where('cabang_id', $cabangId)
            ->orderBy('nama')->get();

        $nextKode = $this->generateKode();
        return view('servis.create', compact('pelanggans', 'teknisis', 'nextKode', 'spareparts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'no_hp' => 'required',
            'nama' => 'required',
            'alamat' => 'nullable',
            'perangkat' => 'required',
            'tipe' => 'required|in:Apple,Android',
            'imei' => 'nullable|max:20',
            'keluhan' => 'required',
            'biaya' => 'numeric|min:0',
            'dp' => 'numeric|min:0',
            'status' => 'in:Masuk,Proses,Pending,Selesai,Dibatalkan',
            'prioritas' => 'in:Normal,Urgent',
            'teknisi_id' => 'nullable|exists:teknisis,id',
            'garansi' => 'integer|min:0',
            'catatan' => 'nullable',
            'eta' => 'nullable|date',
            'foto.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'sparepart_ids' => 'nullable|array',
            'sparepart_ids.*' => 'nullable',
            'sparepart_prices' => 'nullable|array',
            'sparepart_prices.*' => 'nullable|numeric|min:0',
            'sparepart_qtys' => 'nullable|array',
            'sparepart_qtys.*' => 'nullable|integer|min:1',
        ]);

        // Filter empty sparepart_ids sebelum diproses
        if ($request->has('sparepart_ids')) {
            $request->merge([
                'sparepart_ids' => array_values(array_filter($request->sparepart_ids, fn($v) => $v !== '' && $v !== null)),
            ]);
        }
        if ($request->has('sparepart_qtys')) {
            $request->merge([
                'sparepart_qtys' => array_values(array_filter($request->sparepart_qtys, fn($v) => $v !== '' && $v !== null)),
            ]);
        }

        // Auto-create or find pelanggan
        $pelanggan = $this->findOrCreatePelanggan(
            $validated['no_hp'],
            $validated['nama'],
            $validated['alamat'] ?? null
        );

        $kode = $this->generateKode();

        $tanggalGaransi = null;
        if ($validated['status'] === 'Selesai' && (int) $validated['garansi'] > 0) {
            $tanggalGaransi = now()->addDays((int) $validated['garansi'])->format('Y-m-d');
        }

        // Handle foto upload
        $fotoPaths = [];
        if ($request->hasFile('foto')) {
            foreach ($request->file('foto') as $foto) {
                $fotoPaths[] = $foto->store('servis-foto', 'public');
            }
        }

        // Handle spareparts
        $spareparts = [];
        $modalSparepart = 0;
        if ($request->filled('sparepart_ids') && auth()->user()->isAdmin()) {
            foreach ($request->sparepart_ids as $idx => $spId) {
                if ($spId) {
                    $sp = Stok::find($spId);
                    if ($sp) {
                        // Guard: sparepart harus milik cabang aktif — jangan kurangi stok toko lain
                        $aktifCabang = auth()->user()->getActiveCabangId();
                        $milikSparepart = $aktifCabang === null // super admin 'all' (akses penuh)
                            || (int) ($sp->cabang_id ?? 0) === (int) $aktifCabang
                            || ($sp->cabang_id === null && (int) auth()->user()->getEffectiveCabangId() === 1);
                        if (!$milikSparepart) {
                            return back()->withInput()->with('error', "Sparepart {$sp->nama} bukan milik cabang Anda.");
                        }

                        $harga = (float) ($request->sparepart_prices[$idx] ?? $sp->jual);
                        $qty = (int) ($request->sparepart_qtys[$idx] ?? 1);
                        $spareparts[] = [
                            'id'    => $sp->id,
                            'nama'  => $sp->nama,
                            'kode'  => $sp->kode,
                            'harga' => $harga,
                            'qty'   => $qty,
                        ];
                        $modalSparepart += (float) $sp->modal * $qty;

                        // Kurangi stok sesuai qty
                        $sp->decrement('stok', $qty);

                        // Catat pergerakan stok (Kartu Stok): pemakaian sparepart untuk servis
                        SparepartMovementService::record($sp, 'keluar', 'pemakaian_servis', $qty, [
                            'referensi'   => $kode,
                            'harga_satuan'=> $harga,
                            'cabang_id'   => auth()->user()->getActiveCabangId(),
                            'catatan'     => 'Sparepart untuk servis ' . $kode,
                        ]);
                    }
                }
            }
        }

        $servis = Servis::create([
            'kode' => $kode,
            'pelanggan_id' => $pelanggan->id,
            'cabang_id' => auth()->user()->getActiveCabangId(),
            'sumber' => 'admin',
            'perangkat' => $validated['perangkat'],
            'keluhan' => $validated['keluhan'],
            'tipe' => $validated['tipe'],
            'status' => $validated['status'] ?? 'Masuk',
            'biaya' => $validated['biaya'] ?? 0,
            'dp' => $validated['dp'] ?? 0,
            'modal_sparepart' => $modalSparepart,
            'tanggal' => now()->format('Y-m-d'),
            'teknisi_id' => $validated['teknisi_id'] ?? null,
            'prioritas' => $validated['prioritas'] ?? 'Normal',
            'imei' => $validated['imei'] ?? null,
            'catatan' => $validated['catatan'] ?? null,
            'garansi' => $validated['garansi'] ?? 30,
            'eta' => $validated['eta'] ?? null,
            'tanggal_garansi' => $tanggalGaransi,
            'foto' => $fotoPaths ?: null,
            'spareparts' => $spareparts ?: null,
        ]);

        AuditLogService::created('servis', "Menambahkan servis baru: {$kode} - {$validated['perangkat']}", $servis);

        // Auto-catat DP ke Kas Harian jika ada
        $dp = $validated['dp'] ?? 0;
        if ($dp > 0) {
            $cabangId = auth()->user()->getActiveCabangId();
            $lastKas = \App\Models\Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
            $lastSaldo = $lastKas ? $lastKas->saldo : 0;
            $newSaldo = $lastSaldo + $dp;
            \App\Models\Kas::create([
                'tipe' => 'masuk',
                'cabang_id' => $cabangId,
                'jml' => $dp,
                'kategori' => 'DP Servis',
                'ket' => "DP Servis {$kode} — {$validated['perangkat']}",
                'metode' => 'Cash',
                'ref' => $kode,
                'waktu' => now(),
                'saldo' => $newSaldo,
            ]);
            AuditLogService::log('kas', 'create', "Auto-catat DP servis {$kode}: Rp " . number_format($dp));
        }

        // Kirim notifikasi WhatsApp ke pelanggan
        $this->sendWhatsAppNotification($servis);

        return redirect()->route('servis.index')->with('success', "Servis $kode berhasil ditambahkan!");
    }

    public function show(Servis $servis)
    {
        $servis->load(['pelanggan', 'teknisi', 'cabang']);
        return view('servis.show', compact('servis'));
    }

    public function detailJson(Servis $servis)
    {
        $servis->load(['pelanggan', 'teknisi', 'cabang']);

        // Total harga sparepart (informatif — dipakai laporan keuangan untuk memisah laba jasa vs sparepart)
        $totalSparepart = 0;
        if (is_array($servis->spareparts)) {
            foreach ($servis->spareparts as $sp) {
                $totalSparepart += (float) ($sp['harga'] ?? 0) * (int) ($sp['qty'] ?? 1);
            }
        }
        // biaya = harga KESELURUHAN (sudah termasuk sparepart). Sparepart TIDAK ditambah lagi.
        $grandTotal = (float) $servis->biaya;
        $sisa = max(0, $grandTotal - (float) $servis->dp);

        return response()->json([
            'id' => $servis->id,
            'kode' => $servis->kode,
            'tanggal' => $servis->tanggal?->format('d/m/Y'),
            'cabang' => $servis->cabang?->nama ?? '-',
            'sumber' => $servis->sumber,
            'status' => $servis->status,
            'prioritas' => $servis->prioritas,
            'perangkat' => $servis->perangkat,
            'tipe' => $servis->tipe,
            'imei' => $servis->imei ?? '-',
            'keluhan' => $servis->keluhan,
            'catatan' => $servis->catatan,
            'biaya' => $servis->biaya,
            'dp' => $servis->dp,
            'total_sparepart' => $totalSparepart,
            'grand_total' => $grandTotal,
            'sisa' => $sisa,
            'garansi' => $servis->garansi,
            'tanggal_garansi' => $servis->tanggal_garansi?->format('d/m/Y') ?? '-',
            'teknisi' => $servis->teknisi?->nama ?? '-',
            'pelanggan_nama' => $servis->pelanggan?->nama ?? '-',
            'pelanggan_hp' => $servis->pelanggan?->no_hp ?? '-',
            'pelanggan_alamat' => $servis->pelanggan?->alamat ?? '-',
            'biaya_formatted' => formatRp($servis->biaya),
            'dp_formatted' => formatRp($servis->dp),
            'total_sparepart_formatted' => formatRp($totalSparepart),
            'grand_total_formatted' => formatRp($grandTotal),
            'sisa_formatted' => formatRp($sisa),
            'spareparts' => $servis->spareparts ?? [],
            'diambil' => $servis->diambil,
            'tgl_diambil' => $servis->tgl_diambil?->format('d/m/Y H:i'),
            'alasan_pembatalan' => $servis->alasan_pembatalan,
            'dibatalkan_pada' => $servis->dibatalkan_pada?->format('d/m/Y H:i'),
        ]);
    }

    public function edit(Servis $servis)
    {
        $user = auth()->user();
        $cabangId = $user->getActiveCabangId();

        // Sparepart TIDAK boleh campur antar toko:
        // Super Admin mode "Semua Cabang" wajib pilih toko dulu
        if ($cabangId === null) {
            return view('stok.pilih-cabang', ['redirectTo' => route('servis.edit', $servis)]);
        }

        // Pelanggan dari cabang ini: cabang_id di tabel pelanggan ATAU pernah servis di cabang ini
        $pelanggansQuery = Pelanggan::query();
        if ($cabangId !== null) {
            $pelanggansQuery->where(function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId)
                  ->orWhereHas('servis', fn($sq) => $sq->where('cabang_id', $cabangId))
                  ->orWhereHas('user', fn($sq) => $sq->where('cabang_id', $cabangId));
            });
        }
        $pelanggans = $pelanggansQuery->orderBy('nama')->get();

        $teknisis = Teknisi::where('aktif', true);
        if ($cabangId !== null) $teknisis->where('cabang_id', $cabangId);
        $teknisis = $teknisis->get();

        $spareparts = Stok::where('stok', '>', 0);
        if ($cabangId !== null) $spareparts->where('cabang_id', $cabangId);
        $spareparts = $spareparts->orderBy('nama')->get();

        $servis->load(['pelanggan', 'teknisi']);
        return view('servis.edit', compact('servis', 'pelanggans', 'teknisis', 'spareparts'));
    }

    public function update(Request $request, Servis $servis)
    {
        $validated = $request->validate([
            'perangkat' => 'required',
            'tipe' => 'required|in:Apple,Android',
            'imei' => 'nullable|max:20',
            'keluhan' => 'required',
            'biaya' => 'nullable|numeric|min:0',
            'dp' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:Masuk,Proses,Pending,Selesai,Dibatalkan',
            'prioritas' => 'nullable|in:Normal,Urgent',
            'teknisi_id' => 'nullable|exists:teknisis,id',
            'garansi' => 'nullable|integer|min:0',
            'catatan' => 'nullable',
            'foto.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'sparepart_ids' => 'nullable|array',
            'sparepart_ids.*' => 'nullable',
            'sparepart_prices' => 'nullable|array',
            'sparepart_prices.*' => 'nullable|numeric|min:0',
            'sparepart_qtys' => 'nullable|array',
            'sparepart_qtys.*' => 'nullable|integer|min:1',
        ]);

        // Filter empty sparepart_ids sebelum diproses
        if ($request->has('sparepart_ids')) {
            $request->merge([
                'sparepart_ids' => array_values(array_filter($request->sparepart_ids, fn($v) => $v !== '' && $v !== null)),
            ]);
        }
        if ($request->has('sparepart_qtys')) {
            $request->merge([
                'sparepart_qtys' => array_values(array_filter($request->sparepart_qtys, fn($v) => $v !== '' && $v !== null)),
            ]);
        }

        $tanggalGaransi = $servis->tanggal_garansi;
        if ($validated['status'] === 'Selesai' && (int) ($validated['garansi'] ?? $servis->garansi) > 0 && !$servis->tanggal_garansi) {
            $tanggalGaransi = now()->addDays((int) ($validated['garansi'] ?? $servis->garansi))->format('Y-m-d');
        }

        // Handle foto upload
        $existingFoto = $servis->foto ?? [];
        if ($request->hasFile('foto')) {
            foreach ($request->file('foto') as $foto) {
                $existingFoto[] = $foto->store('servis-foto', 'public');
            }
        }

        // Handle spareparts (admin only)
        $spareparts = $servis->spareparts ?? [];
        $modalSparepart = $servis->modal_sparepart ?? 0;

        if ($request->filled('sparepart_ids') && auth()->user()->isAdmin()) {
            // 1. Kembalikan stok sparepart LAMA ke inventory (agar tidak dobel/cekak)
            $oldSpareparts = $servis->spareparts ?? [];
            if (!empty($oldSpareparts)) {
                foreach ($oldSpareparts as $old) {
                    $stokLama = Stok::find($old['id'] ?? null);
                    if ($stokLama) {
                        $qtyKembali = (int) ($old['qty'] ?? 1);
                        $stokLama->increment('stok', $qtyKembali);
                        SparepartMovementService::record($stokLama, 'masuk', 'koreksi_edit_servis', $qtyKembali, [
                            'referensi' => 'EDIT-' . $servis->kode,
                            'cabang_id' => $servis->cabang_id,
                            'catatan'   => 'Koreksi stok saat edit servis: ' . $servis->kode,
                        ]);
                    }
                }
            }

            // 2. Bangun daftar sparepart BARU & kurangi stok sesuai qty
            $spareparts = [];
            $modalSparepart = 0;
            foreach ($request->sparepart_ids as $idx => $spId) {
                if ($spId) {
                    $sp = Stok::find($spId);
                    if ($sp) {
                        // Guard: sparepart harus milik cabang aktif — jangan kurangi stok toko lain
                        $aktifCabang = auth()->user()->getActiveCabangId();
                        $milikSparepart = $aktifCabang === null // super admin 'all' (akses penuh)
                            || (int) ($sp->cabang_id ?? 0) === (int) $aktifCabang
                            || ($sp->cabang_id === null && (int) auth()->user()->getEffectiveCabangId() === 1);
                        if (!$milikSparepart) {
                            return back()->withInput()->with('error', "Sparepart {$sp->nama} bukan milik cabang Anda.");
                        }

                        $harga = (float) ($request->sparepart_prices[$idx] ?? $sp->jual);
                        $qty = (int) ($request->sparepart_qtys[$idx] ?? 1);
                        $spareparts[] = [
                            'id'    => $sp->id,
                            'nama'  => $sp->nama,
                            'kode'  => $sp->kode,
                            'harga' => $harga,
                            'qty'   => $qty,
                        ];
                        $modalSparepart += (float) $sp->modal * $qty;

                        // Kurangi stok sparepart yang dipakai
                        $sp->decrement('stok', $qty);
                        SparepartMovementService::record($sp, 'keluar', 'pemakaian_servis', $qty, [
                            'referensi'    => 'EDIT-' . $servis->kode,
                            'harga_satuan' => $harga,
                            'cabang_id'    => $servis->cabang_id,
                            'catatan'      => 'Sparepart untuk servis (edit) ' . $servis->kode,
                        ]);
                    }
                }
            }
        }

        $servis->update([
            'perangkat' => $validated['perangkat'],
            'keluhan' => $validated['keluhan'],
            'tipe' => $validated['tipe'],
            'status' => $validated['status'] ?? $servis->status,
            'biaya' => $validated['biaya'] ?? $servis->biaya,
            'dp' => $validated['dp'] ?? $servis->dp,
            'teknisi_id' => $validated['teknisi_id'],
            'prioritas' => $validated['prioritas'] ?? $servis->prioritas,
            'imei' => $validated['imei'],
            'catatan' => $validated['catatan'],
            'garansi' => $validated['garansi'] ?? $servis->garansi,
            'tanggal_garansi' => $tanggalGaransi,
            'diambil' => $servis->diambil,
            'tgl_diambil' => $servis->tgl_diambil,
            'foto' => $existingFoto ?: null,
            'spareparts' => $spareparts ?: null,
            'modal_sparepart' => $modalSparepart,
        ]);

        AuditLogService::updated('servis', "Mengupdate servis {$servis->kode} → status: {$validated['status']}", $servis);

        return redirect()->route('servis.index')->with('success', "Servis {$servis->kode} berhasil diupdate!");
    }

    public function destroy(Servis $servis)
    {
        // Hanya Super Admin yang bisa hapus servis
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang bisa menghapus data servis.');
        }

        // Kembalikan stok sparepart jika ada
        if ($servis->spareparts) {
            foreach ($servis->spareparts as $sp) {
                $stok = Stok::find($sp['id'] ?? null);
                if ($stok) {
                    $qtyKembali = (int) ($sp['qty'] ?? 1);
                    $stok->increment('stok', $qtyKembali);
                    // Catat pergerakan stok (Kartu Stok): sparepart dikembalikan
                    SparepartMovementService::record($stok, 'masuk', 'batal_pemakaian_servis', $qtyKembali, [
                        'referensi'   => $servis->kode,
                        'cabang_id'   => $servis->cabang_id,
                        'catatan'     => 'Servis dihapus: ' . $servis->kode,
                    ]);
                }
            }
        }

        AuditLogService::deleted('servis', "Menghapus servis {$servis->kode}", $servis);
        $servis->delete();
        return redirect()->route('servis.index')->with('success', 'Servis berhasil dihapus!');
    }

    /**
     * Hapus banyak data servis sekaligus (bulk delete).
     * Akses: Super Admin. Setiap item stok sparepart dikembalikan.
     */
    public function bulkDestroy(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang bisa menghapus data servis.');
        }

        $ids = $request->input('ids', []);
        if (!is_array($ids)) $ids = [$ids];
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return redirect()->route('servis.index')->with('error', 'Tidak ada item yang dipilih untuk dihapus.');
        }

        $servisList = Servis::whereIn('id', $ids)->get();
        $count = 0;
        foreach ($servisList as $servis) {
            // Kembalikan stok sparepart jika ada
            if ($servis->spareparts) {
                foreach ($servis->spareparts as $sp) {
                    $stok = Stok::find($sp['id'] ?? null);
                    if ($stok) {
                        $qtyKembali = (int) ($sp['qty'] ?? 1);
                        $stok->increment('stok', $qtyKembali);
                        // Catat pergerakan stok (Kartu Stok)
                        SparepartMovementService::record($stok, 'masuk', 'batal_pemakaian_servis', $qtyKembali, [
                            'referensi'   => $servis->kode,
                            'cabang_id'   => $servis->cabang_id,
                            'catatan'     => 'Servis dihapus (bulk): ' . $servis->kode,
                        ]);
                    }
                }
            }
            AuditLogService::deleted('servis', "Menghapus servis {$servis->kode}", $servis);
            $servis->delete();
            $count++;
        }

        return redirect()->route('servis.index')
            ->with('success', $count . ' data servis berhasil dihapus!');
    }

    /**
     * Quick change status dari halaman daftar servis
     */
    public function quickStatus(Request $request, Servis $servis)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isStaff() && !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'status' => 'required|in:Masuk,Proses,Pending,Selesai',
        ]);

        $oldStatus = $servis->status;
        $newStatus = $request->status;

        // Jika status berubah ke Selesai, set garansi tapi JANGAN set diambil
        $tanggalGaransi = $servis->tanggal_garansi;
        if ($newStatus === 'Selesai' && (int) $servis->garansi > 0 && !$servis->tanggal_garansi) {
            $tanggalGaransi = now()->addDays((int) $servis->garansi)->format('Y-m-d');
        }

        $servis->update([
            'status' => $newStatus,
            'tanggal_garansi' => $tanggalGaransi,
        ]);

        AuditLogService::updated('servis', "Quick status {$servis->kode}: {$oldStatus} → {$newStatus}", $servis);

        return response()->json([
            'success' => true,
            'message' => "Status {$servis->kode} diubah: {$oldStatus} → {$newStatus}",
            'status' => $newStatus,
        ]);
    }

    public function konfirmasiDiambil(Servis $servis)
    {
        if ($servis->status !== 'Selesai') {
            return response()->json(['success' => false, 'message' => 'Hanya servis dengan status Selesai yang bisa dikonfirmasi diambil.'], 400);
        }

        if ($servis->diambil) {
            return response()->json(['success' => false, 'message' => 'HP ini sudah dikonfirmasi diambil sebelumnya.'], 400);
        }

        // Auto-catat sisa bayar ke Kas jika ada
        // biaya = harga KESELURUHAN (sudah termasuk sparepart). Sparepart TIDAK ditambah lagi.
        $grandTotal = (float) $servis->biaya;
        $sisa = max(0, $grandTotal - (float) $servis->dp);
        if ($sisa > 0) {
            $cabangId = $servis->cabang_id ?? auth()->user()->getActiveCabangId();
            $lastKas = \App\Models\Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
            $lastSaldo = $lastKas ? $lastKas->saldo : 0;
            $newSaldo = $lastSaldo + $sisa;
            \App\Models\Kas::create([
                'tipe' => 'masuk',
                'cabang_id' => $cabangId,
                'jml' => $sisa,
                'kategori' => 'Pelunasan Servis',
                'ket' => "Pelunasan servis {$servis->kode} — {$servis->perangkat}",
                'metode' => 'Cash',
                'ref' => $servis->kode,
                'waktu' => now(),
                'saldo' => $newSaldo,
            ]);
            AuditLogService::log('kas', 'create', "Auto-catat pelunasan servis {$servis->kode}: Rp " . number_format($sisa));
        }

        $servis->update([
            'diambil' => true,
            'tgl_diambil' => now(),
        ]);

        AuditLogService::updated('servis', "Konfirmasi HP diambil: {$servis->kode}" . ($sisa > 0 ? " — pelunasan Rp " . number_format($sisa) : ''), $servis);

        return response()->json(['success' => true, 'message' => "HP untuk servis {$servis->kode} sudah dikonfirmasi diambil." . ($sisa > 0 ? " Pelunasan Rp " . number_format($sisa) . " dicatat ke Kas." : '')]);
    }

    public function batal(Request $request, Servis $servis)
    {
        $user = auth()->user();

        // Hanya Admin dan Staff yang bisa membatalkan
        if (!$user->isAdmin() && !$user->isStaff() && !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses untuk membatalkan transaksi.'], 403);
        }

        if ($servis->status === 'Dibatalkan') {
            return response()->json(['success' => false, 'message' => 'Transaksi ini sudah dibatalkan.'], 400);
        }

        $request->validate([
            'alasan' => 'required|string|min:3|max:500',
        ]);

        // Kembalikan stok sparepart jika ada
        if ($servis->spareparts) {
            foreach ($servis->spareparts as $sp) {
                $stok = Stok::find($sp['id'] ?? null);
                if ($stok) {
                    $qtyKembali = (int) ($sp['qty'] ?? 1);
                    $stok->increment('stok', $qtyKembali);
                    // Catat pergerakan stok (Kartu Stok)
                    SparepartMovementService::record($stok, 'masuk', 'batal_pemakaian_servis', $qtyKembali, [
                        'referensi'   => 'BATAL-' . $servis->kode,
                        'cabang_id'   => $servis->cabang_id,
                        'catatan'     => 'Pembatalan servis: ' . $request->alasan,
                    ]);
                }
            }
        }

        // Koreksi Kas: kembalikan DP jika sudah masuk kas
        $dpDikembalikan = 0;
        if ($servis->dp > 0) {
            $cabangId = $servis->cabang_id;
            $lastKas = Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
            $lastSaldo = $lastKas ? $lastKas->saldo : 0;
            $dpDikembalikan = $servis->dp;
            Kas::create([
                'tipe' => 'keluar',
                'cabang_id' => $cabangId,
                'jml' => $servis->dp,
                'kategori' => 'Pembatalan DP Servis',
                'ket' => "Pengembalian DP servis {$servis->kode} (dibatalkan)",
                'metode' => 'Cash',
                'ref' => 'BATAL-DP-' . $servis->kode,
                'waktu' => now(),
                'saldo' => $lastSaldo - $servis->dp,
            ]);
        }

        $servis->update([
            'status' => 'Dibatalkan',
            'alasan_pembatalan' => $request->alasan,
            'dibatalkan_oleh' => $user->id,
            'dibatalkan_pada' => now(),
        ]);

        AuditLogService::log('servis', 'batal', "Membatalkan servis {$servis->kode}. Alasan: {$request->alasan}", $servis);

        return response()->json(['success' => true, 'message' => "Transaksi servis {$servis->kode} berhasil dibatalkan."]);
    }

    /**
     * Generate / download PDF Nota Servis (A4).
     */
    public function notaPdf(Servis $servis)
    {
        $servis->load(['pelanggan', 'teknisi', 'cabang']);
        $data = $this->buildNotaData($servis);

        $html = view('nota.servis-pdf', $data)->render();

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Nota_' . $servis->kode . '.pdf';
        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    /**
     * Siapkan PDF Nota, simpan ke storage publik, kembalikan path + URL absolut.
     */
    private function generateNotaFile(Servis $servis): array
    {
        $servis->load(['pelanggan', 'teknisi', 'cabang']);
        $data = $this->buildNotaData($servis);
        $html = view('nota.servis-pdf', $data)->render();

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Nota_' . $servis->kode . '.pdf';
        $relPath = 'nota-servis/' . $filename;
        // Simpan langsung ke folder public/storage (web-accessible) supaya Fonnte bisa fetch
        $dir = public_path('storage/nota-servis');
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        file_put_contents($dir . '/' . $filename, $dompdf->output());

        $baseUrl = rtrim(config('app.url'), '/');
        return [
            'relative' => 'storage/nota-servis/' . $filename,
            'url'      => $baseUrl . '/storage/nota-servis/' . $filename,
            'filename' => $filename,
        ];
    }

    /**
     * Kumpulkan data untuk view nota (dipakai PDF stream & generate file).
     */
    private function buildNotaData(Servis $servis): array
    {
        $cabang = $servis->cabang;
        $cabangId = $cabang?->id ?? 1;

        $namaToko = Setting::get("nama_toko_{$cabangId}") ?? Setting::get('nama_toko') ?? ($cabang?->nama ?? 'FIXPRO');
        $alamat   = Setting::get("alamat_{$cabangId}") ?? Setting::get('alamat') ?? '';
        $telp     = Setting::get("telp_{$cabangId}") ?? Setting::get('telp') ?? '';

        // QRIS: cari path fisik file gambar untuk di-embed ke PDF
        $qrisPath = null;
        $qrisStored = Setting::get("qris_image_{$cabangId}") ?? Setting::get('qris_image');
        if ($qrisStored) {
            $candidate = storage_path('app/public/' . $qrisStored);
            if (file_exists($candidate)) {
                $qrisPath = $candidate;
            } else {
                // kemungkinan disimpan langsung di public/storage
                $candidate2 = public_path('storage/' . $qrisStored);
                if (file_exists($candidate2)) $qrisPath = $candidate2;
            }
        }

        return [
            'servis'   => $servis,
            'settings' => [
                'nama_toko' => $namaToko,
                'alamat'    => $alamat,
                'telp'      => $telp,
                'tagline'   => Setting::get("tagline_{$cabangId}") ?? Setting::get('tagline') ?? 'SMARTPHONE SERVICE CENTER',
                'slogan'    => Setting::get("slogan_{$cabangId}") ?? Setting::get('slogan') ?? 'Smart. Fast. Reliable.',
            ],
            'qrisPath' => $qrisPath,
        ];
    }

    /**
     * Generate isi pesan WhatsApp untuk nota servis.
     */
    private function buildWaNotaMessage(Servis $servis): string
    {
        $sisa = max(0, (float) $servis->biaya - (float) $servis->dp);
        $cabang = $servis->cabang;
        $cabangId = $cabang?->id ?? 1;
        $namaToko = Setting::get("nama_toko_{$cabangId}") ?? Setting::get('nama_toko') ?? ($cabang?->nama ?? 'FIXPRO');
        $telp = Setting::get("telp_{$cabangId}") ?? Setting::get('telp') ?? '';

        $spList = '';
        if ($servis->spareparts) {
            foreach ($servis->spareparts as $sp) {
                $spList .= "\n   • " . ($sp['nama'] ?? '-') . " — Rp " . number_format($sp['harga'] ?? 0, 0, ',', '.');
            }
        }

        $msg = "*NOTA SERVIS — " . $namaToko . "*\n";
        $msg .= "━━━━━━━━━━━━━━━━━\n";
        $msg .= "Halo Kak " . ($servis->pelanggan?->nama ?? '-') . ", 🙏\n\n";
        $msg .= "Servis HP Anda sudah *SELESAI*. Berikut detail tagihannya:\n\n";
        $msg .= "📋 *Detail Servis*\n";
        $msg .= "• Kode: " . $servis->kode . "\n";
        $msg .= "• Perangkat: " . $servis->perangkat . "\n";
        $msg .= "• Keluhan: " . $servis->keluhan . "\n";
        $msg .= "• Teknisi: " . ($servis->teknisi?->nama ?? '-') . "\n";
        if ($servis->garansi) {
            $msg .= "• Garansi: " . $servis->garansi . " hari";
            if ($servis->tanggal_garansi) $msg .= " (s/d " . $servis->tanggal_garansi->format('d/m/Y') . ")";
            $msg .= "\n";
        }
        if ($spList) $msg .= "• Sparepart:" . $spList . "\n";
        $msg .= "\n💰 *Rincian Pembayaran*\n";
        $msg .= "• Biaya Servis: Rp " . number_format($servis->biaya, 0, ',', '.') . "\n";
        if ($servis->dp > 0) {
            $msg .= "• DP dibayar: Rp " . number_format($servis->dp, 0, ',', '.') . "\n";
        }
        $msg .= "• *SISA BAYAR: Rp " . number_format($sisa, 0, ',', '.') . "*\n\n";
        $msg .= "📎 *Nota PDF terlampir* — mohon dicek.\n\n";
        $msg .= "Silakan ambil HP Anda di *" . ($cabang?->nama ?? $namaToko) . "*.\n";
        if ($telp) $msg .= "Tanya-tanya: " . $telp . "\n";
        $msg .= "\nTerima kasih! 🙏";
        return $msg;
    }

    /**
     * API: kirim nota servis ke WhatsApp pelanggan (auto via Fonnte, text + PDF).
     * Boleh juga via tombol manual (wa.me) — frontend yang handle.
     */
    public function kirimWaNota(Request $request, Servis $servis)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isStaff() && !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $phone = $servis->pelanggan?->no_hp;
        if (empty($phone)) {
            return response()->json(['success' => false, 'message' => 'Nomor HP pelanggan tidak ditemukan.']);
        }

        // Format nomor HP Indonesia
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '+62')) {
            $phone = substr($phone, 1);
        } elseif (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        $waApiKey = Setting::get('wa_api_key_' . ($servis->cabang_id ?? 1)) ?? Setting::get('wa_api_key');
        if (empty($waApiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'API WhatsApp (Fonnte) belum dikonfigurasi. Gunakan tombol Buka WhatsApp Manual + Download PDF.',
                'manual_mode' => true,
            ]);
        }

        // Generate PDF & simpan ke storage publik
        try {
            $file = $this->generateNotaFile($servis);
        } catch (\Exception $e) {
            Log::error('Gagal generate nota PDF: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal membuat PDF nota: ' . $e->getMessage()]);
        }

        $message = $this->buildWaNotaMessage($servis);

        try {
            $resp = Http::withHeaders([
                'Authorization' => $waApiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post('https://api.fonnte.com/send', [
                'target'   => $phone,
                'message'  => $message,
                'document' => $file['url'],
                'filename' => $file['filename'],
            ]);

            $body = $resp->json();

            // Fonnte: { "status": true, ... } saat sukses
            if ($resp->successful() && isset($body['status']) && $body['status'] === true) {
                AuditLogService::log('whatsapp', 'send', "Kirim nota servis {$servis->kode} ke {$phone} (PDF terlampir)");
                return response()->json([
                    'success' => true,
                    'message' => "Nota {$servis->kode} berhasil dikirim ke WhatsApp pelanggan.",
                    'pdf_url' => $file['url'],
                ]);
            }

            $reason = $body['reason'] ?? ($body['message'] ?? 'Unknown error');
            Log::warning('Fonnte kirim nota gagal', ['status' => $resp->status(), 'body' => $body]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal kirim WA: ' . $reason,
                'pdf_url' => $file['url'],
                'manual_mode' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Exception kirim WA nota: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Koneksi ke API WA gagal: ' . $e->getMessage(),
                'pdf_url' => $file['url'] ?? null,
                'manual_mode' => true,
            ]);
        }
    }

    /**
     * API: ambil preview pesan WA + URL PDF untuk modal (tanpa kirim).
     */
    public function previewWaNota(Servis $servis)
    {
        $phone = $servis->pelanggan?->no_hp ?? '';
        $message = $this->buildWaNotaMessage($servis);

        // format untuk wa.me
        $waPhone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($waPhone, '0')) $waPhone = '62' . substr($waPhone, 1);
        elseif (str_starts_with($waPhone, '+62')) $waPhone = substr($waPhone, 1);
        elseif ($waPhone && !str_starts_with($waPhone, '62')) $waPhone = '62' . $waPhone;

        return response()->json([
            'kode'        => $servis->kode,
            'pelanggan'   => $servis->pelanggan?->nama ?? '-',
            'phone'       => $phone,
            'wa_url'      => $waPhone ? ('https://wa.me/' . $waPhone . '?text=' . rawurlencode($message)) : null,
            'message'     => $message,
            'pdf_url'     => route('servis.nota-pdf', $servis),
            'fonnte_aktif'=> (bool) (Setting::get('wa_api_key_' . ($servis->cabang_id ?? 1)) ?? Setting::get('wa_api_key')),
        ]);
    }

    private function generateKode(): string
    {
        $date = now()->format('ymd');
        $last = Servis::where('kode', 'like', "SVC-$date-%")->orderBy('id', 'desc')->first();
        $num = $last ? (int) substr($last->kode, -3) + 1 : 1;
        return "SVC-$date-" . str_pad($num, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Kirim notifikasi WhatsApp ke pelanggan via Fonnte API
     */
    private function sendWhatsAppNotification(Servis $servis): void
    {
        try {
            $waApiKey = \App\Models\Setting::get('wa_api_key_' . ($servis->cabang_id ?? 1)) ?? \App\Models\Setting::get('wa_api_key');
            if (empty($waApiKey)) return; // Tidak ada API key, skip

            $phone = $servis->pelanggan?->no_hp;
            if (empty($phone)) return;

            // Format nomor HP
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            }

            $template = \App\Models\Setting::get('wa_template');
            if (empty($template)) {
                $template = "Halo {nama},\n\nServis HP Anda dengan kode {kode} sudah kami terima.\n📱 Perangkat: {perangkat}\n🔧 Keluhan: {keluhan}\n💰 Biaya: Rp {biaya}\n\nTerima kasih telah mempercayakan servis HP Anda kepada kami! 🙏";
            }

            $message = str_replace(
                ['{nama}', '{kode}', '{perangkat}', '{keluhan}', '{biaya}', '{status}', '{teknisi}'],
                [
                    $servis->pelanggan?->nama ?? '-',
                    $servis->kode,
                    $servis->perangkat,
                    $servis->keluhan,
                    number_format($servis->biaya),
                    $servis->status,
                    $servis->teknisi?->nama ?? '-',
                ],
                $template
            );

            \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => $waApiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post('https://api.fonnte.com/send', [
                'target' => $phone,
                'message' => $message,
            ]);

            AuditLogService::log('whatsapp', 'send', "Kirim WA notifikasi ke {$phone} untuk servis {$servis->kode}");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('WhatsApp notification failed: ' . $e->getMessage());
        }
    }

    /**
     * Cari atau buat pelanggan (TANPA buat akun user)
     */
    private function findOrCreatePelanggan(string $noHp, string $nama, ?string $alamat = null): Pelanggan
    {
        $cabangId = auth()->user()->getActiveCabangId();
        // Cari pelanggan di cabang ini dulu
        $pelanggan = Pelanggan::where('no_hp', $noHp)->where('cabang_id', $cabangId)->first();

        if ($pelanggan) {
            // Update nama/alamat kalau berubah
            $pelanggan->update([
                'nama' => $nama,
                'alamat' => $alamat ?? $pelanggan->alamat,
            ]);
            return $pelanggan->fresh();
        }

        // Pelanggan belum ada di cabang ini → buat baru (tanpa akun user)
        return Pelanggan::create([
            'nama' => $nama,
            'no_hp' => $noHp,
            'alamat' => $alamat,
            'cabang_id' => $cabangId,
        ]);
    }
}
