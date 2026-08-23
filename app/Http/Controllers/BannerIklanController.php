<?php

namespace App\Http\Controllers;

use App\Models\BannerIklan;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerIklanController extends Controller
{
    public function index()
    {
        $banners = BannerIklan::orderBy('urutan')->orderBy('created_at', 'desc')->get();
        return view('banner-iklan.index', compact('banners'));
    }

    public function store(Request $request)
    {
        // Handle AJAX requests
        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            try {
                $validated = $request->validate([
                    'judul' => 'required|string|max:255',
                    'deskripsi' => 'nullable|string',
                    'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                    'gambar_url' => 'nullable|url|max:500',
                    'link' => 'nullable|string|max:500',
                    'urutan' => 'nullable|integer',
                ]);

                $path = null;
                if ($request->hasFile('gambar')) {
                    $path = $request->file('gambar')->store('banner', 'public');
                } elseif (!empty($validated['gambar_url'])) {
                    $path = $validated['gambar_url'];
                } else {
                    return response()->json(['error' => 'Upload gambar atau masukkan URL gambar.'], 422);
                }

                BannerIklan::create([
                    'judul' => $validated['judul'],
                    'deskripsi' => $validated['deskripsi'] ?? '',
                    'gambar' => $path,
                    'link' => $validated['link'] ?? '#',
                    'aktif' => true,
                    'urutan' => $validated['urutan'] ?? 1,
                ]);

                AuditLogService::log('banner', 'create', "Menambahkan banner: {$validated['judul']}");

                return response()->json(['success' => true, 'message' => 'Banner berhasil ditambahkan!']);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $errors = $e->errors();
                $firstError = reset($errors);
                return response()->json(['error' => is_array($firstError) ? $firstError[0] : $firstError], 422);
            } catch (\Throwable $e) {
                return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
            }
        }

        // Fallback: normal form submit
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'gambar_url' => 'nullable|url|max:500',
            'link' => 'nullable|url',
            'urutan' => 'nullable|integer',
        ]);

        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('banner', 'public');
        } elseif (!empty($validated['gambar_url'])) {
            $path = $validated['gambar_url'];
        } else {
            return back()->with('error', 'Upload gambar atau masukkan URL gambar.')->withInput();
        }

        BannerIklan::create([
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'],
            'gambar' => $path,
            'link' => $validated['link'] ?? '#',
            'aktif' => true,
            'urutan' => $validated['urutan'] ?? 1,
        ]);

        AuditLogService::log('banner', 'create', "Menambahkan banner: {$validated['judul']}");

        return redirect()->route('banner-iklan.index')->with('success', 'Banner berhasil ditambahkan!');
    }

    public function update(Request $request, $banner_iklan)
    {
        // Handle AJAX requests
        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            try {
                $banner = BannerIklan::findOrFail($banner_iklan);

                $validated = $request->validate([
                    'judul' => 'required|string|max:255',
                    'deskripsi' => 'nullable|string',
                    'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                    'gambar_url' => 'nullable|url|max:500',
                    'link' => 'nullable|string|max:500',
                    'urutan' => 'nullable|integer',
                    'aktif' => 'nullable',
                ]);

                $data = [
                    'judul' => $validated['judul'],
                    'deskripsi' => $validated['deskripsi'] ?? '',
                    'link' => $validated['link'] ?? '#',
                    'aktif' => $request->has('aktif') && $request->aktif,
                    'urutan' => $validated['urutan'] ?? 1,
                ];

                if ($request->hasFile('gambar')) {
                    $data['gambar'] = $request->file('gambar')->store('banner', 'public');
                } elseif (!empty($validated['gambar_url'])) {
                    $data['gambar'] = $validated['gambar_url'];
                }

                $banner->update($data);

                AuditLogService::log('banner', 'update', "Mengupdate banner: {$banner->judul}");

                return response()->json(['success' => true, 'message' => 'Banner berhasil diupdate!']);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $errors = $e->errors();
                $firstError = reset($errors);
                return response()->json(['error' => is_array($firstError) ? $firstError[0] : $firstError], 422);
            } catch (\Throwable $e) {
                return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
            }
        }

        // Fallback: normal form submit
        $banner = BannerIklan::findOrFail($banner_iklan);

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'gambar_url' => 'nullable|url|max:500',
            'link' => 'nullable|url',
            'urutan' => 'nullable|integer',
            'aktif' => 'nullable',
        ]);

        $data = [
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'],
            'link' => $validated['link'] ?? '#',
            'aktif' => $request->has('aktif'),
            'urutan' => $validated['urutan'] ?? 1,
        ];

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('banner', 'public');
        } elseif (!empty($validated['gambar_url'])) {
            $data['gambar'] = $validated['gambar_url'];
        }

        $banner->update($data);

        AuditLogService::log('banner', 'update', "Mengupdate banner: {$banner->judul}");

        return redirect()->route('banner-iklan.index')->with('success', 'Banner berhasil diupdate!');
    }

    public function destroy(Request $request, $banner_iklan)
    {
        // Handle AJAX requests
        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            try {
                $banner = BannerIklan::findOrFail($banner_iklan);
                AuditLogService::log('banner', 'delete', "Menghapus banner: {$banner->judul}");
                $banner->delete();
                return response()->json(['success' => true, 'message' => 'Banner berhasil dihapus!']);
            } catch (\Throwable $e) {
                return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
            }
        }

        // Fallback: normal form submit
        $banner = BannerIklan::findOrFail($banner_iklan);
        AuditLogService::log('banner', 'delete', "Menghapus banner: {$banner->judul}");
        $banner->delete();
        return redirect()->route('banner-iklan.index')->with('success', 'Banner berhasil dihapus!');
    }
}
