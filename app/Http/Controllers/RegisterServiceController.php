<?php

namespace App\Http\Controllers;

use App\Models\Servis;
use App\Models\Pelanggan;
use App\Models\User;
use App\Models\Cabang;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterServiceController extends Controller
{
    public function index()
    {
        if (auth()->user()->isAdmin() || auth()->user()->isStaff()) {
            // Admin/Staff cabang hanya lihat servis di cabang sendiri
            // Super Admin lihat semua
            $query = Servis::with(['pelanggan', 'teknisi', 'cabang']);
            if (!auth()->user()->isSuperAdmin()) {
                $query->where('cabang_id', auth()->user()->cabang_id);
            }
            $servis = $query->orderBy('created_at', 'desc')->paginate(10);
        } else {
            // For users, show services linked via pelanggan.user_id or by phone/name
            $user = auth()->user();
            $servis = Servis::with(['pelanggan', 'teknisi', 'cabang'])
                ->whereHas('pelanggan', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('no_hp', $user->phone)
                      ->orWhere('nama', $user->name);
                })->orderBy('created_at', 'desc')->paginate(10);
        }
        return view('my-service.index', compact('servis'));
    }

    public function create()
    {
        $user = auth()->user();

        // Super Admin bisa lihat semua cabang, yang lain hanya cabang sendiri
        if ($user->isSuperAdmin()) {
            $cabangs = Cabang::where('aktif', true)->orderBy('nama')->get();
        } else {
            $cabangs = Cabang::where('aktif', true)
                ->where('id', $user->cabang_id)
                ->orderBy('nama')->get();
        }

        return view('my-service.create', compact('cabangs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required',
            'no_hp' => 'required',
            'perangkat' => 'required',
            'tipe' => 'required|in:Apple,Android',
            'imei' => 'nullable|max:20',
            'keluhan' => 'required',
            'catatan' => 'nullable',
            'cabang_id' => 'required|exists:cabang,id',
        ]);

        $user = auth()->user();

        // Cari atau buat pelanggan di cabang ini
        $cabangId = auth()->user()->getEffectiveCabangId();
        $pelanggan = Pelanggan::where('no_hp', $validated['no_hp'])->where('cabang_id', $cabangId)->first();
        if ($pelanggan) {
            $pelanggan->update(['nama' => $validated['nama']]);
            // Link ke user kalau belum
            if (!$pelanggan->user_id && $user->role_id === 3) {
                $pelanggan->update(['user_id' => $user->id]);
            }
        } else {
            $pelanggan = Pelanggan::create([
                'user_id' => $user->role_id === 3 ? $user->id : null,
                'nama' => $validated['nama'],
                'no_hp' => $validated['no_hp'],
                'cabang_id' => $cabangId,
            ]);
            // Kalau belum punya user_id, cek/buat user
            if (!$pelanggan->user_id) {
                $existingUser = User::where('phone', $validated['no_hp'])->first();
                if ($existingUser) {
                    $pelanggan->update(['user_id' => $existingUser->id]);
                } else {
                    $newUser = User::create([
                        'name' => $validated['nama'],
                        'email' => $validated['no_hp'] . '@fixpro.local',
                        'password' => Hash::make($validated['no_hp']),
                        'phone' => $validated['no_hp'],
                        'role_id' => 3,
                        'cabang_id' => 1,
                        'is_active' => true,
                        'is_permanent' => false,
                        'login_expires_at' => now()->addMonth(),
                    ]);
                    $pelanggan->update(['user_id' => $newUser->id]);
                }
            }
        }

        $date = now()->format('ymd');
        $last = Servis::where('kode', 'like', "SVC-$date-%")->orderBy('id', 'desc')->first();
        $num = $last ? (int) substr($last->kode, -3) + 1 : 1;
        $kode = "SVC-$date-" . str_pad($num, 3, '0', STR_PAD_LEFT);

        $cabang = Cabang::find($validated['cabang_id']);

        Servis::create([
            'kode' => $kode,
            'pelanggan_id' => $pelanggan->id,
            'cabang_id' => $validated['cabang_id'],
            'sumber' => 'user',
            'perangkat' => $validated['perangkat'],
            'keluhan' => $validated['keluhan'],
            'tipe' => $validated['tipe'],
            'status' => 'Masuk',
            'tanggal' => now()->format('Y-m-d'),
            'imei' => $validated['imei'] ?? null,
            'catatan' => $validated['catatan'] ?? null,
            'garansi' => 30,
        ]);

        AuditLogService::log('servis', 'create', "User mendaftar servis dari web: {$kode} — {$validated['perangkat']}");

        return redirect()->route('my-service.index')->with('success', "Servis $kode berhasil didaftarkan ke cabang {$cabang->nama}! Silakan bawa HP ke toko cabang tersebut.");
    }

    public function show(Servis $servis)
    {
        $servis->load(['pelanggan', 'teknisi', 'cabang']);
        return view('my-service.show', compact('servis'));
    }

    /**
     * Update status servis dari my-service (Admin/Staff cabang only)
     */
    public function updateStatus(Request $request, Servis $servis)
    {
        $user = auth()->user();

        // Hanya Admin dan Staff yang bisa update status
        if (!$user->isAdmin() && !$user->isStaff()) {
            return redirect()->route('my-service.index')->with('error', 'Anda tidak memiliki akses untuk mengubah status servis.');
        }

        // Admin cabang hanya bisa update servis di cabang sendiri
        if (!$user->isSuperAdmin() && $servis->cabang_id != $user->cabang_id) {
            return redirect()->route('my-service.index')->with('error', 'Anda hanya bisa mengubah servis di cabang Anda sendiri.');
        }

        $request->validate([
            'status' => 'required|in:Masuk,Proses,Pending,Selesai,Dibatalkan',
        ]);

        $oldStatus = $servis->status;
        $servis->update(['status' => $request->status]);

        // Auto-set tanggal garansi & diambil jika status Selesai
        if ($request->status === 'Selesai' && $oldStatus !== 'Selesai') {
            $garansi = $servis->garansi ?? 30;
            $servis->update([
                'tanggal_garansi' => now()->addDays($garansi)->format('Y-m-d'),
                'diambil' => false,
            ]);
        }

        AuditLogService::log('servis', 'update-status', "Mengubah status servis {$servis->kode} dari {$oldStatus} → {$request->status} via My Service");

        return redirect()->route('my-service.show', $servis)->with('success', "Status servis {$servis->kode} berhasil diubah menjadi {$request->status}!");
    }

    /**
     * Hapus servis dari my-service (Super Admin & Admin Cabang only)
     */
    public function destroy(Servis $servis)
    {
        $user = auth()->user();

        // Hanya Super Admin dan Admin Cabang yang bisa hapus
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            return redirect()->route('my-service.index')->with('error', 'Anda tidak memiliki akses untuk menghapus servis.');
        }

        // Admin cabang hanya bisa hapus servis di cabang sendiri
        if (!$user->isSuperAdmin() && $servis->cabang_id != $user->cabang_id) {
            return redirect()->route('my-service.index')->with('error', 'Anda hanya bisa menghapus servis di cabang Anda sendiri.');
        }

        $kode = $servis->kode;
        $perangkat = $servis->perangkat;
        $pelangganNama = $servis->pelanggan?->nama ?? '-';
        $cabangNama = $servis->cabang?->nama ?? '-';

        // Kembalikan stok sparepart jika ada
        if ($servis->spareparts) {
            foreach ($servis->spareparts as $sp) {
                $stok = \App\Models\Stok::find($sp['id'] ?? null);
                if ($stok) {
                    $qtyKembali = (int) ($sp['qty'] ?? 1);
                    $stok->increment('stok', $qtyKembali);
                    // Catat pergerakan stok (Kartu Stok)
                    \App\Services\SparepartMovementService::record($stok, 'masuk', 'batal_pemakaian_servis', $qtyKembali, [
                        'referensi' => $servis->kode,
                        'cabang_id' => $servis->cabang_id,
                        'catatan'   => 'Servis dihapus: ' . $servis->kode,
                    ]);
                }
            }
        }

        // Koreksi Kas: kembalikan DP jika ada
        if ($servis->dp > 0) {
            $cabangId = $servis->cabang_id;
            $lastKas = \App\Models\Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
            $lastSaldo = $lastKas ? $lastKas->saldo : 0;
            \App\Models\Kas::create([
                'tipe' => 'keluar',
                'cabang_id' => $cabangId,
                'jml' => $servis->dp,
                'kategori' => 'Penghapusan Servis',
                'ket' => "Pengembalian DP servis {$kode} (dihapus)",
                'metode' => 'Cash',
                'ref' => 'HAPUS-DP-' . $kode,
                'waktu' => now(),
                'saldo' => $lastSaldo - $servis->dp,
            ]);
        }

        $servis->delete();

        AuditLogService::log('servis', 'delete', "Menghapus servis {$kode} — {$perangkat} (Pelanggan: {$pelangganNama}, Cabang: {$cabangNama})");

        return redirect()->route('my-service.index')->with('success', "Servis {$kode} berhasil dihapus dan stok sparepart dikembalikan.");
    }
}
