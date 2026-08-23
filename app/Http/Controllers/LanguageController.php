<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Translation;
use App\Services\AuditLogService;
use App\Services\LocalizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Fitur Multi Bahasa.
 *
 * - Switch locale (semua user)        : GET  /language/switch/{code}
 * - Kelola master bahasa (Super Admin): resource /admin/languages
 * - Kelola terjemahan (Super Admin)   : /admin/languages/{lang}/translations
 */
class LanguageController extends Controller
{
    /** Switch bahasa aktif user (simpan ke session) */
    public function switch(string $code, LocalizationService $loc)
    {
        $loc->setLocale($code);
        return redirect()->back()->with('success', 'Bahasa diubah ke ' . ($loc->activeModel()->native_name ?? $code));
    }

    /* =========================================================
       SUPER ADMIN — MASTER DATA BAHASA
       ========================================================= */

    /** Daftar semua bahasa */
    public function index()
    {
        $this->checkSuperAdmin();
        $languages = Language::orderBy('is_default', 'desc')->orderBy('name')->get();
        return view('languages.index', compact('languages'));
    }

    /** Simpan bahasa baru */
    public function store(Request $request)
    {
        $this->checkSuperAdmin();
        $validated = $request->validate([
            'code'        => 'required|string|max:10|unique:languages,code',
            'name'        => 'required|string|max:60',
            'native_name' => 'nullable|string|max:60',
            'flag'        => 'nullable|string|max:12',
            'is_active'   => 'nullable',
        ]);

        $lang = Language::create([
            'code'        => strtolower($validated['code']),
            'name'        => $validated['name'],
            'native_name' => $validated['native_name'] ?? $validated['name'],
            'flag'        => $validated['flag'] ?: '🌐',
            'is_active'   => $request->has('is_active'),
            'is_default'  => false,
        ]);

        // Salin terjemahan default (id) sebagai starter untuk bahasa baru
        $default = Language::default();
        $idRows = Translation::where('language_id', $default->id)->get(['group', 'key', 'value']);
        $batch = [];
        $now = now();
        foreach ($idRows as $row) {
            $batch[] = [
                'language_id' => $lang->id, 'group' => $row->group, 'key' => $row->key,
                'value' => $row->value, 'created_at' => $now, 'updated_at' => $now,
            ];
        }
        foreach (array_chunk($batch, 200) as $chunk) {
            Translation::insert($chunk);
        }

        AuditLogService::log('language', 'create', "Tambah bahasa {$lang->name} ({$lang->code})");

        return back()->with('success', "Bahasa {$lang->name} ditambahkan. Konten dimulai dari salinan bahasa default — silakan terjemahkan.");
    }

    public function update(Request $request, Language $language)
    {
        $this->checkSuperAdmin();
        $validated = $request->validate([
            'name'        => 'required|string|max:60',
            'native_name' => 'nullable|string|max:60',
            'flag'        => 'nullable|string|max:12',
            'is_active'   => 'nullable',
        ]);

        $language->update([
            'name'        => $validated['name'],
            'native_name' => $validated['native_name'] ?? $validated['name'],
            'flag'        => $validated['flag'] ?: '🌐',
            'is_active'   => $request->has('is_active') || $language->is_default,
        ]);

        Cache::forget('languages.active');

        return back()->with('success', 'Bahasa diperbarui.');
    }

    public function destroy(Language $language)
    {
        $this->checkSuperAdmin();
        if ($language->is_default) {
            return back()->with('error', 'Bahasa default (Indonesia) tidak bisa dihapus.');
        }
        $name = $language->name;
        $language->delete();
        Cache::forget('languages.active');

        AuditLogService::log('language', 'delete', "Hapus bahasa $name");
        return back()->with('success', "Bahasa $name dihapus.");
    }

    /* =========================================================
       SUPER ADMIN — EDIT TERJEMAHAN
       ========================================================= */

    /** Tampilkan editor terjemahan untuk 1 bahasa */
    public function translations(Language $language, Request $request)
    {
        $this->checkSuperAdmin();

        $default = Language::default();

        // Group filter
        $groups = Translation::where('language_id', $default->id)
            ->select('group')->distinct()->orderBy('group')->pluck('group');
        $activeGroup = $request->query('group', $groups->first() ?? 'app');
        $q = trim((string) $request->query('q', ''));

        // Ambil key dari bahasa default (sumber kebenaran key)
        $baseQuery = Translation::where('language_id', $default->id)->where('group', $activeGroup);
        if ($q !== '') {
            $baseQuery->where(fn ($qq) => $qq->where('key', 'like', "%$q%")->orWhere('value', 'like', "%$q%"));
        }
        $baseRows = (clone $baseQuery)->orderBy('key')->paginate(100)->withQueryString();

        // Pasangkan dengan nilai bahasa target
        $targetMap = Translation::where('language_id', $language->id)
            ->where('group', $activeGroup)
            ->pluck('value', 'key');

        return view('languages.translations', compact('language', 'groups', 'activeGroup', 'q', 'baseRows', 'targetMap', 'default'));
    }

    /** Simpan banyak terjemahan sekaligus (inline editor) */
    public function updateTranslations(Request $request, Language $language)
    {
        $this->checkSuperAdmin();
        $data = $request->validate([
            'group'       => 'required|string|max:40',
            'values'      => 'required|array',
            'values.*'    => 'nullable|string|max:5000',
        ]);

        $group = $data['group'];
        $count = 0;

        foreach ($data['values'] as $key => $value) {
            Translation::updateOrCreate(
                ['language_id' => $language->id, 'group' => $group, 'key' => $key],
                ['value' => $value !== '' ? $value : null]
            );
            $count++;
        }

        Cache::forget("translations.{$language->code}");

        AuditLogService::log('language', 'update_translations', "Update $count terjemahan bahasa {$language->name} ($group)");

        return back()->with('success', "$count terjemahan disimpan untuk bahasa {$language->name}.");
    }

    /** Tambah key baru (di bahasa default, lalu tersalin ke semua bahasa) */
    public function addKey(Request $request)
    {
        $this->checkSuperAdmin();
        $data = $request->validate([
            'group'      => 'required|string|max:40',
            'key'        => 'required|string|max:191',
            'value_id'   => 'required|string|max:5000',
        ]);

        $default = Language::default();
        Translation::firstOrCreate(
            ['language_id' => $default->id, 'group' => $data['group'], 'key' => $data['key']],
            ['value' => $data['value_id']]
        );

        // Salin ke semua bahasa lain (kosong dulu, agar admin terjemahkan)
        foreach (Language::where('id', '!=', $default->id)->get() as $lang) {
            Translation::firstOrCreate(
                ['language_id' => $lang->id, 'group' => $data['group'], 'key' => $data['key']],
                ['value' => null]
            );
            Cache::forget("translations.{$lang->code}");
        }

        return back()->with('success', "Key '{$data['key']}' ditambahkan ke group '{$data['group']}'.");
    }

    public function destroyKey(Request $request)
    {
        $this->checkSuperAdmin();
        $data = $request->validate([
            'group' => 'required|string|max:40',
            'key'   => 'required|string|max:191',
        ]);
        Translation::where('group', $data['group'])->where('key', $data['key'])->delete();
        foreach (Language::pluck('code') as $code) {
            Cache::forget("translations.{$code}");
        }
        return back()->with('success', "Key '{$data['key']}' dihapus dari semua bahasa.");
    }

    private function checkSuperAdmin(): void
    {
        if (!auth()->user()?->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang boleh mengelola bahasa.');
        }
    }
}
