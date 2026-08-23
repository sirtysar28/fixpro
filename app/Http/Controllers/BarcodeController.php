<?php

namespace App\Http\Controllers;

use App\Models\Stok;
use Illuminate\Http\Request;

class BarcodeController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $cabangId = $user->getActiveCabangId();

        // Stok barcode tidak boleh campur antar toko:
        // Super Admin mode "Semua Cabang" wajib pilih toko dulu
        if ($cabangId === null) {
            return view('stok.pilih-cabang', ['redirectTo' => $request->fullUrl()]);
        }

        $query = Stok::where('cabang_id', $cabangId);

        if ($request->filled('search')) {
            $s = $request->search;
            // Grouping penting: tanpa grouping, orWhere bisa membocorkan barang toko lain
            $query->where(function ($q) use ($s) {
                $q->where('nama', 'like', "%$s%")
                  ->orWhere('kode', 'like', "%$s%")
                  ->orWhere('barcode', 'like', "%$s%");
            });
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $stoks = $query->orderBy('nama')->paginate(50);

        // Get unique categories
        $kategoriQuery = Stok::where('cabang_id', $cabangId);
        $categories = $kategoriQuery->distinct()->pluck('kategori')->sort()->values();

        // Auto-generate barcodes for products that don't have one
        $noBarcodeCount = 0;
        foreach ($stoks as $stok) {
            if (empty($stok->barcode)) {
                $stok->barcode = $this->generateBarcodeValue($stok);
                $stok->save();
                $noBarcodeCount++;
            }
        }

        return view('barcode.index', compact('stoks', 'categories', 'noBarcodeCount'));
    }

    public function generateSingle(Stok $stok)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            $cabangId = $user->getActiveCabangId();
            if ($stok->cabang_id != $cabangId) {
                abort(403);
            }
        }

        if (empty($stok->barcode)) {
            $stok->barcode = $this->generateBarcodeValue($stok);
            $stok->save();
        }

        return response()->json([
            'success' => true,
            'barcode' => $stok->barcode,
            'nama' => $stok->nama,
            'kode' => $stok->kode,
        ]);
    }

    public function generateAll()
    {
        $user = auth()->user();
        $cabangId = $user->getActiveCabangId();

        // Generate barcode hanya untuk cabang aktif — jangan campur antar toko
        $stoks = Stok::where('cabang_id', $cabangId)->get();
        $generated = 0;

        foreach ($stoks as $stok) {
            if (empty($stok->barcode)) {
                $stok->barcode = $this->generateBarcodeValue($stok);
                $stok->save();
                $generated++;
            }
        }

        return back()->with('success', "Barcode berhasil digenerate untuk {$generated} produk.");
    }

    public function print(Request $request)
    {
        $user = auth()->user();
        $cabangId = $user->getActiveCabangId();

        // Print barcode hanya untuk cabang aktif — jangan campur antar toko
        if ($cabangId === null) {
            return view('stok.pilih-cabang', ['redirectTo' => route('barcode.index')]);
        }

        $stoks = Stok::where('cabang_id', $cabangId)->orderBy('nama')->get();

        // Ensure all have barcodes
        foreach ($stoks as $stok) {
            if (empty($stok->barcode)) {
                $stok->barcode = $this->generateBarcodeValue($stok);
                $stok->save();
            }
        }

        return view('barcode.print', compact('stoks'));
    }

    private function generateBarcodeValue(Stok $stok): string
    {
        // Generate barcode: FXP + padded ID
        return 'FXP' . str_pad($stok->id, 7, '0', STR_PAD_LEFT);
    }
}
