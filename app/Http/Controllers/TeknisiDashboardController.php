<?php

namespace App\Http\Controllers;

use App\Models\Servis;
use App\Models\Teknisi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeknisiDashboardController extends Controller
{
    /**
     * Teknisi dashboard - read only view of own services & income
     */
    public function index()
    {
        $user = auth()->user();
        $teknisi = $user->teknisiProfile;

        if (!$teknisi) {
            return redirect()->route('profile.edit')->with('error', 'Akun Anda belum terhubung ke data teknisi. Hubungi admin.');
        }

        $cabangId = $user->getActiveCabangId();

        // All services assigned to this technician
        $servisQuery = Servis::where('teknisi_id', $teknisi->id)
            ->with(['pelanggan', 'cabang']);

        // Stats
        $servisAktif = (clone $servisQuery)->whereIn('status', ['Masuk', 'Proses', 'Pending'])->count();
        $servisSelesai = (clone $servisQuery)->where('status', 'Selesai')->count();
        $servisDibatalkan = (clone $servisQuery)->where('status', 'Dibatalkan')->count();

        // Income - only from completed services
        $omsetBulanIni = (clone $servisQuery)
            ->where('status', 'Selesai')
            ->whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year)
            ->sum('biaya');

        $omsetTotal = (clone $servisQuery)
            ->where('status', 'Selesai')
            ->sum('biaya');

        // Laba bersih teknisi (bagi hasil)
        $bagiHasil = $teknisi->bagi_hasil ?? 35;
        $labaBulanIni = $omsetBulanIni * ($bagiHasil / 100);
        $labaTotal = $omsetTotal * ($bagiHasil / 100);

        // Monthly income chart (last 6 months)
        $monthlyIncome = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $income = (clone $servisQuery)
                ->where('status', 'Selesai')
                ->whereMonth('tanggal', $month->month)
                ->whereYear('tanggal', $month->year)
                ->sum('biaya');
            $monthlyIncome[] = [
                'month' => $month->format('M Y'),
                'income' => $income,
                'profit' => $income * ($bagiHasil / 100),
            ];
        }

        // Recent services (last 10)
        $servisTerbaru = (clone $servisQuery)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Status distribution
        $statusCounts = (clone $servisQuery)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return view('teknisi-dashboard.index', compact(
            'teknisi', 'servisAktif', 'servisSelesai', 'servisDibatalkan',
            'omsetBulanIni', 'omsetTotal', 'labaBulanIni', 'labaTotal',
            'bagiHasil', 'monthlyIncome', 'servisTerbaru', 'statusCounts'
        ));
    }

    /**
     * View service detail (read only for teknisi)
     */
    public function showServis($id)
    {
        $user = auth()->user();
        $teknisi = $user->teknisiProfile;

        if (!$teknisi) {
            return redirect()->route('dashboard');
        }

        $servis = Servis::where('id', $id)
            ->where('teknisi_id', $teknisi->id)
            ->with(['pelanggan', 'cabang'])
            ->firstOrFail();

        return view('teknisi-dashboard.show', compact('servis', 'teknisi'));
    }
}
