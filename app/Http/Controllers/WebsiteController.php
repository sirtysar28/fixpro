<?php

namespace App\Http\Controllers;

use App\Models\WebsiteContent;
use App\Models\BannerIklan;
use Illuminate\Http\Request;

class WebsiteController extends Controller
{
    /**
     * Show the public landing page
     */
    public function index()
    {
        // Get all website content
        $sections = WebsiteContent::getAllSections();

        // Get active banners for hero slider
        $banners = BannerIklan::where('aktif', true)->orderBy('urutan')->get();

        // Parse JSON fields
        $heroStats = WebsiteContent::getJson('hero', 'stats', []);
        $featureItems = WebsiteContent::getJson('features', 'items', []);
        $serviceItems = WebsiteContent::getJson('services', 'items', []);
        $testimonialItems = WebsiteContent::getJson('testimonials', 'items', []);
        $pricingItems = WebsiteContent::getJson('pricing', 'items', []);
        $faqItems = WebsiteContent::getJson('faq', 'items', []);

        // Get plain text content
        $hero = WebsiteContent::getSection('hero');
        $about = WebsiteContent::getSection('about');
        $features = WebsiteContent::getSection('features');
        $services = WebsiteContent::getSection('services');
        $testimonials = WebsiteContent::getSection('testimonials');
        $cta = WebsiteContent::getSection('cta');
        $contact = WebsiteContent::getSection('contact');
        $footer = WebsiteContent::getSection('footer');
        $pricing = WebsiteContent::getSection('pricing');
        $faq = WebsiteContent::getSection('faq');
        $waGroup = WebsiteContent::getSection('wa_group');

        // Get about image
        $aboutImage = null;
        $aboutImg = WebsiteContent::where('section', 'about')->where('key', 'image')->first();
        if ($aboutImg && $aboutImg->image) {
            $aboutImage = $aboutImg->image;
        } elseif ($aboutImg && $aboutImg->value) {
            $aboutImage = $aboutImg->value;
        }

        // Get WhatsApp group QR image
        $waGroupQrImage = null;
        $waQr = WebsiteContent::where('section', 'wa_group')->where('key', 'qr_image')->first();
        if ($waQr && $waQr->image) {
            $waGroupQrImage = $waQr->image;
        }

        // Get mobile app APK data
        $mobileApp = WebsiteContent::getSection('mobile_app');
        $apkAvailable = false;
        $apkItem = WebsiteContent::where('section', 'mobile_app')->where('key', 'apk_file')->first();
        if ($apkItem && $apkItem->value && \Storage::disk('public')->exists($apkItem->value)) {
            $apkAvailable = true;
        }

        return view('website.index', compact(
            'banners', 'hero', 'heroStats', 'about', 'aboutImage',
            'features', 'featureItems', 'services', 'serviceItems',
            'testimonials', 'testimonialItems', 'cta', 'contact',
            'footer', 'pricing', 'pricingItems', 'faq', 'faqItems',
            'waGroup', 'waGroupQrImage',
            'mobileApp', 'apkAvailable'
        ));
    }

    /**
     * Track service status (public)
     */
    public function lacakServis(Request $request)
    {
        $request->validate(['kode' => 'required|string']);
        $servis = \App\Models\Servis::where('kode', $request->kode)->first();

        if (!$servis) {
            return response()->json(['error' => 'Kode servis tidak ditemukan'], 404);
        }

        return response()->json([
            'kode' => $servis->kode,
            'perangkat' => $servis->perangkat,
            'keluhan' => $servis->keluhan,
            'status' => $servis->status,
            'pelanggan' => $servis->pelanggan?->nama ?? '-',
            'teknisi' => $servis->teknisi?->nama ?? '-',
            'cabang' => $servis->cabang?->nama ?? '-',
            'biaya' => $servis->biaya ? 'Rp ' . number_format($servis->biaya, 0, ',', '.') : '-',
            'created_at' => $servis->created_at?->format('d M Y H:i') ?? '-',
            'updated_at' => $servis->updated_at?->format('d M Y H:i') ?? '-',
        ]);
    }

    public function downloadApk()
    {
        $apk = WebsiteContent::where('section', 'mobile_app')->where('key', 'apk_file')->first();

        if (!$apk || !$apk->value) {
            abort(404, 'APK belum tersedia.');
        }

        $path = storage_path('app/public/' . $apk->value);
        if (!file_exists($path)) {
            abort(404, 'File APK tidak ditemukan.');
        }

        $appName = $apk->value;
        $displayName = 'FixPro-v' . ($apk->image ?: '1.0') . '.apk';

        return response()->download($path, $displayName);
    }
}
