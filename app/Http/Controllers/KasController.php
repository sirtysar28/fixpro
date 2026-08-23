<?php

namespace App\Http\Controllers;

use App\Models\Kas;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class KasController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $cabangId = $user->getActiveCabangId();

        $query = Kas::query();
        if ($cabangId !== null) {
            $query->where('cabang_id', $cabangId);
        }

        if ($request->filled('date')) {
            $query->whereDate('waktu', $request->date);
        }
        $kass = $query->orderBy('waktu', 'desc')->paginate(25);

        $saldoQuery = Kas::query();
        if ($cabangId !== null) {
            $saldoQuery->where('cabang_id', $cabangId);
        }
        $lastKas = (clone $saldoQuery)->orderBy('waktu', 'desc')->first();
        $saldo = $lastKas ? $lastKas->saldo : 0;

        $today = now()->format('Y-m-d');
        $masukHariIni = (clone $saldoQuery)->where('tipe', 'masuk')->whereDate('waktu', $today)->sum('jml');
        $keluarHariIni = (clone $saldoQuery)->where('tipe', 'keluar')->whereDate('waktu', $today)->sum('jml');

        return view('kas.index', compact('kass', 'saldo', 'masukHariIni', 'keluarHariIni'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipe' => 'required|in:masuk,keluar',
            'jml' => 'required|numeric|min:0',
            'kategori' => 'required',
            'ket' => 'required',
            'metode' => 'required|in:Cash,Transfer,QRIS',
            'ref' => 'nullable',
        ]);

        $cabangId = auth()->user()->getActiveCabangId();

        $lastKas = Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
        $lastSaldo = $lastKas ? $lastKas->saldo : 0;

        $newSaldo = $validated['tipe'] === 'masuk'
            ? $lastSaldo + $validated['jml']
            : $lastSaldo - $validated['jml'];

        Kas::create([
            'tipe' => $validated['tipe'],
            'cabang_id' => $cabangId,
            'jml' => $validated['jml'],
            'kategori' => $validated['kategori'],
            'ket' => $validated['ket'],
            'metode' => $validated['metode'],
            'ref' => $validated['ref'] ?? null,
            'waktu' => now(),
            'saldo' => $newSaldo,
        ]);

        AuditLogService::log('kas', 'create', "Transaksi kas {$validated['tipe']}: Rp " . number_format($validated['jml']) . " ({$validated['ket']})");

        return redirect()->route('kas.index')->with('success', 'Transaksi kas berhasil!');
    }

    public function destroy(Kas $ka)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && $ka->cabang_id != $user->getActiveCabangId()) {
            abort(403, 'Anda hanya bisa menghapus data kas di cabang Anda sendiri.');
        }
        AuditLogService::log('kas', 'delete', "Menghapus transaksi kas: {$ka->tipe} Rp " . number_format($ka->jml));
        $ka->delete();
        return redirect()->route('kas.index')->with('success', 'Transaksi kas berhasil dihapus!');
    }
}
