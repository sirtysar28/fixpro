<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Servis;
use App\Models\Teknisi;
use App\Models\Pelanggan;
use App\Models\Stok;
use App\Models\Kas;
use App\Models\JualBeli;
use App\Models\Cabang;
use App\Models\User;
use App\Models\BannerIklan;
use App\Models\Setting;
use App\Models\SerialNumber;
use Illuminate\Http\Request;

class MasterApiController extends Controller
{
    // ========== TEKNISI ==========
    public function teknisiIndex(Request $request)
    {
        $cabangId = $request->user()->getApiCabangId($request);
        $query = Teknisi::orderBy('nama');
        if ($cabangId !== null) {
            $query->where('cabang_id', $cabangId);
        }
        $data = $query->get();
        return response()->json(['data' => $data]);
    }

    public function teknisiStore(Request $request)
    {
        $v = $request->validate([
            'nama' => 'required', 'no_hp' => 'nullable', 'no_wa' => 'nullable',
            'spesialisasi' => 'nullable', 'alamat' => 'nullable',
            'cabang_id' => 'nullable|exists:cabang,id',
            'status' => 'nullable|in:Aktif,Nonaktif', 'aktif' => 'boolean',
        ]);
        // Normalize field names
        if (!isset($v['no_wa']) && isset($v['no_hp'])) $v['no_wa'] = $v['no_hp'];
        if (isset($v['status'])) $v['aktif'] = $v['status'] === 'Aktif';
        unset($v['status'], $v['no_hp']);
        $v['cabang_id'] = $v['cabang_id'] ?? $request->user()->getApiCabangId($request);
        $t = Teknisi::create($v);
        return response()->json(['data' => $t, 'message' => 'Teknisi ditambahkan!'], 201);
    }

    public function teknisiUpdate(Request $request, $id)
    {
        $t = Teknisi::findOrFail($id);
        $v = $request->validate([
            'nama' => 'required', 'no_hp' => 'nullable', 'no_wa' => 'nullable',
            'spesialisasi' => 'nullable', 'alamat' => 'nullable',
            'cabang_id' => 'nullable|exists:cabang,id',
            'status' => 'nullable|in:Aktif,Nonaktif', 'aktif' => 'boolean',
        ]);
        if (!isset($v['no_wa']) && isset($v['no_hp'])) $v['no_wa'] = $v['no_hp'];
        if (isset($v['status'])) $v['aktif'] = $v['status'] === 'Aktif';
        unset($v['status'], $v['no_hp']);
        $t->update($v);
        return response()->json(['data' => $t, 'message' => 'Teknisi diupdate!']);
    }

    public function teknisiDestroy($id)
    {
        Teknisi::findOrFail($id)->delete();
        return response()->json(['message' => 'Teknisi dihapus!']);
    }

    // ========== PELANGGAN ==========
    public function pelangganIndex(Request $request)
    {
        $q = $request->filled('search') ? Pelanggan::with('user')->where('nama', 'like', "%{$request->search}%")->orWhere('no_hp', 'like', "%{$request->search}%") : Pelanggan::with('user');
        return response()->json(['data' => $q->orderBy('nama')->get()]);
    }

    public function pelangganStore(Request $request)
    {
        $v = $request->validate(['nama' => 'required', 'no_hp' => 'required', 'alamat' => 'nullable']);

        \DB::beginTransaction();
        try {
            $existingUser = User::where('phone', $v['no_hp'])->first();

            if ($existingUser) {
                $p = Pelanggan::create(array_merge($v, ['user_id' => $existingUser->id]));
            } else {
                $email = $v['no_hp'] . '@fixpro.local';
                $counter = 1;
                while (User::where('email', $email)->exists()) {
                    $email = $v['no_hp'] . "_{$counter}@fixpro.local";
                    $counter++;
                }

                $user = User::create([
                    'name' => $v['nama'],
                    'email' => $email,
                    'password' => bcrypt($v['no_hp']),
                    'phone' => $v['no_hp'],
                    'role_id' => 3,
                    'cabang_id' => 1,
                    'is_active' => true,
                    'is_permanent' => false,
                    'login_expires_at' => now()->addMonth(),
                ]);

                $p = Pelanggan::create(array_merge($v, ['user_id' => $user->id]));
            }

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }

        return response()->json(['data' => $p->load('user'), 'message' => 'Pelanggan ditambahkan! Akun user otomatis dibuat.'], 201);
    }

    public function pelangganUpdate(Request $request, $id)
    {
        $p = Pelanggan::findOrFail($id);
        $v = $request->validate(['nama' => 'required', 'no_hp' => 'required', 'alamat' => 'nullable']);

        \DB::beginTransaction();
        try {
            $p->update($v);
            // Sinkron ke user
            if ($p->user) {
                $p->user->update(['name' => $v['nama'], 'phone' => $v['no_hp']]);
            }
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }

        return response()->json(['data' => $p->load('user'), 'message' => 'Pelanggan diupdate!']);
    }

    public function pelangganDestroy($id)
    {
        Pelanggan::findOrFail($id)->delete();
        return response()->json(['message' => 'Pelanggan dihapus!']);
    }

    // ========== STOK ==========
    public function stokIndex(Request $request)
    {
        // Stok per cabang — JANGAN campur antar toko.
        // (null hanya terjadi untuk super admin yang eksplisit minta 'all')
        $cabangId = $request->user()->getApiCabangId($request);
        $query = Stok::orderBy('nama');
        if ($cabangId !== null) {
            $query->where('cabang_id', $cabangId);
        }
        return response()->json(['data' => $query->get()]);
    }

    public function stokStore(Request $request)
    {
        $v = $request->validate([
            'kode' => 'nullable', 'nama' => 'required', 'kategori' => 'nullable',
            'stok' => 'integer', 'modal' => 'numeric', 'jual' => 'numeric',
            'min_stok' => 'integer', 'min_alert' => 'integer',
        ]);
        if (!isset($v['min_alert']) && isset($v['min_stok'])) $v['min_alert'] = $v['min_stok'];
        if (empty($v['kode'])) $v['kode'] = 'SP-' . strtoupper(substr($v['nama'], 0, 3)) . rand(100, 999);
        unset($v['min_stok']);
        // Barang baru wajib punya cabang (super admin 'all' → fallback ke cabangnya sendiri)
        $apiCabang = $request->user()->getApiCabangId($request);
        $v['cabang_id'] = $apiCabang ?? (int) ($request->user()->cabang_id ?? 1);
        $s = Stok::create($v);
        return response()->json(['data' => $s, 'message' => 'Stok ditambahkan!'], 201);
    }

    public function stokUpdate(Request $request, $id)
    {
        $s = Stok::findOrFail($id);

        // Guard: hanya boleh ubah stok milik cabang sendiri/grup sendiri
        $this->assertStokOwnership($request, $s);

        $v = $request->validate([
            'kode' => 'nullable', 'nama' => 'required', 'kategori' => 'nullable',
            'stok' => 'integer', 'modal' => 'numeric', 'jual' => 'numeric',
            'min_stok' => 'integer', 'min_alert' => 'integer',
        ]);
        if (!isset($v['min_alert']) && isset($v['min_stok'])) $v['min_alert'] = $v['min_stok'];
        unset($v['min_stok']);
        $s->update($v);
        return response()->json(['data' => $s, 'message' => 'Stok diupdate!']);
    }

    public function stokDestroy(Request $request, $id)
    {
        $s = Stok::findOrFail($id);

        // Guard: hanya boleh hapus stok milik cabang sendiri/grup sendiri
        $this->assertStokOwnership($request, $s);

        $s->delete();
        return response()->json(['message' => 'Stok dihapus!']);
    }

    /**
     * Pastikan sparepart yang diubah/dihapus lewat API milik cabang user
     * (Admin Cabang Anak terkunci ke cabangnya; Enterprise pusat boleh grupnya;
     * Super Admin bebas). Mencegah cabang anak mengubah stok toko lain.
     */
    private function assertStokOwnership(Request $request, Stok $stok): void
    {
        $user = $request->user();
        if ($user->isSuperAdmin()) return;

        if ($user->isEnterprise() && $user->isAdmin()) {
            $allowed = $user->getAllowedCabangIds();
            $stokCabang = $stok->cabang_id !== null ? (int) $stok->cabang_id : null;
            if ($stokCabang !== null && in_array($stokCabang, $allowed, true)) return;
            // Kompatibilitas data lama: sparepart tanpa cabang dianggap milik cabang default 1
            if ($stokCabang === null && (int) $user->getApiCabangId($request) === 1) return;
            abort(403, 'Sparepart ini bukan milik grup cabang Anda.');
        }

        $cabangId = (int) $user->getApiCabangId($request);
        if ((int) ($stok->cabang_id ?? 0) !== $cabangId) {
            // Kompatibilitas data lama: sparepart tanpa cabang hanya untuk cabang default 1
            if (!($stok->cabang_id === null && $cabangId === 1)) {
                abort(403, 'Anda hanya bisa mengelola stok cabang Anda sendiri.');
            }
        }
    }

    // ========== KAS ==========
    public function kasIndex(Request $request)
    {
        $cabangId = $request->user()->getApiCabangId($request);
        $query = Kas::orderBy('waktu', 'desc');
        if ($cabangId !== null) {
            $query->where('cabang_id', $cabangId);
        }
        if ($request->filled('date')) $query->whereDate('waktu', $request->date);
        $data = $query->paginate(25);
        $lastQuery = Kas::orderBy('waktu', 'desc');
        if ($cabangId !== null) {
            $lastQuery->where('cabang_id', $cabangId);
        }
        $last = $lastQuery->first();
        $today = now()->format('Y-m-d');
        $masukQuery = Kas::where('tipe', 'masuk')->whereDate('waktu', $today);
        $keluarQuery = Kas::where('tipe', 'keluar')->whereDate('waktu', $today);
        if ($cabangId !== null) {
            $masukQuery->where('cabang_id', $cabangId);
            $keluarQuery->where('cabang_id', $cabangId);
        }
        return response()->json([
            'data' => $data->through(fn($k) => [
                'id' => $k->id, 'tipe' => $k->tipe, 'kategori' => $k->kategori,
                'jml' => (float) $k->jml, 'ket' => $k->ket, 'metode' => $k->metode,
                'waktu' => $k->waktu?->format('d/m/Y H:i'), 'saldo' => (float) $k->saldo,
            ]),
            'saldo' => (float) ($last?->saldo ?? 0),
            'masuk_hari_ini' => (float) $masukQuery->sum('jml'),
            'keluar_hari_ini' => (float) $keluarQuery->sum('jml'),
        ]);
    }

    public function kasStore(Request $request)
    {
        $v = $request->validate([
            'tipe' => 'required|in:masuk,keluar', 'jml' => 'required|numeric|min:0',
            'kategori' => 'required', 'ket' => 'required', 'metode' => 'required|in:Cash,Transfer,QRIS',
        ]);
        $cabangId = $request->user()->getApiCabangId($request);
        $lastQuery = Kas::orderBy('waktu', 'desc');
        if ($cabangId !== null) {
            $lastQuery->where('cabang_id', $cabangId);
        }
        $last = $lastQuery->first();
        $lastSaldo = $last ? $last->saldo : 0;
        $newSaldo = $v['tipe'] === 'masuk' ? $lastSaldo + $v['jml'] : $lastSaldo - $v['jml'];
        $k = Kas::create(array_merge($v, ['cabang_id' => $cabangId, 'waktu' => now(), 'saldo' => $newSaldo]));
        return response()->json(['data' => $k, 'message' => 'Transaksi kas berhasil!'], 201);
    }

    public function kasDestroy($id)
    {
        Kas::findOrFail($id)->delete();
        return response()->json(['message' => 'Transaksi dihapus!']);
    }

    // ========== JUAL BELI ==========
    public function jualbeliIndex(Request $request)
    {
        $cabangId = $request->user()->getApiCabangId($request);
        $query = JualBeli::orderBy('created_at', 'desc');
        if ($cabangId !== null) {
            $query->where('cabang_id', $cabangId);
        }
        $data = $query->get()->map(fn($j) => [
            'id' => $j->id, 'kode' => $j->kode, 'perangkat' => $j->hp, 'tipe' => $j->tipe,
            'imei' => $j->imei, 'harga_beli' => (float) $j->harga_beli, 'harga' => (float) $j->harga,
            'pelanggan' => $j->pelanggan, 'no_hp_pelanggan' => $j->no_hp_pelanggan,
            'metode_bayar' => $j->metode_bayar, 'kondisi' => $j->kondisi,
            'kelengkapan' => $j->kelengkapan, 'catatan' => $j->catatan,
            'status' => $j->status, 'alasan_pembatalan' => $j->alasan_pembatalan,
            'tanggal' => $j->tanggal?->format('d/m/Y'),
        ]);
        return response()->json(['data' => $data]);
    }

    public function jualbeliStore(Request $request)
    {
        $v = $request->validate([
            'perangkat' => 'required', 'harga' => 'numeric|min:0',
            'harga_beli' => 'numeric|min:0', 'pelanggan' => 'nullable',
            'catatan' => 'nullable', 'kondisi' => 'nullable',
            'kelengkapan' => 'nullable', 'metode_bayar' => 'nullable',
            'imei' => 'nullable', 'tipe' => 'nullable',
        ]);

        $kode = JualBeli::generateKode();
        $cabangId = $request->user()->getApiCabangId($request);

        $j = JualBeli::create(array_merge($v, [
            'kode' => $kode,
            'cabang_id' => $cabangId,
            'user_id' => $request->user()->id,
            'tanggal' => now(),
            'status' => 'Tersedia',
            'hp' => $v['perangkat'],
        ]));

        return response()->json(['data' => $j, 'message' => 'Transaksi ditambahkan!'], 201);
    }

    public function jualbeliUpdate(Request $request, $id)
    {
        $j = JualBeli::findOrFail($id);
        $v = $request->validate([
            'perangkat' => 'required', 'harga' => 'numeric|min:0',
            'harga_beli' => 'numeric|min:0', 'pelanggan' => 'nullable',
            'catatan' => 'nullable', 'kondisi' => 'nullable',
            'kelengkapan' => 'nullable', 'metode_bayar' => 'nullable',
            'imei' => 'nullable', 'tipe' => 'nullable',
        ]);

        $j->update(array_merge($v, ['hp' => $v['perangkat']]));
        return response()->json(['data' => $j, 'message' => 'Data diupdate!']);
    }

    public function jualbeliDestroy($id)
    {
        JualBeli::findOrFail($id)->delete();
        return response()->json(['message' => 'Data dihapus!']);
    }

    // ========== LAPORAN ==========
    public function laporanIndex(Request $request)
    {
        $cabangId = $request->user()->getApiCabangId($request);
        $query = Servis::with(['pelanggan', 'teknisi']);
        if ($cabangId !== null) {
            $query->where('cabang_id', $cabangId);
        }
        if ($request->filled('dari')) $query->whereDate('tanggal', '>=', $request->dari);
        if ($request->filled('sampai')) $query->whereDate('tanggal', '<=', $request->sampai);
        if ($request->filled('status')) $query->where('status', $request->status);

        $servis = $query->orderBy('tanggal', 'desc')->get();
        $totalOmset = $servis->where('status', 'Selesai')->sum('biaya');
        $totalServis = $servis->count();
        $totalSelesai = $servis->where('status', 'Selesai')->count();

        $teknisiPerf = \App\Models\Teknisi::with(['servis' => function ($q) use ($cabangId) {
            $q->where('status', 'Selesai');
            if ($cabangId !== null) {
                $q->where('cabang_id', $cabangId);
            }
        }])->where('aktif', true);
        if ($cabangId !== null) {
            $teknisiPerf->where('cabang_id', $cabangId);
        }

        return response()->json([
            'total_omset' => (float) $totalOmset,
            'total_servis' => $totalServis,
            'total_selesai' => $totalSelesai,
            'teknisi_perf' => $teknisiPerf,
        ]);
    }

    // ========== CABANG (CRUD) ==========
    public function cabangFullIndex()
    {
        $cabangs = Cabang::orderBy('aktif', 'desc')->orderBy('nama')->get();
        $stats = $cabangs->map(function ($c) {
            return [
                'id' => $c->id, 'nama' => $c->nama, 'alamat' => $c->alamat, 'telp' => $c->telp, 'aktif' => $c->aktif,
                'total_servis' => \App\Models\Servis::where('cabang_id', $c->id)->count(),
                'omset' => (float) \App\Models\Servis::where('cabang_id', $c->id)->where('status', 'Selesai')->sum('biaya'),
                'pengeluaran' => (float) Kas::where('cabang_id', $c->id)->where('tipe', 'keluar')->sum('jml'),
            ];
        });
        return response()->json(['data' => $stats]);
    }

    public function cabangStore(Request $request)
    {
        $v = $request->validate(['nama' => 'required', 'alamat' => 'nullable', 'telp' => 'nullable']);
        $c = Cabang::create(array_merge($v, ['aktif' => true]));
        return response()->json(['data' => $c, 'message' => 'Cabang ditambahkan!'], 201);
    }

    public function cabangUpdate(Request $request, $id)
    {
        $c = Cabang::findOrFail($id);
        $v = $request->validate(['nama' => 'required', 'alamat' => 'nullable', 'telp' => 'nullable', 'aktif' => 'boolean']);
        $c->update($v);
        return response()->json(['data' => $c, 'message' => 'Cabang diupdate!']);
    }

    public function cabangDestroy($id)
    {
        Cabang::findOrFail($id)->delete();
        return response()->json(['message' => 'Cabang dihapus!']);
    }

    // ========== USER MANAGEMENT (Super Admin) ==========
    public function userIndex()
    {
        $users = User::with(['role', 'cabang'])->orderBy('name')->get()->map(fn($u) => [
            'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone,
            'role' => $u->role?->name, 'role_id' => $u->role_id,
            'cabang' => $u->cabang?->nama, 'cabang_id' => $u->cabang_id,
            'is_active' => $u->is_active, 'is_super_admin' => $u->is_super_admin,
        ]);
        $roles = \App\Models\Role::orderBy('id')->get();
        $cabangs = Cabang::where('aktif', true)->orderBy('nama')->get();
        return response()->json(['users' => $users, 'roles' => $roles, 'cabangs' => $cabangs]);
    }

    public function userStore(Request $request)
    {
        $v = $request->validate([
            'name' => 'required', 'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6', 'phone' => 'nullable',
            'role_id' => 'required|exists:roles,id', 'cabang_id' => 'required|exists:cabang,id',
        ]);
        $u = User::create(array_merge($v, ['password' => bcrypt($v['password']), 'is_active' => true]));

        // Kalau role User biasa → otomatis buat data pelanggan
        $role = \App\Models\Role::find($v['role_id']);
        if ($role && $role->name === 'User') {
            $existingPelanggan = Pelanggan::where('no_hp', $u->phone)->where('cabang_id', $u->cabang_id)->first();
            if ($existingPelanggan) {
                $existingPelanggan->update(['user_id' => $u->id]);
            } else {
                Pelanggan::create([
                    'user_id' => $u->id,
                    'nama' => $u->name,
                    'no_hp' => $u->phone ?? '-',
                    'alamat' => null,
                    'cabang_id' => $u->cabang_id ?? 1,
                ]);
            }
        }

        return response()->json(['message' => 'User ditambahkan!' . ($role && $role->name === 'User' ? ' Data pelanggan otomatis dibuat.' : '')], 201);
    }

    public function userUpdate(Request $request, $id)
    {
        $u = User::findOrFail($id);
        $v = $request->validate([
            'name' => 'required', 'email' => "required|email|unique:users,email,$id",
            'password' => 'nullable|min:6', 'phone' => 'nullable',
            'role_id' => 'required|exists:roles,id', 'cabang_id' => 'required|exists:cabang,id',
            'is_active' => 'boolean',
        ]);
        if (!empty($v['password'])) $v['password'] = bcrypt($v['password']);
        else unset($v['password']);
        $u->update($v);
        return response()->json(['message' => 'User diupdate!']);
    }

    public function userDestroy($id)
    {
        $u = User::findOrFail($id);
        if ($u->is_super_admin) return response()->json(['message' => 'Super Admin tidak bisa dihapus!'], 403);
        $u->delete();
        return response()->json(['message' => 'User dihapus!']);
    }

    public function userToggleSuper($id)
    {
        $u = User::findOrFail($id);
        if ($u->id === auth()->id()) return response()->json(['message' => 'Tidak bisa ubah diri sendiri'], 403);
        $u->update(['is_super_admin' => !$u->is_super_admin]);
        return response()->json(['message' => $u->is_super_admin ? 'Jadi Super Admin' : 'Cabut Super Admin']);
    }

    // ========== BANNER ==========
    public function bannerIndex()
    {
        $banners = BannerIklan::orderBy('urutan')->get()->map(fn($b) => [
            'id' => $b->id, 'judul' => $b->judul, 'deskripsi' => $b->deskripsi,
            'gambar_url' => asset('storage/' . $b->gambar), 'link' => $b->link,
            'aktif' => $b->aktif, 'urutan' => $b->urutan,
        ]);
        return response()->json(['data' => $banners]);
    }

    // ========== STOK LIST (for dropdown) ==========
    public function stokList(Request $request)
    {
        $cabangId = $request->user()->getApiCabangId($request);
        $query = Stok::orderBy('nama');
        if ($cabangId !== null) {
            // HANYA stok milik cabang aktif — jangan sertakan barang lama tanpa
            // cabang (milik toko lain) supaya tidak kecampur antar toko
            $query->where('cabang_id', $cabangId);
        }
        return response()->json(['data' => $query->get()]);
    }

    // ========== TEKNISI DASHBOARD ==========
    public function teknisiDashboard(Request $request)
    {
        $user = $request->user();
        $cabangId = $user->getApiCabangId($request);

        // Find teknisi linked to this user (only within their branch)
        $teknisiQuery = Teknisi::where('aktif', true);
        if ($cabangId !== null) {
            $teknisiQuery->where('cabang_id', $cabangId);
        }
        $teknisi = $teknisiQuery->first();
        $teknisiId = $teknisi ? $teknisi->id : null;

        $today = now()->format('Y-m-d');
        $monthStart = now()->startOfMonth()->format('Y-m-d');

        $query = Servis::query();
        if ($cabangId !== null) {
            $query->where('cabang_id', $cabangId);
        }
        if ($teknisiId) $query->where('teknisi_id', $teknisiId);

        $proses = (clone $query)->where('status', 'Proses')->count();
        $selesai = (clone $query)->where('status', 'Selesai')->whereDate('tanggal', '>=', $monthStart)->count();
        $pending = (clone $query)->where('status', 'Pending')->count();
        $total = (clone $query)->whereDate('tanggal', '>=', $monthStart)->count();

        $tasks = (clone $query)->where('status', 'Masuk')->orWhere('status', 'Proses')
            ->with(['pelanggan', 'teknisi'])
            ->orderBy('created_at', 'desc')->take(20)->get()
            ->map(fn($s) => [
                'id' => $s->id, 'kode' => $s->kode, 'perangkat' => $s->perangkat,
                'keluhan' => $s->keluhan, 'status' => $s->status, 'prioritas' => $s->prioritas,
                'biaya' => (float) $s->biaya, 'pelanggan' => $s->pelanggan?->nama,
                'pelanggan_hp' => $s->pelanggan?->no_hp, 'teknisi' => $s->teknisi?->nama,
                'tanggal' => $s->tanggal?->format('d/m/Y'),
                'spareparts' => [],
            ]);

        return response()->json([
            'stats' => [
                'nama' => $teknisi ? $teknisi->nama : $user->name,
                'proses' => $proses,
                'selesai' => $selesai,
                'pending' => $pending,
                'total' => $total,
            ],
            'tasks' => $tasks,
        ]);
    }

    // ========== LAPORAN KEUANGAN ==========
    public function laporanKeuanganIndex(Request $request)
    {
        $cabangId = $request->user()->getApiCabangId($request);
        $dari = $request->filled('dari') ? $request->dari : null;
        $sampai = $request->filled('sampai') ? $request->sampai : now()->format('Y-m-d');

        // ════════════════════════════════════════
        //  SERVIS SELESAI (pakai tgl_diambil seperti web)
        // ════════════════════════════════════════
        $servisQuery = Servis::with(['pelanggan', 'teknisi', 'cabang'])
            ->where('status', 'Selesai');
        if ($cabangId !== null) {
            $servisQuery->where('cabang_id', $cabangId);
        }
        if ($dari) {
            $servisQuery->whereDate('tgl_diambil', '>=', $dari);
        }
        if ($sampai) {
            $servisQuery->whereDate('tgl_diambil', '<=', $sampai);
        }
        $servisSelesai = $servisQuery->orderBy('tgl_diambil', 'desc')->get();

        // ════════════════════════════════════════
        //  HITUNG HARGA JUAL & MODAL SPAREPART PER SERVIS
        //  (sama seperti web: dari field JSON spareparts)
        // ════════════════════════════════════════
        $servisSelesai->each(function ($s) {
            $hargaJualSp = 0;
            if (is_array($s->spareparts)) {
                foreach ($s->spareparts as $sp) {
                    $hargaJualSp += (float) ($sp['harga'] ?? 0) * (int) ($sp['qty'] ?? 1);
                }
            }
            $s->harga_jual_sp   = $hargaJualSp;
            $s->modal_sp        = (float) ($s->modal_sparepart ?? 0);
            $s->laba_sp_servis  = $hargaJualSp - $s->modal_sp;
            $s->laba_total      = (float) $s->biaya - $hargaJualSp;
        });

        $totalOmsetServis       = $servisSelesai->sum('biaya');
        $totalHargaJualSpServis = $servisSelesai->sum('harga_jual_sp');
        $totalModalSpServis     = $servisSelesai->sum('modal_sp');
        $labaSpServis           = $servisSelesai->sum('laba_sp_servis');
        $labaServis             = $servisSelesai->sum('laba_total'); // biaya - harga_jual_sp

        $servisData = $servisSelesai->map(fn($s) => [
            'id' => $s->id,
            'kode' => $s->kode,
            'perangkat' => $s->perangkat,
            'pelanggan' => $s->pelanggan?->nama,
            'teknisi' => $s->teknisi?->nama,
            'tanggal' => $s->tgl_diambil?->format('d/m/Y'),
            'biaya' => (float) $s->biaya,
            'harga_jual_sp' => (float) $s->harga_jual_sp,
            'modal_sp' => (float) $s->modal_sp,
            'laba_sp' => (float) $s->laba_sp_servis,
            'laba_bersih' => (float) $s->laba_total,
        ]);

        // ════════════════════════════════════════
        //  PENJUALAN SPAREPART POS (sama seperti web)
        // ════════════════════════════════════════
        $spQuery = \App\Models\PenjualanSparepart::with(['stok', 'pelanggan']);
        if ($cabangId !== null) {
            $spQuery->where('cabang_id', $cabangId);
        }
        if ($dari) {
            $spQuery->whereDate('tanggal', '>=', $dari);
        }
        if ($sampai) {
            $spQuery->whereDate('tanggal', '<=', $sampai);
        }
        $spQuery->where('status', '!=', 'Dibatalkan');
        $penjualanSp = $spQuery->orderBy('tanggal', 'desc')->get();

        $totalOmsetSp = $penjualanSp->sum('total');
        $totalModalSp = $penjualanSp->sum('modal_total');
        $labaSp = $totalOmsetSp - $totalModalSp;

        $spData = $penjualanSp->map(fn($p) => [
            'id' => $p->id,
            'no_transaksi' => $p->kode,
            'tanggal' => $p->tanggal?->format('d/m/Y'),
            'pelanggan' => $p->pelanggan?->nama ?? 'Umum',
            'total' => (float) $p->total,
            'modal_total' => (float) $p->modal_total,
        ]);

        // ════════════════════════════════════════
        //  KAS (sama seperti web)
        // ════════════════════════════════════════
        $kasMasukQuery = Kas::where('tipe', 'masuk');
        $kasKeluarQuery = Kas::where('tipe', 'keluar');
        if ($cabangId !== null) {
            $kasMasukQuery->where('cabang_id', $cabangId);
            $kasKeluarQuery->where('cabang_id', $cabangId);
        }
        if ($dari) {
            $kasMasukQuery->whereDate('waktu', '>=', $dari);
            $kasKeluarQuery->whereDate('waktu', '>=', $dari);
        }
        if ($sampai) {
            $kasMasukQuery->whereDate('waktu', '<=', $sampai);
            $kasKeluarQuery->whereDate('waktu', '<=', $sampai);
        }
        $kasMasuk = $kasMasukQuery->sum('jml');
        $kasKeluar = $kasKeluarQuery->sum('jml');

        // ════════════════════════════════════════
        //  PERHITUNGAN FINAL (sama seperti web)
        // ════════════════════════════════════════
        $labaBersih = $labaServis + $labaSpServis + $labaSp;
        $totalOmset = $totalOmsetServis + $totalOmsetSp;
        $totalModal = $totalModalSpServis + $totalModalSp;
        $margin = $totalOmset > 0 ? round(($labaBersih / $totalOmset) * 100) : 0;
        $totalTransaksi = $servisSelesai->count() + $penjualanSp->count();

        return response()->json([
            'laba_bersih' => (float) $labaBersih,
            'laba_servis' => (float) $labaServis,
            'laba_sp_servis' => (float) $labaSpServis,
            'laba_sp' => (float) $labaSp,
            'total_omset' => (float) $totalOmset,
            'total_modal' => (float) $totalModal,
            'margin' => (int) $margin,
            'total_transaksi' => $totalTransaksi,
            'total_transaksi_servis' => $servisSelesai->count(),
            'total_transaksi_sp' => $penjualanSp->count(),
            'total_omset_servis' => (float) $totalOmsetServis,
            'total_harga_jual_sp_servis' => (float) $totalHargaJualSpServis,
            'total_modal_servis_sp' => (float) $totalModalSpServis,
            'kas_masuk' => (float) $kasMasuk,
            'kas_keluar' => (float) $kasKeluar,
            'servis_selesai' => $servisData,
            'penjualan_sp' => $spData,
        ]);
    }

    public function laporanKeuanganExport(Request $request)
    {
        return response()->json(['message' => 'Export tidak tersedia di mobile. Gunakan web version.', 'url' => null]);
    }

    // ========== TIPE HP ==========
    public function tipeHpIndex()
    {
        $data = \App\Models\TipeHp::orderBy('merk')->orderBy('tipe')->get();
        return response()->json(['data' => $data->map(fn($t) => [
            'id' => $t->id,
            'merk' => $t->merk,
            'nama' => $t->tipe,
            'tipe' => $t->tipe,
            'kategori' => $t->kategori,
            'aktif' => $t->aktif,
        ])]);
    }

    public function tipeHpStore(Request $request)
    {
        $v = $request->validate(['merk' => 'required', 'nama' => 'required']);
        $t = \App\Models\TipeHp::create(['merk' => $v['merk'], 'tipe' => $v['nama']]);
        return response()->json(['data' => $t, 'message' => 'Tipe HP ditambahkan!'], 201);
    }

    public function tipeHpUpdate(Request $request, $id)
    {
        $t = \App\Models\TipeHp::findOrFail($id);
        $v = $request->validate(['merk' => 'required', 'nama' => 'required']);
        $t->update(['merk' => $v['merk'], 'tipe' => $v['nama']]);
        return response()->json(['data' => $t, 'message' => 'Tipe HP diupdate!']);
    }

    public function tipeHpDestroy($id)
    {
        \App\Models\TipeHp::findOrFail($id)->delete();
        return response()->json(['message' => 'Tipe HP dihapus!']);
    }

    // ========== SETTINGS ==========
    public function settingsIndex()
    {
        return response()->json(['data' => Setting::pluck('value', 'key')]);
    }

    public function settingsUpdate(Request $request)
    {
        foreach ($request->all() as $key => $value) {
            Setting::set($key, $value);
        }
        return response()->json(['message' => 'Pengaturan disimpan!']);
    }

    // ========== TEKNISI LIST (for dropdown) ==========
    public function teknisiList(Request $request)
    {
        $cabangId = $request->user()->getApiCabangId($request);
        $query = Teknisi::where('aktif', true);
        if ($cabangId !== null) {
            $query->where('cabang_id', $cabangId);
        }
        return response()->json(['data' => $query->get(['id', 'nama', 'spesialisasi'])]);
    }

    // ========== PENJUALAN SPAREPART ==========
    public function penjualanSparepartIndex(Request $request)
    {
        $cabangId = $request->user()->getApiCabangId($request);
        $query = \App\Models\PenjualanSparepart::with(['stok', 'pelanggan', 'user']);
        if ($cabangId !== null) {
            $query->where('cabang_id', $cabangId);
        }

        if ($request->filled('date')) $query->whereDate('tanggal', $request->date);

        $data = $query->orderBy('created_at', 'desc')->paginate(25);
        $today = now()->format('Y-m-d');
        return response()->json([
            'data' => $data->through(fn($p) => [
                'id' => $p->id, 'kode' => $p->kode,
                'sparepart' => $p->stok?->nama, 'qty' => $p->qty,
                'harga_satuan' => (float) $p->harga_satuan, 'total' => (float) $p->total,
                'laba' => (float) ($p->total - $p->modal_total),
                'metode_bayar' => $p->metode_bayar,
                'pelanggan' => $p->pelanggan?->nama ?? 'Umum',
                'tanggal' => $p->tanggal?->format('d/m/Y'),
                'catatan' => $p->catatan,
            ]),
            'omset_hari_ini' => (float) \App\Models\PenjualanSparepart::where('cabang_id', $cabangId)->whereDate('tanggal', $today)->sum('total'),
        ]);
    }

    public function penjualanSparepartStore(Request $request)
    {
        $v = $request->validate([
            'stok_id' => 'required|exists:stoks,id',
            'qty' => 'required|integer|min:1',
            'harga_satuan' => 'required|numeric|min:0',
            'metode_bayar' => 'required|in:Cash,Transfer,QRIS',
            'pelanggan_id' => 'nullable|exists:pelanggans,id',
            'catatan' => 'nullable',
        ]);

        $stok = Stok::find($v['stok_id']);

        // Guard: hanya boleh jual stok milik cabang sendiri (jangan kurangi stok toko lain)
        if ($stok) {
            $this->assertStokOwnership($request, $stok);
        }

        if (!$stok || $stok->stok < $v['qty']) {
            return response()->json(['message' => 'Stok tidak cukup'], 400);
        }

        \DB::beginTransaction();
        try {
            $total = $v['qty'] * $v['harga_satuan'];
            $p = \App\Models\PenjualanSparepart::create([
                'stok_id' => $v['stok_id'],
                'pelanggan_id' => $v['pelanggan_id'] ?? null,
                'cabang_id' => $request->user()->getApiCabangId($request),
                'user_id' => $request->user()->id,
                'kode' => \App\Models\PenjualanSparepart::generateKode(),
                'qty' => $v['qty'],
                'harga_satuan' => $v['harga_satuan'],
                'total' => $total,
                'modal_total' => $v['qty'] * $stok->modal,
                'metode_bayar' => $v['metode_bayar'],
                'catatan' => $v['catatan'] ?? null,
                'tanggal' => now()->format('Y-m-d'),
            ]);
            $stok->decrement('stok', $v['qty']);
            // Catat pergerakan stok (Kartu Stok) — dari API/mobile
            \App\Services\SparepartMovementService::record($stok, 'keluar', 'penjualan', (int) $v['qty'], [
                'referensi'       => $p->kode,
                'referensi_id'    => $p->id,
                'referensi_model' => $p,
                'harga_satuan'    => $v['harga_satuan'],
                'metode'          => $v['metode_bayar'],
                'cabang_id'       => $p->cabang_id,
            ]);
            \DB::commit();
            return response()->json(['data' => $p, 'message' => 'Penjualan berhasil!'], 201);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function penjualanSparepartShow($id)
    {
        $p = \App\Models\PenjualanSparepart::with(['stok', 'pelanggan', 'user', 'cabang'])->findOrFail($id);
        return response()->json(['data' => [
            'id' => $p->id, 'kode' => $p->kode,
            'sparepart' => $p->stok?->nama, 'qty' => $p->qty,
            'harga_satuan' => (float) $p->harga_satuan, 'total' => (float) $p->total,
            'laba' => (float) ($p->total - $p->modal_total),
            'metode_bayar' => $p->metode_bayar,
            'pelanggan' => $p->pelanggan?->nama ?? 'Umum',
            'tanggal' => $p->tanggal?->format('d/m/Y H:i'),
            'catatan' => $p->catatan,
            'cabang' => $p->cabang?->nama,
            'kasir' => $p->user?->name,
        ]]);
    }

    // ========== SERIAL NUMBER (Admin only) ==========
    public function serialIndex()
    {
        $user = request()->user();
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $serials = SerialNumber::with(['creator:id,name', 'usedBy:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json(['data' => $serials]);
    }

    public function serialGenerate(Request $request)
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate(['email' => 'required|email|exists:users,email']);

        $serial = SerialNumber::generateFromEmail($request->email, $user->id);

        return response()->json([
            'data' => $serial,
            'message' => 'Serial Number berhasil dibuat!',
        ], 201);
    }

    public function serialDestroy($id)
    {
        $user = request()->user();
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $serial = SerialNumber::findOrFail($id);
        if ($serial->is_used) {
            return response()->json(['message' => 'Serial yang sudah dipakai tidak bisa dihapus.'], 400);
        }

        $serial->delete();
        return response()->json(['message' => 'Serial Number dihapus.']);
    }

    // ========== AUDIT LOG (Super Admin only) ==========
    public function auditLogIndex(Request $request)
    {
        $user = $request->user();
        if (!$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = \App\Models\AuditLog::with('user:id,name,email');

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json(['data' => $logs]);
    }

    public function auditLogShow($id)
    {
        $user = request()->user();
        if (!$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $log = \App\Models\AuditLog::with('user')->findOrFail($id);

        return response()->json(['data' => [
            'id' => $log->id,
            'user' => $log->user?->name ?? 'System',
            'module' => $log->module,
            'action' => $log->action,
            'description' => $log->description,
            'ip_address' => $log->ip_address,
            'old_values' => $log->old_values,
            'new_values' => $log->new_values,
            'created_at' => $log->created_at->toIso8601String(),
        ]]);
    }
}
