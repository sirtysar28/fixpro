<?php

namespace App\Http\Controllers;

use App\Models\PenjualanGrosir;
use App\Models\ReturGrosir;
use App\Models\ReturGrosirItem;
use App\Services\AuditLogService;
use App\Services\GrosirService;
use App\Services\SparepartMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturGrosirController extends Controller
{
    public function index(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return view('stok.pilih-cabang', ['redirectTo' => route('grosir.retur.index')]);
        }
        $cabangId = $gate;

        $query = ReturGrosir::with(['penjualan', 'pelanggan', 'user'])
            ->where('cabang_id', $cabangId);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('no_retur', 'like', "%$s%")
                    ->orWhereHas('penjualan', fn($pq) => $pq->where('no_nota', 'like', "%$s%"))
                    ->orWhere('nama_pelanggan', 'like', "%$s%");
            });
        }

        $returs = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        return view('grosir.retur.index', compact('returs'));
    }

    public function create(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return view('stok.pilih-cabang', ['redirectTo' => route('grosir.retur.create')]);
        }
        $cabangId = $gate;

        // Daftar nota yang bisa diretur (milik cabang aktif & tidak dibatalkan)
        $notas = PenjualanGrosir::with('pelanggan')
            ->where('cabang_id', $cabangId)
            ->where('status', '!=', 'Dibatalkan')
            ->orderByDesc('created_at')->limit(100)->get();

        $selected = null;
        $items = collect([]);
        if ($request->filled('nota')) {
            $selected = $notas->firstWhere('id', $request->nota);
            if ($selected) {
                $selected->load('items.stok');
                $items = $selected->items;
            }
        }

        return view('grosir.retur.create', compact('notas', 'selected', 'items'));
    }

    public function store(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return redirect()->route('grosir.retur.index')->with('error', 'Pilih toko terlebih dahulu.');
        }
        $cabangId = $gate;

        $validated = $request->validate([
            'penjualan_grosir_id' => 'required|exists:penjualan_grosirs,id',
            'metode' => 'required|in:Uang Kembali,Tukar Barang,Potong Piutang',
            'alasan' => 'required|string|min:3|max:500',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:penjualan_grosir_items,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $penjualan = PenjualanGrosir::with('items')->findOrFail($validated['penjualan_grosir_id']);
        GrosirService::assertAksesCabang($penjualan->cabang_id);

        if ($penjualan->status === 'Dibatalkan') {
            return back()->with('error', 'Nota sudah dibatalkan — tidak bisa retur.');
        }

        // Validasi qty retur <= qty beli - qty sudah pernah diretur
        $sudahRetur = [];
        foreach ($penjualan->returs as $r) {
            foreach ($r->items as $ri) {
                $key = $ri->stok_id . '|' . $ri->nama;
                $sudahRetur[$key] = ($sudahRetur[$key] ?? 0) + $ri->qty;
            }
        }

        $totalRetur = 0;
        $rows = [];
        foreach ($validated['items'] as $row) {
            $item = $penjualan->items->firstWhere('id', $row['item_id']);
            if (!$item) continue;
            $key = $item->stok_id . '|' . $item->nama;
            $batas = $item->qty - ($sudahRetur[$key] ?? 0);
            if ($row['qty'] > $batas) {
                return back()->with('error', "Retur {$item->nama} melebihi batas (maks {$batas}).")->withInput();
            }
            $totalRetur += $row['qty'] * (float) $item->harga_satuan;
            $rows[] = ['item' => $item, 'qty' => $row['qty']];
        }

        DB::beginTransaction();
        try {
            $retur = ReturGrosir::create([
                'no_retur' => ReturGrosir::generateNoRetur(),
                'cabang_id' => $cabangId,
                'user_id' => auth()->id(),
                'penjualan_grosir_id' => $penjualan->id,
                'pelanggan_grosir_id' => $penjualan->pelanggan_grosir_id,
                'nama_pelanggan' => $penjualan->nama_pelanggan,
                'tanggal' => now(),
                'total' => $totalRetur,
                'metode' => $validated['metode'],
                'alasan' => $validated['alasan'],
            ]);

            foreach ($rows as $row) {
                $item = $row['item'];
                ReturGrosirItem::create([
                    'retur_grosir_id' => $retur->id,
                    'stok_id' => $item->stok_id,
                    'nama' => $item->nama,
                    'qty' => $row['qty'],
                    'harga_satuan' => $item->harga_satuan,
                    'subtotal' => $row['qty'] * (float) $item->harga_satuan,
                ]);

                // Barang kembali ke sumber stok nota
                if ($item->stok) {
                    $item->stok->increment('stok', $row['qty']);
                    SparepartMovementService::record($item->stok, 'masuk', 'retur_grosir', (int) $row['qty'], [
                        'referensi' => $retur->no_retur,
                        'referensi_id' => $retur->id,
                        'cabang_id' => $penjualan->sumber_cabang_id,
                        'catatan' => 'Retur grosir: ' . $validated['alasan'],
                    ]);
                }
            }

            // Akibat finansial
            if ($validated['metode'] === 'Uang Kembali') {
                GrosirService::kasKeluar($cabangId, $totalRetur, "Retur grosir {$retur->no_retur} (nota {$penjualan->no_nota})", 'Cash', $retur->no_retur);
            }
            // 'Tukar Barang' → tidak ada uang keluar (barang sudah masuk stok)
            // 'Potong Piutang' → sisa piutang berkurang otomatis via sisaPiutang()

            $penjualan->increment('total_retur', $totalRetur);

            DB::commit();
            AuditLogService::created('retur_grosir', "Retur grosir {$retur->no_retur} untuk nota {$penjualan->no_nota}, total Rp " . number_format($totalRetur), $retur);

            return redirect()->route('grosir.retur.index')
                ->with('success', "Retur {$retur->no_retur} berhasil. Total: " . formatRp($totalRetur));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function show(ReturGrosir $retur_grosir)
    {
        GrosirService::assertAksesCabang($retur_grosir->cabang_id);
        $retur_grosir->load(['items', 'penjualan', 'pelanggan', 'user']);
        return view('grosir.retur.show', compact('retur_grosir'));
    }
}
