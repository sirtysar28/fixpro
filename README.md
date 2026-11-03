# FIXPRO — Aplikasi Manajemen Service HP

<div align="center">

**fixpro.id** — Platform manajemen toko service HP terintegrasi: servis, sparepart, grosir, multi-cabang, pembayaran, WhatsApp, dan aplikasi Android.

</div>

---

## 📋 Tentang FIXPRO

FIXPRO adalah aplikasi berbasis web (SaaS) untuk mengelola usaha service HP yang terdiri dari **dashboard admin berbasis web** dan **API untuk aplikasi Android**. Aplikasi ini membantu teknisi, admin, dan pemilik toko dalam mengelola tiket servis, stok sparepart, penjualan grosir, keuangan, hingga komunikasi dengan pelanggan via WhatsApp — semuanya dalam satu platform.

---

## ✨ Fitur Utama

### 🌐 Website Publik (fixpro.id)
- Landing page dinamis yang dapat dikelola via **Website Management** (CMS internal)
- **Lacak Servis** — pelanggan dapat melacak status perbaikan HP dengan kode servis
- Halaman **Download APK** untuk aplikasi Android
- Banner iklan & promo card

### 🔧 Manajemen Servis HP
- Tiket servis lengkap: data pelanggan, tipe HP, kerusakan, teknisi, estimasi biaya
- Alur status servis (diterima → dikerjakan → selesai → diambil / batal)
- Nota servis **PDF** (DomPDF) & cetak **thermal print** (struk 58/80mm)
- Kirim nota & notifikasi ke pelanggan via **WhatsApp**
- Arsip servis riwayat
- **My Service** — pelanggan mendaftar & memantau servisnya sendiri
- **Dashboard Teknisi** — teknisi melihat & mengelola pekerjaan yang ditugaskan

### 📦 Stok & Sparepart
- Manajemen stok sparepart dengan notifikasi stok menipis
- **Kartu Stok** (aktivitas sparepart) — riwayat masuk/keluar barang
- **POS Penjualan Sparepart** — transaksi kasir cepat + struk thermal
- **Pembelian** sparepart ke supplier + pembayaran
- **Invoice & Tagihan Sparepart** untuk penjualan tempo
- **Serial Number tracking** per unit sparepart
- **Transfer Stok** antar cabang (paket Enterprise)
- Cetak **barcode** produk

### 🏬 Modul Grosir
- Stok grosir terpisah dari stok ritel
- **Harga Grosir** bertingkat + harga khusus per pelanggan
- Penjualan grosir, pesanan (order), dan retur
- Manajemen **piutang grosir** + pembayaran
- Laporan grosir lengkap

### 💰 Keuangan
- Manajemen **Kas** (kas masuk / kas keluar)
- **Laporan Keuangan** — laba/rugi, arus kas, rekap transaksi
- **Laporan Servis & Penjualan** dengan filter periode
- Export laporan ke Excel (XLSX)

### 🏢 Multi Cabang
- Cabang pusat & **cabang anak** dengan aktivasi terpusat
- Switch cabang untuk Admin Cabang Anak
- Laporan terpisah per cabang

### 💳 Langganan & Lisensi (SaaS)
- **Paket Berlangganan**:
  | Paket | Durasi | Cabang |
  |---|---|---|
  | Standar | 3 bulan | 1 cabang (toko kecil) |
  | Enterprise | 3 bulan | 1 pusat + maks 3 cabang anak + transfer stok |
- **Serial Number / Kode Aktivasi** — lisensi per pengguna, redeem via profil
- **Activation Request** — permintaan aktivasi dari cabang anak
- Integrasi **Payment Gateway** dengan webhook otomatis

### 📱 Aplikasi Android (REST API)
- API lengkap berbasis **Laravel Sanctum** (token auth)
- Sinkronisasi data servis, stok, kas, jual-beli, pelanggan, teknisi
- **Offline Sync** — transaksi offline tersinkron otomatis saat online (dengan penanganan konflik)
- Download APK langsung dari website

### 💬 Komunikasi
- **Integrasi WhatsApp** — notifikasi otomatis + webhook
- **Chat internal** antar pengguna + chatbot (bot assistant)
- Chat admin ↔ pelanggan

### ⚙️ Administrasi Sistem
- **Multi Role**: Super Admin, Admin, Staff, User, Teknisi, Admin Cabang Anak
- **User Management** — CRUD pengguna & kontrol role
- **Audit Log** — jejak semua aktivitas penting (khusus Super Admin)
- **Multi Bahasa** — dukungan beberapa bahasa + manajemen terjemahan
- **Login Google OAuth 2.0**
- Manajemen rekening **Bank**
- Pengaturan toko (logo, nama, alamat, dll)

---

## 🛠 Teknologi

| Komponen | Teknologi |
|---|---|
| Backend | **Laravel 11** (PHP ≥ 8.2) |
| Frontend | **Tailwind CSS**, **Alpine.js**, **Vite** |
| API Mobile | **Laravel Sanctum** |
| PDF | **DomPDF** |
| Database | MySQL / MariaDB (via `.env`) |
| Testing | **PHPUnit** |

---

## 📁 Struktur Direktori (Ringkas)

```
Fixpro-new/
├── app/
│   ├── Http/Controllers/     # Controller web (Servis, Stok, Grosir, Kas, dll)
│   │   └── Api/              # Controller API untuk aplikasi Android
│   ├── Models/               # Eloquent Models (50+ model)
│   ├── Services/             # Logika bisnis (Payment, WhatsApp, Sync, Audit, dll)
│   ├── Helpers/              # Helper function global
│   └── Middleware/           # Role check, status aktif, dll
├── database/
│   ├── migrations/           # Skema database
│   └── seeders/              # Data awal
├── resources/views/          # Blade templates (per modul)
├── routes/
│   ├── web.php               # Route aplikasi web
│   ├── api.php               # Route API (prefix /api)
│   └── auth.php              # Route autentikasi
├── config/                   # Konfigurasi aplikasi
└── public/                   # Document root & aset
```

---

## 🚀 Instalasi (Development)

### Prasyarat
- PHP ≥ 8.2 (dengan ekstensi: `pdo`, `mbstring`, `gd`, `zip`, dll)
- Composer
- Node.js & NPM
- MySQL / MariaDB

### Langkah Instalasi

```bash
# 1. Clone / salin project
cd Fixpro-new

# 2. Install dependency PHP
composer install

# 3. Install dependency frontend
npm install

# 4. Salin file environment
cp .env.example .env

# 5. Generate application key
php artisan key:generate

# 6. Konfigurasi database di file .env
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_DATABASE=fixpro
#    DB_USERNAME=...
#    DB_PASSWORD=...

# 7. Jalankan migrasi & seeder
php artisan migrate --seed

# 8. Build aset frontend (mode production)
npm run build
# atau mode development:
npm run dev

# 9. Jalankan server development
php artisan serve
```

Aplikasi web dapat diakses di `http://localhost:8000`.

> **Catatan:** Hak akses & kredensial login bersifat privat dan hanya dikelola oleh pemilik aplikasi melalui panel internal. Informasi akun tidak didistribusikan di dokumen ini.

---

## 🔌 API Aplikasi Android

Endpoint API berada di prefix `/api` dengan autentikasi token **Sanctum**. Cakupan API:

- Autentikasi (login/register/logout via token, redeem serial)
- Dashboard & statistik
- CRUD Servis + quick status + arsip
- My Service (pelanggan)
- CRUD Stok, Kas, Jual-Beli, Pelanggan, Teknisi, Tipe HP
- Penjualan sparepart & laporan keuangan (export)
- Banner, settings, cabang
- User management & audit log (Super Admin)
- **Offline Sync** (`/api/offline/sync`)

Detail endpoint dapat dilihat di `routes/api.php`.

---

## 🧪 Menjalankan Test

```bash
php artisan test
```

---

## 🌍 Deploy ke Produksi

1. Arahkan document root server ke folder `public/`
2. `composer install --optimize-autoloader --no-dev`
3. `npm run build`
4. Konfigurasi `.env` produksi (`APP_ENV=production`, `APP_DEBUG=false`)
5. `php artisan migrate --force`
6. Optimasi:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
7. Pastikan folder `storage/` dan `bootstrap/cache` dapat ditulis server
8. Setup webhook Payment Gateway & WhatsApp ke endpoint publik:
   - `POST /payment/webhook`
   - `POST /whatsapp/webhook`

---

## 📄 Lisensi

Aplikasi internal **FIXPRO (fixpro.id)**. Seluruh hak cipta dilindungi.
