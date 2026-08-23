<?php

namespace App\Http\Controllers;

use App\Models\SerialNumber;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class SerialNumberController extends Controller
{
    private function checkAdmin()
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang bisa mengakses halaman ini.');
        }
    }

    /**
     * Tampilkan halaman kelola aktivasi & lisensi
     * Menampilkan: daftar user yang request aktivasi + serial number yang sudah digenerate
     */
    public function index()
    {
        $this->checkAdmin();

        // User yang belum permanen (trial/belum aktivasi)
        $pendingUsers = User::with(['cabang', 'role'])
            ->where('is_permanent', false)
            ->where('is_super_admin', false)
            ->where('is_active', true)
            ->orderBy('login_expires_at', 'asc')
            ->get()
            ->map(function ($u) {
                $u->days_left = $u->daysUntilExpiry() ?? 0;
                $u->status_label = $u->days_left > 0 ? "Trial ({$u->days_left} hari)" : 'Expired';
                $u->status_color = $u->days_left > 7 ? 'warning' : ($u->days_left > 0 ? 'danger' : 'expired');
                return $u;
            });

        // Serial number yang sudah digenerate
        $serials = SerialNumber::with(['creator', 'usedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('serial-number.index', compact('pendingUsers', 'serials'));
    }

    /**
     * Generate serial number untuk user yang request aktivasi
     */
    public function generate(Request $request)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'Email tidak ditemukan di database user.',
        ]);

        $serial = SerialNumber::generateFromEmail($validated['email'], auth()->id());

        // Auto-activate user langsung
        $user = User::where('email', $validated['email'])->first();
        if ($user) {
            $user->update([
                'is_permanent' => true,
                'login_expires_at' => null,
            ]);
        }

        AuditLogService::custom('serial_number', 'generate', "Generate & aktivasi untuk {$validated['email']}. Serial: {$serial->serial_code}");

        return redirect()->route('serial-number.index')
            ->with('success', "Serial Number berhasil dibuat & akun sudah diaktivasi: {$serial->serial_code}")
            ->with('generated_serial', $serial->serial_code);
    }

    /**
     * Generate multiple serial numbers
     */
    public function generateBulk(Request $request)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'emails' => 'required|array|min:1',
            'emails.*' => 'email|exists:users,email',
        ]);

        $serials = [];
        foreach ($validated['emails'] as $email) {
            $serial = SerialNumber::generateFromEmail($email, auth()->id());
            $serials[] = $serial;
        }

        return redirect()->route('serial-number.index')
            ->with('success', count($serials) . ' Serial Number berhasil dibuat!');
    }

    /**
     * Hapus serial number yang belum dipakai
     */
    public function destroy(SerialNumber $serialNumber)
    {
        $this->checkAdmin();

        if ($serialNumber->is_used) {
            return redirect()->route('serial-number.index')
                ->with('error', 'Serial Number yang sudah dipakai tidak bisa dihapus.');
        }

        $serialNumber->delete();

        AuditLogService::log('serial_number', 'delete', "Menghapus Serial Number: {$serialNumber->serial_code}");

        return redirect()->route('serial-number.index')
            ->with('success', 'Serial Number berhasil dihapus.');
    }
}
