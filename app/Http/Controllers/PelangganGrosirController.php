<?php

namespace App\Http\Controllers;

use App\Models\PelangganGrosir;
use App\Models\PenjualanGrosir;
use App\Services\AuditLogService;
use App\Services\GrosirService;
use Illuminate\Http\Request;

class PelangganGrosirController extends Controller
{
    public function index(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return view('stok.pilih-cabang', ['redirectTo' => route('grosir.pelanggan.index')]);
        }
        $cabangId = $gate;

        // Pelanggan grosir SELALU per cabang — tidak campur toko lain
        $query = PelangganGrosir::where('cabang_id', $cabangId);

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nama', 'like', "%$s%")
                    ->orWhere('kode', 'like', "%$s%")
                    ->orWhere('no_hp', 'like', "%$s%");
            });
        }

        $pelanggans = $query->orderBy('nama')->paginate(20)->withQueryString();

        // Statistik per pelanggan (omzet & piutang)
        $pelanggans->getCollection()->transform(function ($p) {
            $p->total_omzet = PenjualanGrosir::where('pelanggan_grosir_id', $p->id)
                ->where('status', '!=', 'Dibatalkan')->sum('total');
            $p->piutang_aktif = PenjualanGrosir::where('pelanggan_grosir_id', $p->id)
                ->whereIn('status', ['Piutang', 'Sebagian'])
                ->with('payments')
                ->get()
                ->sum(fn($pj) => $pj->sisaPiutang());
            return $p;
        });

        return view('grosir.pelanggan.index', compact('pelanggans'));
    }

    public function create()
    {
        return view('grosir.pelanggan.form', ['pelanggan' => new PelangganGrosir()]);
    }

    public function store(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return redirect()->route('grosir.pelanggan.index')->with('error', 'Pilih toko terlebih dahulu.');
        }
        $cabangId = $gate;

        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'no_hp' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
            'alamat_kirim' => 'nullable|string',
            'tipe' => 'required|in:' . implode(',', PelangganGrosir::TIPE),
            'level_harga' => 'required|in:' . implode(',', array_keys(GrosirService::LEVELS)),
            'limit_piutang' => 'nullable|numeric|min:0',
            'aktif' => 'nullable|boolean',
            'catatan' => 'nullable|string',
        ]);

        $validated['cabang_id'] = $cabangId;
        $validated['user_id'] = auth()->id();
        $validated['kode'] = PelangganGrosir::generateKode($cabangId);
        $validated['aktif'] = $request->boolean('aktif', true);

        $pelanggan = PelangganGrosir::create($validated);
        AuditLogService::created('pelanggan_grosir', "Tambah pelanggan grosir: {$pelanggan->nama} ({$pelanggan->kode})", $pelanggan);

        return redirect()->route('grosir.pelanggan.index')
            ->with('success', "Pelanggan grosir {$pelanggan->nama} ({$pelanggan->kode}) berhasil ditambahkan!");
    }

    public function edit(PelangganGrosir $pelanggan_grosir)
    {
        GrosirService::assertAksesCabang($pelanggan_grosir->cabang_id);
        return view('grosir.pelanggan.form', ['pelanggan' => $pelanggan_grosir]);
    }

    public function update(Request $request, PelangganGrosir $pelanggan_grosir)
    {
        GrosirService::assertAksesCabang($pelanggan_grosir->cabang_id);

        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'no_hp' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
            'alamat_kirim' => 'nullable|string',
            'tipe' => 'required|in:' . implode(',', PelangganGrosir::TIPE),
            'level_harga' => 'required|in:' . implode(',', array_keys(GrosirService::LEVELS)),
            'limit_piutang' => 'nullable|numeric|min:0',
            'aktif' => 'nullable|boolean',
            'catatan' => 'nullable|string',
        ]);
        $validated['aktif'] = $request->boolean('aktif', true);

        $pelanggan_grosir->update($validated);
        AuditLogService::updated('pelanggan_grosir', "Update pelanggan grosir: {$pelanggan_grosir->nama}", $pelanggan_grosir);

        return redirect()->route('grosir.pelanggan.index')->with('success', 'Pelanggan grosir berhasil diupdate!');
    }

    public function destroy(PelangganGrosir $pelanggan_grosir)
    {
        GrosirService::assertAksesCabang($pelanggan_grosir->cabang_id);

        $adaTransaksi = PenjualanGrosir::where('pelanggan_grosir_id', $pelanggan_grosir->id)->exists();
        if ($adaTransaksi) {
            // Jangan hard delete — cukup nonaktifkan supaya riwayat nota tetap nyambung
            $pelanggan_grosir->update(['aktif' => false]);
            return back()->with('success', 'Pelanggan punya riwayat transaksi — dinonaktifkan saja (data nota tetap aman).');
        }

        AuditLogService::deleted('pelanggan_grosir', "Hapus pelanggan grosir: {$pelanggan_grosir->nama}");
        $pelanggan_grosir->delete();
        return back()->with('success', 'Pelanggan grosir dihapus.');
    }
}
