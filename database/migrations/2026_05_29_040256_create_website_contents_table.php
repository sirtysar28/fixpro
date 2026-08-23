<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_contents', function (Blueprint $table) {
            $table->id();
            $table->string('section'); // hero, features, about, services, testimonials, cta, contact, footer
            $table->string('key')->nullable(); // sub-key like title, subtitle, description, etc.
            $table->longText('value')->nullable(); // content value (text, HTML, JSON)
            $table->string('image')->nullable(); // image path
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['section', 'key']);
        });

        // Insert default website content
        $now = now();
        DB::table('website_contents')->insert([
            // Hero Section
            ['section' => 'hero', 'key' => 'title', 'value' => 'Solusi Servis HP Profesional', 'image' => null, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'hero', 'key' => 'subtitle', 'value' => 'Kelola bisnis servis HP Anda dengan sistem yang modern, cepat, dan terpercaya. Dari input servis hingga laporan lengkap dalam satu platform.', 'image' => null, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'hero', 'key' => 'cta_text', 'value' => 'Mulai Sekarang', 'image' => null, 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'hero', 'key' => 'cta_link', 'value' => '/login?tab=register', 'image' => null, 'is_active' => true, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'hero', 'key' => 'cta_secondary_text', 'value' => 'Lihat Fitur', 'image' => null, 'is_active' => true, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'hero', 'key' => 'cta_secondary_link', 'value' => '#features', 'image' => null, 'is_active' => true, 'sort_order' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'hero', 'key' => 'background_image', 'value' => null, 'image' => null, 'is_active' => true, 'sort_order' => 7, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'hero', 'key' => 'stats', 'value' => json_encode([
                ['number' => '500+', 'label' => 'Toko Terdaftar'],
                ['number' => '10K+', 'label' => 'Servis Selesai'],
                ['number' => '99%', 'label' => 'Kepuasan'],
                ['number' => '24/7', 'label' => 'Support'],
            ]), 'image' => null, 'is_active' => true, 'sort_order' => 8, 'created_at' => $now, 'updated_at' => $now],

            // About Section
            ['section' => 'about', 'key' => 'title', 'value' => 'Tentang FixPro', 'image' => null, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'about', 'key' => 'description', 'value' => '<p>FixPro adalah platform manajemen servis HP terdepan yang dirancang khusus untuk membantu pemilik bengkel dan toko servis HP dalam mengelola operasional bisnis mereka.</p><p>Dengan teknologi modern dan antarmuka yang mudah digunakan, FixPro membantu Anda meningkatkan efisiensi, akurasi, dan profitabilitas bisnis servis HP.</p>', 'image' => null, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'about', 'key' => 'image', 'value' => null, 'image' => null, 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],

            // Features Section
            ['section' => 'features', 'key' => 'title', 'value' => 'Fitur Unggulan', 'image' => null, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'features', 'key' => 'subtitle', 'value' => 'Semua yang Anda butuhkan untuk mengelola bisnis servis HP', 'image' => null, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'features', 'key' => 'items', 'value' => json_encode([
                ['icon' => 'fas fa-tools', 'title' => 'Manajemen Servis', 'description' => 'Input dan lacak setiap servis HP dengan detail lengkap. Status tracking realtime dari masuk hingga selesai.'],
                ['icon' => 'fas fa-boxes', 'title' => 'Stok & Sparepart', 'description' => 'Kelola inventori sparepart dengan mudah. Notifikasi stok menipis dan laporan otomatis.'],
                ['icon' => 'fas fa-cash-register', 'title' => 'POS & Kas Harian', 'description' => 'Point of Sale terintegrasi untuk penjualan sparepart. Rekap kas harian otomatis.'],
                ['icon' => 'fas fa-chart-line', 'title' => 'Laporan Lengkap', 'description' => 'Dashboard analytics dan laporan detail untuk monitoring performa bisnis.'],
                ['icon' => 'fas fa-store', 'title' => 'Multi Cabang', 'description' => 'Kelola banyak cabang dari satu dashboard. Sinkronisasi data antar lokasi.'],
                ['icon' => 'fas fa-mobile-alt', 'title' => 'Aplikasi Mobile', 'description' => 'Akses dari smartphone kapan saja. Teknisi bisa update status servis langsung dari HP.'],
            ]), 'image' => null, 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],

            // Services Section
            ['section' => 'services', 'key' => 'title', 'value' => 'Layanan Kami', 'image' => null, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'services', 'key' => 'subtitle', 'value' => 'Solusi lengkap untuk setiap kebutuhan bisnis servis Anda', 'image' => null, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'services', 'key' => 'items', 'value' => json_encode([
                ['icon' => 'fas fa-wrench', 'title' => 'Service HP', 'description' => 'Servis semua merk dan tipe HP dengan teknisi berpengalaman. Garansi service untuk setiap perbaikan.'],
                ['icon' => 'fas fa-microchip', 'title' => 'Ganti Sparepart', 'description' => 'Sparepart original dan berkualitas. Tersedia untuk semua merk HP populer.'],
                ['icon' => 'fas fa-exchange-alt', 'title' => 'Jual Beli HP', 'description' => 'Beli dan jual HP bekas dengan harga terbaik. Penilaian kondisi akurat dan transparan.'],
                ['icon' => 'fas fa-shield-alt', 'title' => 'Garansi Servis', 'description' => 'Setiap servis bergaransi. Pelacakan status real-time melalui kode servis.'],
            ]), 'image' => null, 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],

            // Testimonials Section
            ['section' => 'testimonials', 'key' => 'title', 'value' => 'Apa Kata Mereka', 'image' => null, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'testimonials', 'key' => 'subtitle', 'value' => 'Testimoni dari pengguna FixPro yang sudah merasakan manfaatnya', 'image' => null, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'testimonials', 'key' => 'items', 'value' => json_encode([
                ['name' => 'Ahmad Fauzi', 'role' => 'Pemilik Toko Service', 'content' => 'Sejak pakai FixPro, bisnis servis saya jadi lebih terorganisir. Tidak ada lagi servis yang terlewat atau tertukar. Laporan keuangannya juga sangat membantu.', 'rating' => 5],
                ['name' => 'Dewi Sartika', 'role' => 'Admin Bengkel', 'content' => 'Sangat memudahkan pekerjaan saya sebagai admin. Input servis, kas harian, dan stok sparepart semua dalam satu aplikasi. Waktu kerja jadi lebih efisien.', 'rating' => 5],
                ['name' => 'Budi Santoso', 'role' => 'Teknisi HP', 'content' => 'Dengan FitPro saya bisa langsung lihat daftar servis yang harus dikerjakan. Update status juga gampang lewat HP. Gaji saya juga transparan berdasarkan bagi hasil.', 'rating' => 4],
            ]), 'image' => null, 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],

            // CTA Section
            ['section' => 'cta', 'key' => 'title', 'value' => 'Siap Mengembangkan Bisnis Servis Anda?', 'image' => null, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'cta', 'key' => 'subtitle', 'value' => 'Bergabung dengan ratusan pemilik bengkel yang sudah menggunakan FixPro. Daftar gratis dan langsung coba semua fitur premium selama trial.', 'image' => null, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'cta', 'key' => 'button_text', 'value' => 'Daftar Gratis Sekarang', 'image' => null, 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'cta', 'key' => 'button_link', 'value' => '/login?tab=register', 'image' => null, 'is_active' => true, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],

            // Contact Section
            ['section' => 'contact', 'key' => 'title', 'value' => 'Hubungi Kami', 'image' => null, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'contact', 'key' => 'subtitle', 'value' => 'Ada pertanyaan? Tim kami siap membantu Anda', 'image' => null, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'contact', 'key' => 'whatsapp', 'value' => '6281234567890', 'image' => null, 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'contact', 'key' => 'email', 'value' => 'info@fixpro.id', 'image' => null, 'is_active' => true, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'contact', 'key' => 'address', 'value' => 'Jl. Raya Utama No. 88, Indonesia', 'image' => null, 'is_active' => true, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'contact', 'key' => 'instagram', 'value' => 'fixpro.id', 'image' => null, 'is_active' => true, 'sort_order' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'contact', 'key' => 'facebook', 'value' => 'fixpro.id', 'image' => null, 'is_active' => true, 'sort_order' => 7, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'contact', 'key' => 'youtube', 'value' => '', 'image' => null, 'is_active' => true, 'sort_order' => 8, 'created_at' => $now, 'updated_at' => $now],

            // Footer Section
            ['section' => 'footer', 'key' => 'copyright', 'value' => '© 2026 FixPro AL2000. All rights reserved.', 'image' => null, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'footer', 'key' => 'tagline', 'value' => 'Sistem Manajemen Servis Profesional', 'image' => null, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],

            // Pricing Section
            ['section' => 'pricing', 'key' => 'title', 'value' => 'Paket Harga', 'image' => null, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'pricing', 'key' => 'subtitle', 'value' => 'Pilih paket yang sesuai dengan kebutuhan bisnis Anda', 'image' => null, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'pricing', 'key' => 'items', 'value' => json_encode([
                ['name' => 'Trial', 'price' => 'Gratis', 'period' => '1 Bulan', 'features' => ['Semua fitur premium', '1 cabang', 'Support via chat', 'Tanpa kartu kredit'], 'popular' => false, 'button_text' => 'Mulai Trial', 'button_link' => '/login?tab=register'],
                ['name' => 'Standard', 'price' => 'Rp 99K', 'period' => '/bulan', 'features' => ['Semua fitur premium', 'Hingga 3 cabang', 'Priority support', 'Laporan export Excel'], 'popular' => true, 'button_text' => 'Hubungi Kami', 'button_link' => '#contact'],
                ['name' => 'Enterprise', 'price' => 'Custom', 'period' => '', 'features' => ['Semua fitur premium', 'Unlimited cabang', 'Dedicated support', 'Custom integrasi', 'Training tim'], 'popular' => false, 'button_text' => 'Hubungi Kami', 'button_link' => '#contact'],
            ]), 'image' => null, 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],

            // FAQ Section
            ['section' => 'faq', 'key' => 'title', 'value' => 'Pertanyaan Umum', 'image' => null, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'faq', 'key' => 'items', 'value' => json_encode([
                ['question' => 'Apakah FixPro gratis?', 'answer' => 'Ya! FixPro menyediakan trial gratis 1 bulan dengan akses ke semua fitur premium. Setelah itu Anda bisa berlangganan dengan harga terjangkau.'],
                ['question' => 'Apakah bisa digunakan di HP?', 'answer' => 'Tentu! FixPro memiliki aplikasi mobile (Android) dan juga bisa diakses via browser di smartphone Anda. Teknisi bisa update status servis langsung dari HP.'],
                ['question' => 'Bagaimana cara mendaftar?', 'answer' => 'Cukup klik tombol "Daftar" di halaman login, isi data toko Anda, dan langsung bisa menggunakan semua fitur FixPro. Tanpa perlu verifikasi yang rumit.'],
                ['question' => 'Apakah data saya aman?', 'answer' => 'Pasti! Semua data disimpan secara aman dengan enkripsi. Backup otomatis dan hanya Anda yang bisa mengakses data bisnis Anda.'],
            ]), 'image' => null, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('website_contents');
    }
};
