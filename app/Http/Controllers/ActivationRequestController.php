<?php

namespace App\Http\Controllers;

use App\Models\ActivationRequest;
use App\Models\BankAccount;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActivationRequestController extends Controller
{
    /**
     * Admin Cabang: Halaman request aktivasi
     * Menampilkan info rekening bank + form request + riwayat request
     */
    public function index()
    {
        $user = auth()->user();

        // Cek kalau sudah permanen
        if ($user->is_permanent) {
            return redirect()->route('dashboard')->with('success', 'Akun Anda sudah permanen. Tidak perlu request aktivasi lagi.');
        }

        // Bank accounts dari super admin
        $bankAccounts = BankAccount::getActiveBanks();

        // Riwayat request dari user ini
        $myRequests = ActivationRequest::with(['approvedBy'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Cek apakah ada request menunggu / sedang diproses
        $hasPending = ActivationRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        return view('activation-request.index', compact('bankAccounts', 'myRequests', 'hasPending'));
    }

    /**
     * Admin Cabang: Kirim request aktivasi
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if ($user->is_permanent) {
            return back()->with('error', 'Akun Anda sudah permanen.');
        }

        // Cek pending/processing request
        if (ActivationRequest::where('user_id', $user->id)->whereIn('status', ['pending', 'processing'])->exists()) {
            return back()->with('error', 'Anda sudah memiliki request yang sedang diproses. Harap tunggu.');
        }

        $validated = $request->validate([
            'durasi' => 'required|in:standard_1_tahun,enterprise_1_tahun,1_bulan,3_bulan,6_bulan,1_tahun',
            'nama_cabang' => 'nullable|string|max:150',
            'alamat' => 'nullable|string|max:500',
            'nama_pemilik' => 'nullable|string|max:150',
            'no_wa' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'paket' => 'nullable|in:standar,enterprise',
            'jumlah_user' => 'nullable|integer|min:1|max:100',
            'jumlah_perangkat' => 'nullable|integer|min:1|max:100',
            'nominal_bayar' => 'nullable|numeric|min:0',
            'bukti_transfer' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'catatan' => 'nullable|string|max:500',
        ]);

        // Paket otomatis mengikuti pilihan masa berlaku (Standard / Enterprise — 1 tahun)
        $paket = str_contains($validated['durasi'], 'enterprise')
            ? 'enterprise'
            : (str_contains($validated['durasi'], 'standard') ? 'standar' : ($validated['paket'] ?? 'standar'));

        $buktiPath = $request->file('bukti_transfer')->store('bukti-transfer', 'public');

        ActivationRequest::create([
            'user_id' => $user->id,
            'cabang_id' => $user->cabang_id,
            'nama_cabang' => $validated['nama_cabang'] ?? $user->cabang?->nama,
            'nama_toko' => $user->cabang?->nama,
            'alamat' => $validated['alamat'] ?? null,
            'nama_pemilik' => $validated['nama_pemilik'] ?? $user->name,
            'no_wa' => $validated['no_wa'] ?? $user->phone,
            'email' => $validated['email'] ?? $user->email,
            'paket' => $paket,
            'jumlah_user' => $validated['jumlah_user'] ?? 1,
            'jumlah_perangkat' => $validated['jumlah_perangkat'] ?? null,
            'status' => 'pending',
            'durasi' => $validated['durasi'],
            'nominal_bayar' => $validated['nominal_bayar'],
            'bukti_transfer' => $buktiPath,
            'catatan' => $validated['catatan'],
        ]);

        $reqDummy = new ActivationRequest(['durasi' => $validated['durasi']]);
        AuditLogService::log('activation_request', 'create', "Request aktivasi paket {$paket} ({$reqDummy->durasiLabel()}) dari {$user->name} ({$user->cabang?->nama})");

        return back()->with('success', 'Request aktivasi berhasil dikirim! Silakan tunggu konfirmasi dari Admin Pusat.');
    }

    // ============= SUPER ADMIN: Kelola Request =============

    /**
     * Super Admin: Daftar semua request aktivasi
     */
    public function adminIndex()
    {
        $requests = ActivationRequest::with(['user', 'cabang', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        $pendingCount = ActivationRequest::where('status', 'pending')->count();

        return view('activation-request.admin-index', compact('requests', 'pendingCount'));
    }

    /**
     * Super Admin: Tandai request sedang diproses (Menunggu → Diproses)
     */
    public function proses(ActivationRequest $activationRequest)
    {
        if (!in_array($activationRequest->status, ['pending', 'processing'])) {
            return back()->with('error', 'Request ini sudah diproses.');
        }

        $activationRequest->update(['status' => 'processing']);
        AuditLogService::log('activation_request', 'proses', "Request aktivasi #{$activationRequest->id} ({$activationRequest->user?->name}) ditandai Diproses");

        return back()->with('success', 'Request ditandai sebagai Diproses.');
    }

    /**
     * Super Admin: Approve request — perpanjang masa aktif + buat KODE AKTIVASI terikat cabang
     */
    public function approve(Request $request, ActivationRequest $activationRequest)
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        if (!in_array($activationRequest->status, ['pending', 'processing'])) {
            return back()->with('error', 'Request ini sudah diproses.');
        }

        $user = $activationRequest->user;

        // Extend expiry based on durasi
        $durasi = $activationRequest->durasi;
        if ($durasi === 'permanen') {
            $user->update([
                'is_permanent' => true,
                'login_expires_at' => null,
            ]);
        } else {
            $days = $activationRequest->durasiDays();
            $currentExpiry = $user->login_expires_at;
            $newExpiry = $currentExpiry && now()->lt($currentExpiry)
                ? $currentExpiry->addDays($days)
                : now()->addDays($days);
            $user->update([
                'is_permanent' => false,
                'login_expires_at' => $newExpiry,
            ]);
        }

        $activationRequest->update([
            'status' => 'aktif',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'admin_note' => $request->admin_note,
        ]);

        // Buat KODE AKTIVASI unik terikat cabang + masa berlaku sesuai durasi paket
        $mulai = now();
        $berakhir = $activationRequest->durasi === 'permanen' ? null : $mulai->copy()->addDays($activationRequest->durasiDays());
        \App\Models\ActivationCode::create([
            'code' => \App\Models\ActivationCode::generateUniqueCode(),
            'cabang_id' => $activationRequest->cabang_id,
            'status' => 'aktif',
            'durasi' => $activationRequest->durasi,
            'paket' => $activationRequest->paket ?? 'standar',
            'jumlah_user' => $activationRequest->jumlah_user ?? 1,
            'activated_at' => $mulai,
            'activated_by' => auth()->id(),
            'mulai_berlaku' => $mulai,
            'berakhir_berlaku' => $berakhir,
            'created_by' => auth()->id(),
            'note' => 'Otomatis dari Request Aktivasi #' . $activationRequest->id,
        ]);

        AuditLogService::log('activation_request', 'approve', "Approve request aktivasi dari {$user->name} ({$activationRequest->durasiLabel()}) + kode aktivasi dibuat");

        return back()->with('success', "Request dari {$user->name} di-approve & kode aktivasi cabang dibuat. Durasi: {$activationRequest->durasiLabel()}");
    }

    /**
     * Super Admin: Reject request
     */
    public function reject(Request $request, ActivationRequest $activationRequest)
    {
        $request->validate([
            'admin_note' => 'required|string|min:5|max:500',
        ]);

        if (!in_array($activationRequest->status, ['pending', 'processing'])) {
            return back()->with('error', 'Request ini sudah diproses.');
        }

        $activationRequest->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'admin_note' => $request->admin_note,
        ]);

        AuditLogService::log('activation_request', 'reject', "Reject request aktivasi dari {$activationRequest->user?->name}. Alasan: {$request->admin_note}");

        return back()->with('success', 'Request berhasil ditolak.');
    }

    /**
     * Super Admin: View detail request
     */
    public function show(ActivationRequest $activationRequest)
    {
        $activationRequest->load(['user', 'cabang', 'approvedBy']);
        return view('activation-request.show', compact('activationRequest'));
    }

    /**
     * CONTROL: Status Aktivasi seluruh cabang (Aktif/Nonaktif/Expired)
     */
    public function statusIndex()
    {
        $cabangs = \App\Models\Cabang::with(['users' => fn ($q) => $q->where('role_id', 2)])
            ->orderBy('nama')->get();

        $kodeAktivasi = \App\Models\ActivationCode::with('activatedBy')
            ->whereNotNull('cabang_id')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('cabang_id');

        $data = $cabangs->map(function ($cabang) use ($kodeAktivasi) {
            $kode = $kodeAktivasi->get($cabang->id)?->first();
            $adminCabang = $cabang->users->first();

            // Status aktivasi: dari kode aktivasi terakhir + user expiry
            $status = 'nonaktif';
            $berakhir = null;
            if ($kode && $kode->status === 'aktif') {
                $berakhir = $kode->berakhir_berlaku;
                if ($berakhir && $berakhir->isPast()) {
                    $status = 'expired';
                } else {
                    $status = 'aktif';
                }
            } elseif ($adminCabang) {
                if ($adminCabang->is_permanent) {
                    $status = 'aktif';
                } elseif ($adminCabang->login_expires_at) {
                    $berakhir = $adminCabang->login_expires_at;
                    $status = $berakhir->isPast() ? 'expired' : 'aktif';
                }
            }

            return (object) [
                'cabang' => $cabang,
                'kode' => $kode,
                'admin_cabang' => $adminCabang,
                'status' => $status,
                'berakhir' => $berakhir,
                'days_left' => $berakhir ? (int) now()->diffInDays($berakhir, false) : null,
            ];
        });

        return view('activation-request.status', compact('data'));
    }

    /**
     * CONTROL: Role & Permission (ringkasan peran + jumlah user)
     */
    public function rolesIndex()
    {
        $roles = \App\Models\Role::withCount('users')->get();

        $permissionMap = [
            'Super Admin' => 'Akses penuh semua modul: Control Aktivasi, Kode Aktivasi, Status Aktivasi, Paket/Langganan, Kelola User, Role & Permission, Audit Log, Kelola Website, Multi Bahasa',
            'Admin' => 'Servis, Invoice Sparepart (Retail/Grosir/Reseller/Member), Master (Pelanggan, Sparepart, Harga, Harga Grosir, Cabang, Gudang), Stok, Piutang, Retur, Laporan, Kas, Pembelian Supplier, WhatsApp, Pengaturan, Kelola Akun',
            'Admin Cabang Anak' => 'Invoice Sparepart, Master & Stok sesuai cabang sendiri (terkunci per cabang), Pembelian Supplier, Aktivitas Sparepart',
            'Staff' => 'Input & kelola servis, arsip servis, laporan keuangan, cetak nota, POS penjualan sparepart',
            'Teknisi' => 'Dashboard teknisi: daftar servis yang dikerjakan, update status perbaikan',
            'User' => 'Layanan servis pelanggan: daftar servis miliknya, register servis, lacak status',
        ];

        return view('control.roles', compact('roles', 'permissionMap'));
    }
}
