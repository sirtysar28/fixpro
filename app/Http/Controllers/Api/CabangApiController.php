<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use Illuminate\Http\Request;

class CabangApiController extends Controller
{
    public function index()
    {
        $cabangs = Cabang::where('aktif', true)->orderBy('nama')->get()->map(fn($c) => [
            'id' => $c->id,
            'nama' => $c->nama,
            'alamat' => $c->alamat,
            'telp' => $c->telp,
            'parent_cabang_id' => $c->parent_cabang_id,
        ]);
        return response()->json(['cabangs' => $cabangs]);
    }

    public function setCabang(Request $request)
    {
        $id = $request->input('cabang_id');
        $user = $request->user();

        if (!$id || !Cabang::find($id)) {
            return response()->json(['ok' => false, 'message' => 'Cabang tidak ditemukan'], 404);
        }

        if ($user->isSuperAdmin()) {
            session(['cabang_id' => (int) $id]);
            return response()->json(['ok' => true, 'cabang_id' => (int) $id]);
        }

        // Enterprise admin: only switch within their group
        if ($user->isEnterprise() && $user->isAdmin()) {
            $allowedIds = $user->getAllowedCabangIds();
            if (in_array((int) $id, $allowedIds)) {
                session(['cabang_id' => (int) $id]);
                return response()->json(['ok' => true, 'cabang_id' => (int) $id]);
            }
            return response()->json(['ok' => false, 'message' => 'Anda tidak memiliki akses ke cabang ini'], 403);
        }

        return response()->json(['ok' => false, 'message' => 'Tidak diizinkan'], 403);
    }
}
