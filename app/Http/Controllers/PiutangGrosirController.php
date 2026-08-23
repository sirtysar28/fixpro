<?php

namespace App\Http\Controllers;

use App\Models\PenjualanGrosir;
use App\Models\PiutangGrosirPayment;
use App\Services\AuditLogService;
use App\Services\GrosirService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PiutangGrosirController extends Controller
{
    /**
     * Daftar piutang: tab aktif / jatuh-tempo / riwayat (semua per cabang aktif).
     */
    public function index(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return view('stok.pilih-cabang', ['redirectTo' => route('grosir.piutang.index')]);
        }
        $cabangId = $gate;

        $tab = $request->get('tab', 'aktif');

        $query = PenjualanGrosir::with(['pelanggan', 'user', 'payments'])
            ->where('cabang_id', $cabangId)
            ->where('status', '!=', 'Dibatalkan');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('no_nota', 'like', "%$s%")
                    ->orWhere('nama_pelanggan', 'like', "%$s%");
            });
        }

        if ($tab === 'riwayat') {
            // Semua nota yang pernah punya piutang (termasuk yang sudah lunas)
            $piutangs = $query->whereRaw('(piutang > 0)')->orderByDesc('created_at')->paginate(20)->withQueryString();
        } else {
            $piutangs = $query->whereIn('status', ['Piutang', 'Sebagian'])->orderByDesc('created_at')->get();

            if ($tab === 'jatuh-tempo') {
                $piutangs = $piutangs->filter(fn($p) => $p->jatuh_tempo && $p->jatuh_tempo->isPast() && $p->sisaPiutang() > 0)->values();
            }

            // Hitung sisa per nota
            $totalSisa = $piutangs->sum(fn($p) => $p->sisaPiutang());
            $perPage = 20;
            $page = max(1, (int) $request->get('page', 1));
            $piutangs = new \Illuminate\Pagination\LengthAwarePaginator(
                $piutangs->forPage($page, $perPage),
                $piutangs->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            $piutangs->totalSisa = $totalSisa;
        }

        return view('grosir.piutang.index', compact('piutangs', 'tab'));
    }

    /**
     * Catat pembayaran piutang grosir.
     */
    public function bayar(Request $request, PenjualanGrosir $penjualan_grosir)
    {
        GrosirService::assertAksesCabang($penjualan_grosir->cabang_id);

        if ($penjualan_grosir->status === 'Dibatalkan') {
            return back()->with('error', 'Nota sudah dibatalkan.');
        }

        $validated = $request->validate([
            'jml' => 'required|numeric|min:1',
            'metode' => 'required|in:Cash,Transfer,QRIS',
            'tanggal' => 'nullable|date',
            'catatan' => 'nullable|string|max:500',
        ]);

        $sisa = $penjualan_grosir->sisaPiutang();
        if ($sisa <= 0) {
            return back()->with('error', 'Piutang nota ini sudah lunas.');
        }
        if ((float) $validated['jml'] > $sisa) {
            return back()->with('error', 'Pembayaran melebihi sisa piutang (' . formatRp($sisa) . ').')->withInput();
        }

        DB::beginTransaction();
        try {
            PiutangGrosirPayment::create([
                'penjualan_grosir_id' => $penjualan_grosir->id,
                'cabang_id' => $penjualan_grosir->cabang_id,
                'user_id' => auth()->id(),
                'tanggal' => $validated['tanggal'] ?? now(),
                'jml' => $validated['jml'],
                'metode' => $validated['metode'],
                'catatan' => $validated['catatan'] ?? null,
            ]);

            GrosirService::kasMasuk(
                $penjualan_grosir->cabang_id,
                (float) $validated['jml'],
                "Pelunasan piutang grosir {$penjualan_grosir->no_nota}",
                $validated['metode'],
                $penjualan_grosir->no_nota
            );

            // Update status nota
            $sisaBaru = $penjualan_grosir->fresh()->sisaPiutang();
            $penjualan_grosir->update(['status' => $sisaBaru <= 0 ? 'Lunas' : 'Sebagian']);

            DB::commit();
            AuditLogService::log('piutang_grosir', 'bayar', "Pembayaran piutang {$penjualan_grosir->no_nota}: Rp " . number_format($validated['jml']));

            return back()->with('success', 'Pembayaran piutang dicatat: ' . formatRp($validated['jml']));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }
}
