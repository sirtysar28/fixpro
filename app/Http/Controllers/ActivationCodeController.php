<?php

namespace App\Http\Controllers;

use App\Models\ActivationCode;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class ActivationCodeController extends Controller
{
    private function checkAdmin()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang bisa mengakses halaman ini.');
        }
    }

    /**
     * Daftar semua kode aktivasi
     */
    public function index()
    {
        $this->checkAdmin();

        $codes = ActivationCode::with(['creator', 'usedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $stats = [
            'total'    => ActivationCode::count(),
            'tersedia' => ActivationCode::where('is_used', false)->count(),
            'terpakai' => ActivationCode::where('is_used', true)->count(),
        ];

        return view('activation-code.index', compact('codes', 'stats'));
    }

    /**
     * Generate satu / beberapa kode aktivasi
     */
    public function generate(Request $request)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'durasi'   => 'required|in:1_bulan,3_bulan,6_bulan,1_tahun,permanen',
            'jumlah'   => 'required|integer|min:1|max:100',
            'note'     => 'nullable|string|max:200',
        ]);

        $created = [];
        for ($i = 0; $i < $validated['jumlah']; $i++) {
            $created[] = ActivationCode::generate(
                $validated['durasi'],
                auth()->id(),
                $validated['note'] ?? null
            );
        }

        AuditLogService::custom(
            'activation_code',
            'generate',
            'Generate ' . count($created) . ' kode aktivasi durasi ' . $validated['durasi']
        );

        return redirect()->route('activation-code.index')
            ->with('success', count($created) . ' kode aktivasi berhasil dibuat.')
            ->with('generated_codes', collect($created)->pluck('code')->toArray());
    }

    /**
     * Hapus kode yang belum dipakai
     */
    public function destroy(ActivationCode $activationCode)
    {
        $this->checkAdmin();

        if ($activationCode->is_used) {
            return redirect()->route('activation-code.index')
                ->with('error', 'Kode yang sudah dipakai tidak bisa dihapus.');
        }

        $code = $activationCode->code;
        $activationCode->delete();

        AuditLogService::custom('activation_code', 'delete', "Hapus kode aktivasi: {$code}");

        return redirect()->route('activation-code.index')
            ->with('success', 'Kode aktivasi berhasil dihapus.');
    }
}
