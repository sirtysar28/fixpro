<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Stok;
use App\Services\GrosirService;
use Illuminate\Http\Request;

/**
 * Stok Grosir: Stok Toko, Stok Gudang, Stok Minimum, Stok Reservasi.
 * SEMUA data per cabang — tidak pernah campur antar toko.
 * Transfer stok & riwayat stok pakai modul yang sudah ada.
 */
class StokGrosirController extends Controller
{
    public function index(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return view('stok.pilih-cabang', ['redirectTo' => route('grosir.stok.index')]);
        }
        $cabangId = $gate;

        $tab = $request->get('tab', 'toko');
        $user = auth()->user();

        // ===== Stok Gudang: daftar gudang milik grup sendiri =====
        $gudangs = GrosirService::gudangOptions($user);
        $gudangTerpilih = (int) $request->get('gudang', $gudangs[0]['id'] ?? 0);

        // Sumber data sesuai tab: gudang → stok cabang gudang, lainnya → cabang aktif
        $sumberCabangId = $tab === 'gudang' ? $gudangTerpilih : $cabangId;

        $query = Stok::where('cabang_id', $sumberCabangId);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nama', 'like', "%$s%")
                    ->orWhere('kode', 'like', "%$s%")
                    ->orWhere('barcode', 'like', "%$s%");
            });
        }

        // Tab khusus
        if ($tab === 'minimum') {
            $query->whereColumn('stok', '<=', 'min_alert');
        }
        if ($tab === 'reservasi') {
            $query->where('reserved', '>', 0);
        }

        $stoks = $query->with('hargaGrosir')->orderBy('nama')->paginate(20)->withQueryString();

        // Statistik
        $statsBase = Stok::where('cabang_id', $sumberCabangId);
        $totalJenis = (clone $statsBase)->count();
        $totalUnit = (clone $statsBase)->sum('stok');
        $totalReserved = (clone $statsBase)->sum('reserved');
        $stokRendah = (clone $statsBase)->whereColumn('stok', '<=', 'min_alert')->count();
        $nilaiModal = (clone $statsBase)->get()->sum(fn($s) => $s->stok * $s->modal);

        return view('grosir.stok.index', compact(
            'stoks', 'tab', 'gudangs', 'gudangTerpilih',
            'totalJenis', 'totalUnit', 'totalReserved', 'stokRendah', 'nilaiModal'
        ));
    }
}
