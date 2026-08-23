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

        // Cek apakah ada request pending
        $hasPending = ActivationRequest::where('user_id', $user->id)
            ->where('status', 'pending')
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

        // Cek pending request
        if (ActivationRequest::where('user_id', $user->id)->where('status', 'pending')->exists()) {
            return back()->with('error', 'Anda sudah memiliki request yang sedang diproses. Harap tunggu.');
        }

        $validated = $request->validate([
            'durasi' => 'required|in:1_bulan,3_bulan,6_bulan,1_tahun,permanen',
            'nominal_bayar' => 'nullable|numeric|min:0',
            'bukti_transfer' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'catatan' => 'nullable|string|max:500',
        ]);

        $buktiPath = $request->file('bukti_transfer')->store('bukti-transfer', 'public');

        ActivationRequest::create([
            'user_id' => $user->id,
            'cabang_id' => $user->cabang_id,
            'nama_toko' => $user->cabang?->nama,
            'status' => 'pending',
            'durasi' => $validated['durasi'],
            'nominal_bayar' => $validated['nominal_bayar'],
            'bukti_transfer' => $buktiPath,
            'catatan' => $validated['catatan'],
        ]);

        AuditLogService::log('activation_request', 'create', "Request aktivasi durasi {$validated['durasi']} dari {$user->name} ({$user->cabang?->nama})");

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
     * Super Admin: Approve request
     */
    public function approve(Request $request, ActivationRequest $activationRequest)
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        if ($activationRequest->status !== 'pending') {
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
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'admin_note' => $request->admin_note,
        ]);

        AuditLogService::log('activation_request', 'approve', "Approve request aktivasi dari {$user->name} ({$activationRequest->durasiLabel()})");

        return back()->with('success', "Request dari {$user->name} sudah di-approve. Durasi: {$activationRequest->durasiLabel()}");
    }

    /**
     * Super Admin: Reject request
     */
    public function reject(Request $request, ActivationRequest $activationRequest)
    {
        $request->validate([
            'admin_note' => 'required|string|min:5|max:500',
        ]);

        if ($activationRequest->status !== 'pending') {
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
}
