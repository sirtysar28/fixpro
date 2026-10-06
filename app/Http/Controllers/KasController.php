<?php

namespace App\Http\Controllers;

use App\Models\Kas;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // PERBAIKAN 1: Blade menggunakan name="tanggal", bukan "date"
        if ($request->filled('tanggal')) {
            $query->whereDate('waktu', $request->tanggal);
        }
        
        // Tambahkan pencarian jika ada (sesuai fitur di Blade Anda)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ket', 'like', "%{$search}%")
                  ->orWhere('ref', 'like', "%{$search}%")
                  ->orWhere('kategori', 'like', "%{$search}%");
            });
        }

        $kass = $query->orderBy('waktu', 'desc')->orderBy('id', 'desc')->paginate(25);

        $saldoQuery = Kas::query();
        if ($cabangId !== null) {
            $saldoQuery->where('cabang_id', $cabangId);
        }
        
        $lastKas = (clone $saldoQuery)->orderBy('waktu', 'desc')->orderBy('id', 'desc')->first();
        $saldo = $lastKas ? $lastKas->saldo : 0;

        $today = now()->format('Y-m-d');
        $masukHariIni = (clone $saldoQuery)->where('tipe', 'masuk')->whereDate('waktu', $today)->sum('jml');
        $keluarHariIni = (clone $saldoQuery)->where('tipe', 'keluar')->whereDate('waktu', $today)->sum('jml');

        // Hitung jumlah transaksi untuk badge di Blade
        $jmlTransaksiMasuk = (clone $saldoQuery)->where('tipe', 'masuk')->whereDate('waktu', $today)->count();
        $jmlTransaksiKeluar = (clone $saldoQuery)->where('tipe', 'keluar')->whereDate('waktu', $today)->count();

        return view('kas.index', compact(
            'kass', 'saldo', 'masukHariIni', 'keluarHariIni', 
            'jmlTransaksiMasuk', 'jmlTransaksiKeluar'
        ));
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

        $lastKas = Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->orderBy('id', 'desc')->first();
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

        // PERBAIKAN 2: Simpan acuan waktu & ID sebelum dihapus
        $waktuHapus = $ka->waktu;
        $idHapus = $ka->id;
        $cabangId = $ka->cabang_id;

        AuditLogService::log('kas', 'delete', "Menghapus transaksi kas: {$ka->tipe} Rp " . number_format($ka->jml));
        
        // Hapus transaksi
        $ka->delete();

        // PERBAIKAN 3: REKALKULASI SALDO OTOMATIS
        // 1. Cari saldo terakhir SEBELUM transaksi yang dihapus
        $saldoSebelumnya = Kas::where('cabang_id', $cabangId)
            ->where(function($q) use ($waktuHapus, $idHapus) {
                $q->where('waktu', '<', $waktuHapus)
                  ->orWhere(function($q2) use ($waktuHapus, $idHapus) {
                      $q2->where('waktu', $waktuHapus)->where('id', '<', $idHapus);
                  });
            })
            ->orderBy('waktu', 'desc')
            ->orderBy('id', 'desc')
            ->value('saldo') ?? 0;

        // 2. Ambil semua transaksi SETELAH transaksi yang dihapus, urutkan dari yang terlama (ASC)
        $transaksiSetelahnya = Kas::where('cabang_id', $cabangId)
            ->where(function($q) use ($waktuHapus, $idHapus) {
                $q->where('waktu', '>', $waktuHapus)
                  ->orWhere(function($q2) use ($waktuHapus, $idHapus) {
                      $q2->where('waktu', $waktuHapus)->where('id', '>', $idHapus);
                  });
            })
            ->orderBy('waktu', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // 3. Loop dan update saldo satu per satu
        foreach ($transaksiSetelahnya as $trx) {
            if ($trx->tipe === 'masuk') {
                $saldoSebelumnya += $trx->jml;
            } else {
                $saldoSebelumnya -= $trx->jml;
            }
            
            // Update hanya kolom saldo untuk efisiensi
            $trx->saldo = $saldoSebelumnya;
            $trx->save(); 
        }

        return redirect()->route('kas.index')->with('success', 'Transaksi kas berhasil dihapus dan saldo telah disesuaikan ulang!');
    }
}