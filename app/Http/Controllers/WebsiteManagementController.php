<?php

namespace App\Http\Controllers;

use App\Models\WebsiteContent;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WebsiteManagementController extends Controller
{
    public function index()
    {
        $sections = WebsiteContent::orderBy('section')->orderBy('sort_order')->get()->groupBy('section');
        return view('website.manage', compact('sections'));
    }

    public function updateSection(Request $request)
    {
        $section = $request->input('section');
        $fields = $request->input('fields', []);

        foreach ($fields as $key => $data) {
            $value = is_array($data) ? ($data['value'] ?? null) : $data;

            $updateData = ['value' => $value];

            // Handle image upload
            $fileKey = "fields.{$key}.image";
            if ($request->hasFile($fileKey)) {
                $updateData['image'] = $request->file($fileKey)->store('website', 'public');
            }

            WebsiteContent::updateOrCreate(
                ['section' => $section, 'key' => $key],
                $updateData
            );
        }

        AuditLogService::custom('website', 'update', "Mengupdate section website: {$section}");
        return response()->json(['success' => true, 'message' => 'Konten website berhasil diupdate!']);
    }

    public function updateItem(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:website_contents,id',
            'section' => 'required|string',
            'key' => 'required|string',
            'value' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $data = [
            'section' => $validated['section'],
            'key' => $validated['key'],
            'value' => $validated['value'] ?? '',
            'is_active' => $request->has('is_active') ? (bool) $validated['is_active'] : true,
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('website', 'public');
        }

        if (!empty($validated['id'])) {
            $item = WebsiteContent::find($validated['id']);
            $item->update($data);
        } else {
            $item = WebsiteContent::create($data);
        }

        AuditLogService::custom('website', 'update', "Mengupdate website content: {$validated['section']}.{$validated['key']}");
        return response()->json(['success' => true, 'message' => 'Konten berhasil disimpan!', 'id' => $item->id]);
    }

    public function deleteItem($id)
    {
        $item = WebsiteContent::findOrFail($id);
        AuditLogService::custom('website', 'delete', "Menghapus website content: {$item->section}.{$item->key}");

        if ($item->image && Storage::disk('public')->exists($item->image)) {
            Storage::disk('public')->delete($item->image);
        }

        $item->delete();
        return response()->json(['success' => true, 'message' => 'Konten berhasil dihapus!']);
    }

    public function updateJsonItems(Request $request)
    {
        $validated = $request->validate([
            'section' => 'required|string',
            'key' => 'required|string',
            'items' => 'required|array',
        ]);

        WebsiteContent::updateOrCreate(
            ['section' => $validated['section'], 'key' => $validated['key']],
            ['value' => json_encode($validated['items'], JSON_UNESCAPED_UNICODE), 'is_active' => true]
        );

        AuditLogService::custom('website', 'update', "Mengupdate {$validated['section']}.{$validated['key']} items");
        return response()->json(['success' => true, 'message' => 'Data berhasil disimpan!']);
    }

    public function uploadApk(Request $request)
    {
        $request->validate([
            'apk' => 'required|file|mimes:apk|max:102400',
            'version' => 'nullable|string|max:20',
        ], [
            'apk.required' => 'File APK wajib dipilih.',
            'apk.mimes' => 'File harus berformat .apk',
            'apk.max' => 'Ukuran file maksimal 100MB.',
        ]);

        // Delete old file if exists
        $old = WebsiteContent::where('section', 'mobile_app')->where('key', 'apk_file')->first();
        if ($old && $old->value && Storage::disk('public')->exists($old->value)) {
            Storage::disk('public')->delete($old->value);
        }

        $path = $request->file('apk')->store('apk', 'public');
        $version = $request->input('version', '1.0');
        $fileName = $request->file('apk')->getClientOriginalName();
        $fileSize = $request->file('apk')->getSize();

        WebsiteContent::updateOrCreate(
            ['section' => 'mobile_app', 'key' => 'apk_file'],
            ['value' => $path, 'image' => $version, 'is_active' => true]
        );

        WebsiteContent::updateOrCreate(
            ['section' => 'mobile_app', 'key' => 'apk_filename'],
            ['value' => $fileName, 'is_active' => true]
        );

        WebsiteContent::updateOrCreate(
            ['section' => 'mobile_app', 'key' => 'apk_size'],
            ['value' => $this->formatFileSize($fileSize), 'is_active' => true]
        );

        AuditLogService::custom('website', 'update', "Upload APK: {$fileName} (v{$version})");
        return response()->json([
            'success' => true,
            'message' => "APK berhasil diupload!",
            'filename' => $fileName,
            'version' => $version,
            'size' => $this->formatFileSize($fileSize),
        ]);
    }

    public function deleteApk()
    {
        $old = WebsiteContent::where('section', 'mobile_app')->where('key', 'apk_file')->first();
        if ($old && $old->value && Storage::disk('public')->exists($old->value)) {
            Storage::disk('public')->delete($old->value);
        }
        WebsiteContent::where('section', 'mobile_app')->delete();
        AuditLogService::custom('website', 'delete', 'Menghapus file APK dari website');
        return response()->json(['success' => true, 'message' => 'APK berhasil dihapus!']);
    }

    private function formatFileSize($bytes)
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        return round($bytes / 1024, 0) . ' KB';
    }
}
