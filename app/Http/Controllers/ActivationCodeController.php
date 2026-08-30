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
            'durasi'   => 'required|in:register,standard_1_tahun,enterprise_1_tahun,1_bulan,3_bulan,6_bulan,1_tahun',
            'jumlah'   => 'required|integer|min:1|max:100',
            'cabang_id' => 'nullable|exists:cabang,id',
            'paket'    => 'nullable|in:standar,enterprise',
            'jumlah_user' => 'nullable|integer|min:1|max:100',
            'note'     => 'nullable|string|max:200',
        ]);

        // "Sesuai yang di-register": ikuti paket dari request aktivasi cabang tsb.
        // Tidak ada lagi opsi permanen — masa berlaku selalu terbatas sesuai paket (1 tahun).
        $durasi = $validated['durasi'];
        if ($durasi === 'register') {
            $paketRegister = null;
            if (!empty($validated['cabang_id'])) {
                $paketRegister = \App\Models\ActivationRequest::where('cabang_id', $validated['cabang_id'])
                    ->whereNotNull('paket')->latest('id')->value('paket');
            }
            $durasi = ($paketRegister ?? $validated['paket'] ?? 'standar') === 'enterprise'
                ? 'enterprise_1_tahun'
                : 'standard_1_tahun';
        }

        // Paket otomatis mengikuti pilihan masa berlaku
        $paket = str_contains($durasi, 'enterprise') ? 'enterprise' : ($validated['paket'] ?? 'standar');

        $mulai = now();
        $durasiDays = match ($durasi) {
            'standard_1_tahun', 'enterprise_1_tahun', '1_tahun' => 365,
            '1_bulan' => 30, '3_bulan' => 90, '6_bulan' => 180,
            default => 365,
        };
        $berakhir = $mulai->copy()->addDays($durasiDays);

        $created = [];
        for ($i = 0; $i < $validated['jumlah']; $i++) {
            $created[] = ActivationCode::create([
                'code'             => ActivationCode::generateUniqueCode(),
                'cabang_id'        => $validated['cabang_id'] ?? null,
                'status'           => 'aktif',
                'durasi'           => $durasi,
                'paket'            => $paket,
                'jumlah_user'      => $validated['jumlah_user'] ?? 1,
                'activated_at'     => $validated['cabang_id'] ? $mulai : null,
                'activated_by'     => $validated['cabang_id'] ? auth()->id() : null,
                'mulai_berlaku'    => $validated['cabang_id'] ? $mulai : null,
                'berakhir_berlaku' => $validated['cabang_id'] ? $berakhir : null,
                'created_by'       => auth()->id(),
                'note'             => $validated['note'] ?? null,
            ]);
        }

        AuditLogService::custom(
            'activation_code',
            'generate',
            'Generate ' . count($created) . ' kode aktivasi paket ' . $paket . ' (' . $created[0]->durasiLabel() . ')'
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

    /**
     * Aktifkan / nonaktifkan kode aktivasi (hanya Admin Pusat / Super Admin)
     * Kode nonaktif tidak bisa dipakai cabang lain & tercatat di audit log.
     */
    public function toggle(ActivationCode $activationCode)
    {
        $this->checkAdmin();

        $baru = $activationCode->status === 'aktif' ? 'nonaktif' : 'aktif';
        $activationCode->update(['status' => $baru]);

        AuditLogService::custom(
            'activation_code',
            'toggle',
            ($baru === 'nonaktif' ? 'Nonaktifkan' : 'Aktifkan') . " kode aktivasi {$activationCode->code} (cabang: " . ($activationCode->cabang?->nama ?? '-') . ')'
        );

        return redirect()->route('activation-code.index')
            ->with('success', "Kode {$activationCode->code} sekarang {$baru}.");
    }
}
