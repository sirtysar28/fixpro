<?php

namespace App\Http\Controllers;

use App\Models\TipeHp;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class TipeHpController extends Controller
{
    public function index(Request $request)
    {
        $query = TipeHp::query();

        if ($request->filled('merk')) {
            $query->where('merk', $request->merk);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('tipe', 'like', "%$s%")
                  ->orWhere('merk', 'like', "%$s%");
            });
        }

        $tipeHps = $query->orderBy('merk')->orderBy('tipe')->paginate(25);
        $merks = TipeHp::getMerks();

        return view('tipe-hp.index', compact('tipeHps', 'merks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'merk' => 'required|string|max:100',
            'tipe' => 'required|string|max:200|unique:tipe_hp,tipe',
            'kategori' => 'nullable|string|max:100',
        ]);

        TipeHp::create($validated);
        AuditLogService::log('tipe_hp', 'create', "Menambahkan tipe HP: {$validated['merk']} {$validated['tipe']}");

        return back()->with('success', "Tipe HP {$validated['merk']} {$validated['tipe']} berhasil ditambahkan!");
    }

    public function update(Request $request, TipeHp $tipeHp)
    {
        $validated = $request->validate([
            'merk' => 'required|string|max:100',
            'tipe' => 'required|string|max:200|unique:tipe_hp,tipe,' . $tipeHp->id,
            'kategori' => 'nullable|string|max:100',
            'aktif' => 'boolean',
        ]);

        $tipeHp->update($validated);
        AuditLogService::log('tipe_hp', 'update', "Mengupdate tipe HP: {$validated['merk']} {$validated['tipe']}");

        return back()->with('success', "Tipe HP berhasil diupdate!");
    }

    public function destroy(TipeHp $tipeHp)
    {
        AuditLogService::log('tipe_hp', 'delete', "Menghapus tipe HP: {$tipeHp->merk} {$tipeHp->tipe}");
        $tipeHp->delete();
        return back()->with('success', 'Tipe HP berhasil dihapus!');
    }

    /**
     * API: Get tipe HP by merk for dynamic dropdown
     */
    public function getByMerk(Request $request)
    {
        $request->validate(['merk' => 'required|string']);

        $types = TipeHp::aktif()->where('merk', $request->merk)
            ->orderBy('tipe')
            ->get(['id', 'tipe']);

        return response()->json($types);
    }

    /**
     * API: Search tipe HP
     */
    public function search(Request $request)
    {
        $s = $request->query('q', '');
        $results = TipeHp::aktif()
            ->where('tipe', 'like', "%$s%")
            ->orWhere('merk', 'like', "%$s%")
            ->orderBy('merk')->orderBy('tipe')
            ->limit(20)
            ->get(['id', 'merk', 'tipe']);

        return response()->json($results);
    }
}
