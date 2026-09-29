<?php

namespace App\Http\Controllers;

use App\Models\PromoCard;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

/**
 * CARD BANNER PROMO (Dashboard)
 * - Tampil di paling atas dashboard untuk SEMUA role (render via partial).
 * - Create / Update / Delete HANYA Super Admin (dijaga middleware EnsureSuperAdmin di routes).
 */
class PromoCardController extends Controller
{
    private function rules(): array
    {
        return [
            'judul'       => 'required|string|max:255',
            'deskripsi'   => 'nullable|string|max:10000',
            'gambar'      => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'gambar_url'  => 'nullable|url|max:500',
            'tombol_text' => 'nullable|string|max:100',
            'tombol_link' => 'nullable|string|max:500',
            'urutan'      => 'nullable|integer|min:1',
        ];
    }

    /**
     * Resolve gambar: upload file > URL > pertahankan yang lama.
     */
    private function resolveImage(Request $request, ?string $current = null): ?string
    {
        if ($request->hasFile('gambar')) {
            return $request->file('gambar')->store('promo-cards', 'public');
        }
        if ($request->filled('gambar_url')) {
            return $request->input('gambar_url');
        }
        return $current;
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->rules());

            if (!$request->hasFile('gambar') && !$request->filled('gambar_url')) {
                return response()->json(['error' => 'Upload gambar atau masukkan URL gambar.'], 422);
            }

            $card = PromoCard::create([
                'judul'       => $validated['judul'],
                'deskripsi'   => $validated['deskripsi'] ?? '',
                'gambar'      => $this->resolveImage($request),
                'tombol_text' => $validated['tombol_text'] ?: 'Lihat Detail',
                'tombol_link' => $validated['tombol_link'] ?: '#',
                'aktif'       => $request->has('aktif') ? $request->boolean('aktif') : true,
                'urutan'      => $validated['urutan'] ?? 1,
            ]);

            AuditLogService::log('promo-card', 'create', "Menambahkan card promo dashboard: {$card->judul}");

            return response()->json(['success' => true, 'message' => 'Card promo berhasil ditambahkan!']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $first = reset($errors);
            return response()->json(['error' => is_array($first) ? $first[0] : $first], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $promo_card)
    {
        try {
            $card = PromoCard::findOrFail($promo_card);
            $validated = $request->validate($this->rules());

            $card->update([
                'judul'       => $validated['judul'],
                'deskripsi'   => $validated['deskripsi'] ?? '',
                'gambar'      => $this->resolveImage($request, $card->gambar),
                'tombol_text' => $validated['tombol_text'] ?: 'Lihat Detail',
                'tombol_link' => $validated['tombol_link'] ?: '#',
                'aktif'       => $request->has('aktif') ? $request->boolean('aktif') : $card->aktif,
                'urutan'      => $validated['urutan'] ?? $card->urutan,
            ]);

            AuditLogService::log('promo-card', 'update', "Mengupdate card promo dashboard: {$card->judul}");

            return response()->json(['success' => true, 'message' => 'Card promo berhasil diupdate!']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $first = reset($errors);
            return response()->json(['error' => is_array($first) ? $first[0] : $first], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($promo_card)
    {
        try {
            $card = PromoCard::findOrFail($promo_card);
            AuditLogService::log('promo-card', 'delete', "Menghapus card promo dashboard: {$card->judul}");
            $card->delete();

            return response()->json(['success' => true, 'message' => 'Card promo berhasil dihapus!']);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
}
