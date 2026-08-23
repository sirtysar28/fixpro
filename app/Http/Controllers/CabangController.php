<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Servis;
use App\Models\Teknisi;
use App\Models\Kas;
use App\Models\Stok;
use App\Models\StockTransfer;
use App\Services\AuditLogService;
use App\Services\SparepartMovementService;
use Illuminate\Http\Request;

class CabangController extends Controller
{
    /**
     * Hanya Super Admin dan Enterprise yang boleh akses.
     */
    private function checkAccess(): void
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isEnterprise()) {
            abort(403, 'Fitur Multi Cabang hanya tersedia untuk paket Enterprise. Hubungi Super Admin untuk upgrade.');
        }
    }

    public function index()
    {
        $this->checkAccess();
        $user = auth()->user();
        $isSuper = $user->isSuperAdmin();

        if ($isSuper) {
            // Super admin lihat semua cabang
            $cabangs = Cabang::orderBy('aktif', 'desc')->orderBy('nama')->get();
        } else {
            // Enterprise admin hanya lihat cabang dalam group-nya saja
            $myCabangIds = $this->getMyGroupCabangIds($user);
            $cabangs = Cabang::whereIn('id', $myCabangIds)->orderBy('aktif', 'desc')->orderBy('nama')->get();
        }

        // Stats per cabang (hanya cabang yang terlihat)
        $cabangStats = $cabangs->map(function ($c) {
            $servisCount = Servis::where('cabang_id', $c->id)->count();
            $omset = Servis::where('cabang_id', $c->id)->where('status', 'Selesai')->sum('biaya');
            $pengeluaran = Kas::where('cabang_id', $c->id)->where('tipe', 'keluar')->sum('jml');
            $labaBersih = $omset - $pengeluaran;

            $teknisiCount = Teknisi::where('cabang_id', $c->id)->where('aktif', true)->count();
            $email = $c->users()->first()?->email ?? '-';

            $c->stat_servis = $servisCount;
            $c->stat_omset = $omset;
            $c->stat_pengeluaran = $pengeluaran;
            $c->stat_laba = $labaBersih;
            $c->stat_teknisi = $teknisiCount;
            $c->stat_email = $email;
            return $c;
        });

        // Total stats (hanya dari cabang yang terlihat)
        $totalCabang = $cabangs->where('aktif', true)->count();
        $totalTeknisi = Teknisi::whereIn('cabang_id', $cabangs->pluck('id'))->where('aktif', true)->count();
        $totalOmset = Servis::whereIn('cabang_id', $cabangs->pluck('id'))->where('status', 'Selesai')->sum('biaya');
        $totalPengeluaran = Kas::whereIn('cabang_id', $cabangs->pluck('id'))->where('tipe', 'keluar')->sum('jml');
        $totalLaba = $totalOmset - $totalPengeluaran;

        // Teknisi performance
        $teknisiAll = Teknisi::with(['servis' => function ($q) {
            $q->where('status', 'Selesai');
        }, 'cabang'])->whereIn('cabang_id', $cabangs->pluck('id'))->where('aktif', true)->get()->map(function ($t) {
            $t->selesai_count = $t->servis->count();
            $t->omset = $t->servis->sum('biaya');
            $t->laba_bersih = $t->omset * 0.5;
            $t->bagi_persen = 35;
            $t->bagi_hasil = $t->omset * ($t->bagi_persen / 100);
            return $t;
        });

        // Transfer history
        $transfers = StockTransfer::with(['stok', 'fromCabang', 'toCabang', 'user'])
            ->when(!$isSuper, function ($q) use ($user) {
                $myCabangIds = $this->getMyGroupCabangIds($user);
                $q->whereIn('from_cabang_id', $myCabangIds)
                  ->orWhereIn('to_cabang_id', $myCabangIds);
            })
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        // Sisa kuota cabang anak
        $sisaKuota = $isSuper ? -1 : ($user->maxChildCabang() - $user->countChildCabang());

        // Parent cabang & child branches info (for Enterprise transfer)
        $parentCabang = null;
        $childCabangs = [];
        if (!$isSuper) {
            $parentCabang = Cabang::whereIn('id', $this->getMyGroupCabangIds($user))
                ->whereNull('parent_cabang_id')
                ->first();
            $childCabangs = Cabang::whereIn('id', $this->getMyGroupCabangIds($user))
                ->whereNotNull('parent_cabang_id')
                ->get();
        }

        // Admin accounts info per branch (for "Akun Login" column)
        $adminRole = \App\Models\Role::where('name', 'Admin')->first();
        $branchAdmins = [];
        if ($adminRole) {
            $allAdminUsers = \App\Models\User::where('role_id', $adminRole->id)
                ->where('is_super_admin', false)
                ->whereIn('cabang_id', $cabangs->pluck('id'))
                ->get();
            foreach ($allAdminUsers as $au) {
                $branchAdmins[$au->cabang_id][] = $au;
            }
        }

        return view('cabang.index', compact(
            'cabangs', 'cabangStats', 'totalCabang', 'totalTeknisi',
            'totalOmset', 'totalLaba', 'teknisiAll', 'transfers', 'sisaKuota',
            'parentCabang', 'childCabangs', 'branchAdmins'
        ));
    }

    public function store(Request $request)
    {
        $this->checkAccess();
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'telp' => 'nullable|string|max:30',
            'tipe' => 'nullable|in:toko,gudang',
        ]);

        $user = auth()->user();

        // Standar tidak bisa buat cabang baru (hanya 1 cabang)
        if (!$user->isSuperAdmin() && $user->isStandar()) {
            return redirect()->route('cabang.index')->with('error', 'Paket Standar hanya boleh 1 cabang. Upgrade ke Enterprise (1 pusat + 3 cabang anak + transfer stok) untuk menambah cabang.');
        }

        // Enterprise: batas maks 3 cabang ANAK
        if (!$user->isSuperAdmin()) {
            $childCount = $user->countChildCabang();
            if ($childCount >= $user->maxChildCabang()) {
                return redirect()->route('cabang.index')->with('error', 'Maksimal ' . $user->maxChildCabang() . ' cabang anak untuk paket ' . ucfirst($user->paket) . '. Anda sudah punya ' . $childCount . ' cabang anak.');
            }
        }

        // Tentukan parent_cabang_id
        $parentCabangId = null;
        if (!$user->isSuperAdmin()) {
            // Cari cabang utama admin (parent_cabang_id = null atau cabang_id sendiri)
            $myCabangs = Cabang::whereIn('id', $this->getMyGroupCabangIds($user))->get();
            // Parent adalah cabang pertama yang tidak punya parent, atau cabang user sendiri
            $parent = $myCabangs->first(fn($c) => $c->parent_cabang_id === null);
            if ($parent) {
                $parentCabangId = $parent->id;
            }
        }

        $cabang = Cabang::create([
            'nama' => $validated['nama'],
            'alamat' => $validated['alamat'],
            'telp' => $validated['telp'],
            'tipe' => $validated['tipe'] ?? 'toko',
            'aktif' => true,
            'created_by_user_id' => $user->id,
            'parent_cabang_id' => $parentCabangId,
        ]);

        // FIX: auto-assign cabang utama untuk admin yang users.cabang_id-nya masih NULL.
        // Tanpa ini, semua guard cabang jatuh ke cabang 1 (milik toko lain)
        // → admin enterprise tidak bisa edit stok/pembelian grupnya sendiri.
        if (!$user->isSuperAdmin() && empty($user->cabang_id)) {
            $user->update(['cabang_id' => $parentCabangId ?? $cabang->id]);
        }

        AuditLogService::log('cabang', 'create', "Menambahkan cabang: {$validated['nama']}");

        return redirect()->route('cabang.index')->with('success', 'Cabang berhasil ditambahkan!');
    }

    public function update(Request $request, Cabang $cabang)
    {
        $this->checkAccess();
        $user = auth()->user();
        // Enterprise admin hanya boleh edit cabang group sendiri
        if (!$user->isSuperAdmin()) {
            $myIds = $this->getMyGroupCabangIds($user);
            if (!in_array($cabang->id, $myIds)) {
                abort(403, 'Anda tidak bisa mengedit cabang ini.');
            }
        }

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'telp' => 'nullable|string|max:30',
            'aktif' => 'nullable',
            'tipe' => 'nullable|in:toko,gudang',
        ]);

        $cabang->update([
            'nama' => $validated['nama'],
            'alamat' => $validated['alamat'],
            'telp' => $validated['telp'],
            'tipe' => $validated['tipe'] ?? 'toko',
            'aktif' => $request->has('aktif'),
        ]);

        AuditLogService::log('cabang', 'update', "Mengupdate cabang: {$cabang->nama}");

        return redirect()->route('cabang.index')->with('success', 'Cabang berhasil diupdate!');
    }

    public function destroy(Cabang $cabang)
    {
        $this->checkAccess();
        $user = auth()->user();
        // Enterprise admin hanya boleh hapus cabang group sendiri
        if (!$user->isSuperAdmin()) {
            $myIds = $this->getMyGroupCabangIds($user);
            if (!in_array($cabang->id, $myIds)) {
                abort(403, 'Anda tidak bisa menghapus cabang ini.');
            }
            // Jangan boleh hapus cabang terakhir (minimal 1)
            if (count($myIds) <= 1) {
                return redirect()->route('cabang.index')->with('error', 'Anda harus punya minimal 1 cabang.');
            }
        }

        AuditLogService::log('cabang', 'delete', "Menghapus cabang: {$cabang->nama}");
        $cabang->delete();
        return redirect()->route('cabang.index')->with('success', 'Cabang berhasil dihapus!');
    }

    public function setCabang(Request $request)
    {
        $id = $request->input('cabang_id');
        $user = auth()->user();

        // Redirect kembali ke halaman asal (mis. halaman stok yang mewajibkan pilih toko)
        $redirectTo = $request->input('redirect_to');
        $goBack = fn() => $redirectTo && str_starts_with($redirectTo, '/') && !str_starts_with($redirectTo, '//')
            ? redirect()->to($redirectTo)
            : redirect()->back();

        if ($user->isSuperAdmin()) {
            if ($id === 'all') {
                session(['cabang_id' => 'all']);
            } elseif ($id && \App\Models\Cabang::find($id)) {
                session(['cabang_id' => (int) $id]);
            }
            return $goBack();
        }

        // Enterprise admin: only switch within their group
        if ($user->isEnterprise() && $user->isAdmin()) {
            if ($id && \App\Models\Cabang::find($id)) {
                $allowedIds = $user->getAllowedCabangIds();
                if (in_array((int) $id, $allowedIds)) {
                    session(['cabang_id' => (int) $id]);
                }
            }
            return $goBack();
        }

        return $goBack();
    }

    /**
     * Transfer stok antar cabang dalam 1 group (single item)
     */
    public function transferStok(Request $request)
    {
        $validated = $request->validate([
            'stok_id' => 'required|exists:stoks,id',
            'from_cabang_id' => 'required|exists:cabang,id',
            'to_cabang_id' => 'required|exists:cabang,id|different:from_cabang_id',
            'qty' => 'required|integer|min:1',
            'catatan' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        // Cari stok di cabang asal
        $stokAsal = Stok::where('id', $validated['stok_id'])
                        ->where('cabang_id', $validated['from_cabang_id'])
                        ->first();

        if (!$stokAsal) {
            return back()->with('error', 'Stok tidak ditemukan di cabang asal.');
        }

        if ($stokAsal->stok < $validated['qty']) {
            return back()->with('error', "Stok {$stokAsal->nama} di cabang asal hanya tersisa {$stokAsal->stok}. Tidak cukup untuk transfer {$validated['qty']}.");
        }

        // Cari atau buat stok yang sama di cabang tujuan (pakai kode + cabang_id)
        $stokTujuan = Stok::firstOrCreate(
            ['kode' => $stokAsal->kode, 'cabang_id' => $validated['to_cabang_id']],
            [
                'barcode' => $stokAsal->barcode,
                'nama' => $stokAsal->nama,
                'kategori' => $stokAsal->kategori,
                'merk_hp' => $stokAsal->merk_hp,
                'stok' => 0,
                'modal' => $stokAsal->modal,
                'jual' => $stokAsal->jual,
                'satuan' => $stokAsal->satuan ?? 'pcs',
                'min_alert' => $stokAsal->min_alert ?? 1,
            ]
        );

        // Proses transfer
        $kodeTransfer = StockTransfer::generateKode();

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $stokAsal->decrement('stok', $validated['qty']);
            $stokTujuan->refresh()->increment('stok', $validated['qty']);

            StockTransfer::create([
                'stok_id' => $stokAsal->id,
                'from_cabang_id' => $validated['from_cabang_id'],
                'to_cabang_id' => $validated['to_cabang_id'],
                'qty' => $validated['qty'],
                'harga_satuan' => $stokAsal->jual,
                'kode' => $kodeTransfer,
                'catatan' => $validated['catatan'] ?? null,
                'user_id' => $user->id,
            ]);

            $namaAsal = Cabang::find($validated['from_cabang_id'])?->nama ?? '-';
            $namaTujuan = Cabang::find($validated['to_cabang_id'])?->nama ?? '-';

            // Catat pergerakan stok (Kartu Stok)
            SparepartMovementService::record($stokAsal, 'keluar', 'transfer_keluar', (int) $validated['qty'], [
                'referensi'   => $kodeTransfer,
                'pelaku_nama' => "Transfer ke {$namaTujuan}",
                'cabang_id'   => $validated['from_cabang_id'],
                'catatan'     => $validated['catatan'] ?? null,
            ]);
            SparepartMovementService::record($stokTujuan, 'masuk', 'transfer_masuk', (int) $validated['qty'], [
                'referensi'   => $kodeTransfer,
                'pelaku_nama' => "Transfer dari {$namaAsal}",
                'cabang_id'   => $validated['to_cabang_id'],
                'catatan'     => $validated['catatan'] ?? null,
            ]);

            AuditLogService::log('stock_transfer', 'create', "Transfer stok {$kodeTransfer}: {$stokAsal->nama} x{$validated['qty']} dari {$namaAsal} ke {$namaTujuan}");

            \Illuminate\Support\Facades\DB::commit();

            $stokAsal->refresh();
            $stokTujuan->refresh();
            return back()->with('success', "Transfer berhasil! {$stokAsal->nama} x{$validated['qty']} dari {$namaAsal} ke {$namaTujuan}. Sisa stok asal: {$stokAsal->stok}, Stok tujuan: {$stokTujuan->stok}.");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Gagal transfer: ' . $e->getMessage());
        }
    }

    /**
     * Transfer stok batch dari pusat ke cabang anak (maks 25 item sekaligus)
     */
    public function transferStokBatch(Request $request)
    {
        $validated = $request->validate([
            'from_cabang_id' => 'required|exists:cabang,id',
            'to_cabang_id' => 'required|exists:cabang,id|different:from_cabang_id',
            'stok_ids' => 'required|array|min:1|max:25',
            'stok_ids.*' => 'required|exists:stoks,id',
            'qtys' => 'required|array|min:1|max:25',
            'qtys.*' => 'required|integer|min:1',
            'catatan' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $fromCabangId = $validated['from_cabang_id'];
        $toCabangId = $validated['to_cabang_id'];
        $stokIds = $validated['stok_ids'];
        $qtys = $validated['qtys'];
        $catatan = $validated['catatan'] ?? null;

        // Validasi: stok_ids harus unique (tidak boleh produk yang sama diinput 2x)
        if (count($stokIds) !== count(array_unique($stokIds))) {
            return back()->with('error', 'Tidak boleh ada produk yang sama ditambahkan lebih dari 1 kali. Gabungkan qty-nya menjadi satu baris.');
        }

        // Cek semua stok di cabang asal & validasi qty
        $stokAsalList = [];
        foreach ($stokIds as $idx => $stokId) {
            $stokAsal = Stok::where('id', $stokId)
                            ->where('cabang_id', $fromCabangId)
                            ->first();
            if (!$stokAsal) {
                return back()->with('error', 'Produk baris ke-' . ($idx + 1) . ' tidak ditemukan di cabang asal.');
            }
            if ($stokAsal->stok < $qtys[$idx]) {
                return back()->with('error', "Stok {$stokAsal->nama} hanya tersisa {$stokAsal->stok}, tidak cukup untuk transfer {$qtys[$idx]}.");
            }
            $stokAsalList[] = $stokAsal;
        }

        $namaAsal = Cabang::find($fromCabangId)?->nama ?? '-';
        $namaTujuan = Cabang::find($toCabangId)?->nama ?? '-';

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $successItems = [];
            foreach ($stokAsalList as $idx => $stokAsal) {
                // Cari atau buat stok di cabang tujuan
                $stokTujuan = Stok::firstOrCreate(
                    ['kode' => $stokAsal->kode, 'cabang_id' => $toCabangId],
                    [
                        'barcode' => $stokAsal->barcode,
                        'nama' => $stokAsal->nama,
                        'kategori' => $stokAsal->kategori,
                        'merk_hp' => $stokAsal->merk_hp,
                        'stok' => 0,
                        'modal' => $stokAsal->modal,
                        'jual' => $stokAsal->jual,
                        'satuan' => $stokAsal->satuan ?? 'pcs',
                        'min_alert' => $stokAsal->min_alert ?? 1,
                    ]
                );

                $qty = $qtys[$idx];
                $kodeTransfer = StockTransfer::generateKode();

                $stokAsal->decrement('stok', $qty);
                $stokTujuan->refresh()->increment('stok', $qty);

                StockTransfer::create([
                    'stok_id' => $stokAsal->id,
                    'from_cabang_id' => $fromCabangId,
                    'to_cabang_id' => $toCabangId,
                    'qty' => $qty,
                    'harga_satuan' => $stokAsal->jual,
                    'kode' => $kodeTransfer,
                    'catatan' => $catatan,
                    'user_id' => $user->id,
                ]);

                // Catat pergerakan stok (Kartu Stok)
                SparepartMovementService::record($stokAsal, 'keluar', 'transfer_keluar', (int) $qty, [
                    'referensi'   => $kodeTransfer,
                    'pelaku_nama' => "Transfer ke {$namaTujuan}",
                    'cabang_id'   => $fromCabangId,
                    'catatan'     => $catatan,
                ]);
                SparepartMovementService::record($stokTujuan, 'masuk', 'transfer_masuk', (int) $qty, [
                    'referensi'   => $kodeTransfer,
                    'pelaku_nama' => "Transfer dari {$namaAsal}",
                    'cabang_id'   => $toCabangId,
                    'catatan'     => $catatan,
                ]);

                $successItems[] = "{$stokAsal->nama} x{$qty}";
            }

            AuditLogService::log('stock_transfer', 'create', "Transfer batch stok: " . implode(', ', $successItems) . " dari {$namaAsal} ke {$namaTujuan}");

            \Illuminate\Support\Facades\DB::commit();

            $itemCount = count($successItems);
            return back()->with('success', "Transfer batch berhasil! {$itemCount} produk telah dikirim dari {$namaAsal} ke {$namaTujuan}: " . implode(', ', $successItems));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Gagal transfer batch: ' . $e->getMessage());
        }
    }

    /**
     * Create admin login account for a child branch.
     * Only Super Admin & Enterprise Admin (cabang pusat) can do this.
     */
    public function createBranchAccount(Request $request)
    {
        $this->checkAccess();

        $validated = $request->validate([
            'cabang_id' => 'required|exists:cabang,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:30',
        ]);

        $currentUser = auth()->user();
        $targetCabang = Cabang::find($validated['cabang_id']);

        // Validate access
        if (!$currentUser->isSuperAdmin()) {
            // Enterprise admin: only create for branches in their group
            // AND must be a child branch (not their own parent cabang)
            $allowedIds = $currentUser->getAllowedCabangIds();
            if (!in_array((int) $validated['cabang_id'], $allowedIds)) {
                return back()->with('error', 'Anda tidak memiliki akses ke cabang ini.');
            }
            // Don't allow creating account for own cabang (already logged in)
            if ((int) $validated['cabang_id'] === (int) $currentUser->cabang_id) {
                return back()->with('error', 'Anda sudah login di cabang ini. Tidak perlu membuat akun baru untuk cabang sendiri.');
            }
        }

        // Check if branch already has an admin account
        $existingAdmin = \App\Models\User::where('cabang_id', $validated['cabang_id'])
            ->where('role_id', function ($q) {
                $q->select('id')->from('roles')->where('name', 'Admin');
            })
            ->whereNull('is_super_admin')
            ->orWhere('is_super_admin', false)
            ->exists();

        if ($existingAdmin && !$currentUser->isSuperAdmin()) {
            return back()->with('error', 'Cabang ini sudah memiliki akun Admin. Gunakan menu Kelola Akun untuk mengelolanya.');
        }

        // Get Admin role
        $adminRole = \App\Models\Role::where('name', 'Admin')->first();
        if (!$adminRole) {
            return back()->with('error', 'Role Admin tidak ditemukan.');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $newUser = \App\Models\User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'role_id' => $adminRole->id,
                'cabang_id' => $validated['cabang_id'],
                'is_active' => true,
                'is_super_admin' => false,
                'is_permanent' => true,
                'login_expires_at' => null,
                'paket' => 'standar', // Child branch = standar, parent manages everything
            ]);

            \Illuminate\Support\Facades\DB::commit();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Gagal membuat akun: ' . $e->getMessage());
        }

        AuditLogService::log('cabang', 'create_account', "Membuat akun login untuk cabang {$targetCabang->nama}: {$validated['email']}");

        return back()->with('success', "Akun Admin berhasil dibuat untuk cabang {$targetCabang->nama}! Email: {$validated['email']} — Gunakan akun ini untuk login ke cabang {$targetCabang->nama} secara langsung.");
    }

    /**
     * Get list stok per cabang untuk dropdown transfer
     */
    public function getStokByCabang(Request $request)
    {
        $cabangId = $request->input('cabang_id');
        if (!$cabangId) {
            return response()->json([]);
        }

        // Guard: cabang yang diminta harus milik grup sendiri
        // (Admin Cabang Anak tidak boleh lihat stok toko lain)
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !in_array((int) $cabangId, $user->getAllowedCabangIds(), true)) {
            return response()->json(['error' => 'Anda tidak memiliki akses ke stok cabang tersebut.'], 403);
        }

        $stoks = Stok::where('cabang_id', $cabangId)
                      ->where('stok', '>', 0)
                      ->orderBy('nama')
                      ->get();

        return response()->json($stoks);
    }

    /**
     * Ambil semua cabang_id dalam group admin (termasuk parent & child)
     */
    private function getMyGroupCabangIds($user): array
    {
        $ids = [];

        // 1. Cabang user sendiri
        if (!empty($user->cabang_id)) {
            $ids[] = (int) $user->cabang_id;
        }

        // 2. Semua cabang yang dibuat oleh user ini
        $createdIds = \Illuminate\Support\Facades\DB::table('cabang')
            ->where('created_by_user_id', $user->id)
            ->pluck('id')
            ->all();
        foreach ($createdIds as $id) {
            $ids[] = (int) $id;
        }

        // 3. Parent dari cabang yang dibuat user
        $parentIds = \Illuminate\Support\Facades\DB::table('cabang')
            ->where('created_by_user_id', $user->id)
            ->whereNotNull('parent_cabang_id')
            ->pluck('parent_cabang_id')
            ->all();
        foreach ($parentIds as $pid) {
            if (!empty($pid)) {
                $ids[] = (int) $pid;
            }
        }

        $result = [];
        foreach (array_unique($ids) as $id) {
            if ($id > 0) {
                $result[] = $id;
            }
        }

        return $result;
    }
}
