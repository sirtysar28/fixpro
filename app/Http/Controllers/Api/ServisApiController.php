<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Servis;
use App\Models\Pelanggan;
use App\Models\Teknisi;
use App\Models\User;
use App\Models\Cabang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ServisApiController extends Controller
{
    // === SERVIS (Admin/Staff) ===

    public function index(Request $request)
    {
        $cabangId = $request->user()->getApiCabangId($request);
        $query = Servis::with(['pelanggan', 'teknisi', 'cabang']);
        if ($cabangId !== null) {
            $query->where('cabang_id', $cabangId);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('kode', 'like', "%$s%")
                    ->orWhere('perangkat', 'like', "%$s%")
                    ->orWhere('imei', 'like', "%$s%")
                    ->orWhereHas('pelanggan', fn($q) => $q->where('nama', 'like', "%$s%")->orWhere('no_hp', 'like', "%$s%"));
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('sumber')) $query->where('sumber', $request->sumber);

        $servis = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($servis->through(fn($s) => $this->formatServis($s)));
    }

    public function show($id)
    {
        $servis = Servis::with(['pelanggan', 'teknisi', 'cabang'])->findOrFail($id);
        return response()->json(['servis' => $this->formatServisDetail($servis)]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'no_hp' => 'required', 'nama' => 'required', 'perangkat' => 'required',
            'tipe' => 'required|in:Apple,Android', 'keluhan' => 'required',
            'biaya' => 'numeric|min:0', 'dp' => 'numeric|min:0',
            'status' => 'in:Masuk,Proses,Pending,Selesai', 'prioritas' => 'in:Normal,Urgent',
            'teknisi_id' => 'nullable|exists:teknisis,id', 'garansi' => 'integer|min:0',
            'imei' => 'nullable|max:20', 'catatan' => 'nullable',
        ]);

        $pelanggan = $this->findOrCreatePelangganWithUser($validated['no_hp'], $validated['nama']);

        $kode = $this->generateKode();

        $servis = Servis::create([
            'kode' => $kode,
            'pelanggan_id' => $pelanggan->id,
            'cabang_id' => $request->user()->getApiCabangId($request),
            'sumber' => 'admin',
            'perangkat' => $validated['perangkat'],
            'keluhan' => $validated['keluhan'],
            'tipe' => $validated['tipe'],
            'status' => $validated['status'] ?? 'Masuk',
            'biaya' => $validated['biaya'] ?? 0,
            'dp' => $validated['dp'] ?? 0,
            'tanggal' => now()->format('Y-m-d'),
            'teknisi_id' => $validated['teknisi_id'] ?? null,
            'prioritas' => $validated['prioritas'] ?? 'Normal',
            'imei' => $validated['imei'] ?? null,
            'catatan' => $validated['catatan'] ?? null,
            'garansi' => $validated['garansi'] ?? 30,
        ]);

        return response()->json(['servis' => $this->formatServisDetail($servis), 'message' => "Servis $kode berhasil ditambahkan!"], 201);
    }

    public function update(Request $request, $id)
    {
        $servis = Servis::findOrFail($id);
        $validated = $request->validate([
            'perangkat' => 'required', 'tipe' => 'required|in:Apple,Android',
            'keluhan' => 'required', 'biaya' => 'numeric|min:0', 'dp' => 'numeric|min:0',
            'status' => 'in:Masuk,Proses,Pending,Selesai', 'prioritas' => 'in:Normal,Urgent',
            'teknisi_id' => 'nullable|exists:teknisis,id', 'garansi' => 'integer|min:0',
            'imei' => 'nullable|max:20', 'catatan' => 'nullable',
        ]);

        $tanggalGaransi = null;
        if ($validated['status'] === 'Selesai' && (int) $validated['garansi'] > 0) {
            $tanggalGaransi = now()->addDays((int) $validated['garansi'])->format('Y-m-d');
        }

        $servis->update([
            'perangkat' => $validated['perangkat'],
            'keluhan' => $validated['keluhan'],
            'tipe' => $validated['tipe'],
            'status' => $validated['status'],
            'biaya' => $validated['biaya'],
            'dp' => $validated['dp'],
            'teknisi_id' => $validated['teknisi_id'],
            'prioritas' => $validated['prioritas'],
            'imei' => $validated['imei'],
            'catatan' => $validated['catatan'],
            'garansi' => $validated['garansi'],
            'tanggal_garansi' => $tanggalGaransi,
            'diambil' => $validated['status'] === 'Selesai' ? true : $servis->diambil,
            'tgl_diambil' => $validated['status'] === 'Selesai' && !$servis->tgl_diambil ? now() : $servis->tgl_diambil,
        ]);

        return response()->json(['servis' => $this->formatServisDetail($servis->fresh()), 'message' => 'Servis berhasil diupdate!']);
    }

    public function destroy($id)
    {
        Servis::findOrFail($id)->delete();
        return response()->json(['message' => 'Servis berhasil dihapus!']);
    }

    // === ARSIP SERVIS ===
    public function arsip(Request $request)
    {
        $cabangId = $request->user()->getApiCabangId($request);
        $query = Servis::with(['pelanggan', 'teknisi', 'cabang']);
        if ($cabangId !== null) {
            $query->where('cabang_id', $cabangId);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('kode', 'like', "%$s%")
                    ->orWhere('perangkat', 'like', "%$s%")
                    ->orWhereHas('pelanggan', fn($q) => $q->where('nama', 'like', "%$s%"));
            });
        }

        $servis = $query->whereIn('status', ['Selesai', 'Dibatalkan', 'Diambil'])
            ->orderBy('updated_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $servis->through(fn($s) => $this->formatServisDetail($s)),
            'total' => $servis->total(),
        ]);
    }

    // === DETAIL JSON ===
    public function detailJson($id)
    {
        $servis = Servis::with(['pelanggan', 'teknisi', 'cabang', 'spareparts.stok'])->findOrFail($id);
        return response()->json($this->formatServisDetail($servis));
    }

    // === QUICK STATUS ===
    public function quickStatus(Request $request, $id)
    {
        $servis = Servis::findOrFail($id);
        $request->validate(['status' => 'required|in:Masuk,Proses,Pending,Selesai']);

        $oldStatus = $servis->status;
        $servis->update(['status' => $request->status]);

        if ($request->status === 'Selesai' && !$servis->tanggal_garansi) {
            $servis->update([
                'tanggal_garansi' => now()->addDays($servis->garansi ?: 30),
            ]);
        }

        return response()->json([
            'message' => "Status diubah: $oldStatus → {$request->status}",
            'servis' => $this->formatServisDetail($servis->fresh()),
        ]);
    }

    // === BATALKAN ===
    public function batal(Request $request, $id)
    {
        $servis = Servis::findOrFail($id);
        $request->validate(['alasan' => 'required|string|min:3']);

        $servis->update([
            'status' => 'Dibatalkan',
            'alasan_pembatalan' => $request->alasan,
        ]);

        return response()->json([
            'message' => 'Servis dibatalkan',
            'servis' => $this->formatServisDetail($servis->fresh()),
        ]);
    }

    // === KONFIRMASI DIAMBIL ===
    public function diambil(Request $request, $id)
    {
        $servis = Servis::findOrFail($id);
        $servis->update([
            'diambil' => true,
            'tgl_diambil' => now(),
            'status' => 'Diambil',
        ]);

        return response()->json([
            'message' => 'HP dikonfirmasi diambil',
            'servis' => $this->formatServisDetail($servis->fresh()),
        ]);
    }

    // === MY SERVICE (User) ===

    public function myService(Request $request)
    {
        $user = $request->user();
        $query = Servis::with(['pelanggan', 'teknisi', 'cabang'])
            ->whereHas('pelanggan', fn($q) => $q->where('user_id', $user->id)->orWhere('no_hp', $user->phone)->orWhere('nama', $user->name))
            ->orderBy('created_at', 'desc');

        return response()->json($query->paginate(20)->through(fn($s) => $this->formatServis($s)));
    }

    public function myServiceStore(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required', 'no_hp' => 'required', 'perangkat' => 'required',
            'tipe' => 'required|in:Apple,Android', 'keluhan' => 'required',
            'cabang_id' => 'required|exists:cabang,id',
            'imei' => 'nullable|max:20', 'catatan' => 'nullable',
        ]);

        $pelanggan = $this->findOrCreatePelangganWithUser($validated['no_hp'], $validated['nama']);
        $kode = $this->generateKode();
        $cabang = Cabang::find($validated['cabang_id']);

        $servis = Servis::create([
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

        return response()->json([
            'servis' => $this->formatServis($servis),
            'message' => "Servis $kode berhasil didaftarkan ke cabang {$cabang->nama}!",
        ], 201);
    }

    public function myServiceShow(Request $request, $id)
    {
        $servis = Servis::with(['pelanggan', 'teknisi', 'cabang'])->findOrFail($id);
        return response()->json(['servis' => $this->formatServisDetail($servis)]);
    }

    private function generateKode(): string
    {
        $date = now()->format('ymd');
        $last = Servis::where('kode', 'like', "SVC-$date-%")->orderBy('id', 'desc')->first();
        $num = $last ? (int) substr($last->kode, -3) + 1 : 1;
        return "SVC-$date-" . str_pad($num, 3, '0', STR_PAD_LEFT);
    }

    private function findOrCreatePelangganWithUser(string $noHp, string $nama): Pelanggan
    {
        $cabangId = auth()->user()->getApiCabangId(request());
        // Cari pelanggan di cabang ini dulu
        $pelanggan = Pelanggan::where('no_hp', $noHp);
        if ($cabangId !== null) {
            $pelanggan->where('cabang_id', $cabangId);
        }
        $pelanggan = $pelanggan->first();

        if ($pelanggan) {
            $pelanggan->update(['nama' => $nama]);
            if (!$pelanggan->user_id) {
                $user = User::where('phone', $noHp)->first();
                if (!$user) {
                    $user = User::create([
                        'name' => $nama,
                        'email' => $noHp . '@fixpro.local',
                        'password' => Hash::make($noHp),
                        'phone' => $noHp,
                        'role_id' => 3,
                        'cabang_id' => $cabangId ?? 1,
                        'is_active' => true,
                        'is_permanent' => false,
                        'login_expires_at' => now()->addMonth(),
                    ]);
                }
                $pelanggan->update(['user_id' => $user->id]);
            }
            return $pelanggan->fresh();
        }

        $user = User::where('phone', $noHp)->first();
        if (!$user) {
            $user = User::create([
                'name' => $nama,
                'email' => $noHp . '@fixpro.local',
                'password' => Hash::make($noHp),
                'phone' => $noHp,
                'role_id' => 3,
                'cabang_id' => $cabangId ?? 1,
                'is_active' => true,
                'is_permanent' => false,
                'login_expires_at' => now()->addMonth(),
            ]);
        }

        return Pelanggan::create([
            'user_id' => $user->id,
            'nama' => $nama,
            'no_hp' => $noHp,
            'cabang_id' => $cabangId ?? 1,
        ]);
    }

    private function formatServis($s)
    {
        return [
            'id' => $s->id, 'kode' => $s->kode,
            'tanggal' => $s->tanggal?->format('d/m/Y'),
            'pelanggan' => $s->pelanggan?->nama, 'pelanggan_hp' => $s->pelanggan?->no_hp,
            'perangkat' => $s->perangkat, 'tipe' => $s->tipe,
            'keluhan' => $s->keluhan, 'status' => $s->status,
            'prioritas' => $s->prioritas, 'biaya' => (float) $s->biaya,
            'teknisi' => $s->teknisi?->nama, 'cabang' => $s->cabang?->nama,
            'sumber' => $s->sumber, 'created_at' => $s->created_at?->format('d/m/Y H:i'),
        ];
    }

    private function formatServisDetail($s)
    {
        return array_merge($this->formatServis($s), [
            'id' => $s->id,
            'imei' => $s->imei,
            'dp' => (float) ($s->dp ?? 0),
            'sisa' => (float) (($s->biaya ?? 0) - ($s->dp ?? 0)),
            'garansi' => $s->garansi,
            'tanggal_garansi' => $s->tanggal_garansi?->format('d/m/Y'),
            'catatan' => $s->catatan,
            'pelanggan_alamat' => $s->pelanggan?->alamat,
            'pelanggan_nama' => $s->pelanggan?->nama,
            'diambil' => $s->diambil,
            'tgl_diambil' => $s->tgl_diambil?->format('d/m/Y H:i'),
            'alasan_pembatalan' => $s->alasan_pembatalan,
            'spareparts' => $s->whenLoaded('spareparts') ? $s->spareparts->map(fn($sp) => [
                'id' => $sp->id,
                'nama' => $sp->stok?->nama ?? '-',
                'harga' => (float) ($sp->harga_satuan ?? 0),
                'qty' => $sp->qty ?? 1,
            ]) : null,
        ]);
    }
}
