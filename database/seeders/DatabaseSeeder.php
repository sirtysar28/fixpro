<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Pelanggan;
use App\Models\Teknisi;
use App\Models\Stok;
use App\Models\Servis;
use App\Models\Kas;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@fixpro.id',
            'password' => 'admin123',
            'phone' => '081234567890',
            'role_id' => 1, // Admin
            'is_active' => true,
        ]);

        // Staff user
        User::create([
            'name' => 'Staff Kasir',
            'email' => 'staff@fixpro.id',
            'password' => 'staff123',
            'phone' => '082345678901',
            'role_id' => 2, // Staff
            'is_active' => true,
        ]);

        // Sample customers
        $pelanggans = [
            ['nama' => 'Budi Santoso', 'no_hp' => '081234567890', 'alamat' => 'Jl. Merdeka 10'],
            ['nama' => 'Siti Aminah', 'no_hp' => '082345678901', 'alamat' => 'Jl. Sudirman 25'],
            ['nama' => 'Rizky Pratama', 'no_hp' => '083456789012', 'alamat' => 'Jl. Gatot Subroto 5'],
        ];
        foreach ($pelanggans as $p) {
            Pelanggan::create($p);
        }

        // Sample technicians
        Teknisi::create(['nama' => 'Rendi', 'spesialisasi' => 'Apple', 'no_wa' => '081299988877', 'aktif' => true]);
        Teknisi::create(['nama' => 'Dedi', 'spesialisasi' => 'Android', 'no_wa' => '081288877766', 'aktif' => true]);

        // Sample stock
        Stok::create(['kode' => 'LCD-IP11', 'nama' => 'LCD iPhone 11', 'kategori' => 'LCD', 'stok' => 5, 'modal' => 180000, 'jual' => 280000, 'min_alert' => 2]);
        Stok::create(['kode' => 'BAT-SAM-A52', 'nama' => 'Baterai Samsung A52', 'kategori' => 'Baterai', 'stok' => 3, 'modal' => 60000, 'jual' => 120000, 'min_alert' => 2]);
        Stok::create(['kode' => 'FLEX-IPX', 'nama' => 'Flexibel iPhone X', 'kategori' => 'Flexibel', 'stok' => 1, 'modal' => 50000, 'jual' => 100000, 'min_alert' => 1]);

        // Sample service
        Servis::create([
            'kode' => 'SVC-250615-001',
            'pelanggan_id' => 1,
            'perangkat' => 'iPhone 11',
            'keluhan' => 'Ganti LCD',
            'tipe' => 'Apple',
            'status' => 'Selesai',
            'biaya' => 350000,
            'dp' => 200000,
            'modal_sparepart' => 180000,
            'tanggal' => '2025-06-15',
            'teknisi_id' => 1,
            'prioritas' => 'Normal',
            'catatan' => 'LCD original',
            'garansi' => 30,
            'tanggal_garansi' => '2025-07-15',
            'imei' => '356789012345678',
        ]);

        Servis::create([
            'kode' => 'SVC-250615-002',
            'pelanggan_id' => 2,
            'perangkat' => 'Samsung A52',
            'keluhan' => 'Ganti Baterai',
            'tipe' => 'Android',
            'status' => 'Proses',
            'biaya' => 150000,
            'dp' => 100000,
            'modal_sparepart' => 60000,
            'tanggal' => '2025-06-15',
            'teknisi_id' => 2,
            'prioritas' => 'Normal',
            'garansi' => 30,
            'imei' => '490154203237518',
        ]);

        Servis::create([
            'kode' => 'SVC-250614-001',
            'pelanggan_id' => 3,
            'perangkat' => 'iPhone X',
            'keluhan' => 'Mati Total',
            'tipe' => 'Apple',
            'status' => 'Masuk',
            'biaya' => 500000,
            'dp' => 0,
            'tanggal' => '2025-06-14',
            'teknisi_id' => 1,
            'prioritas' => 'Urgent',
            'catatan' => 'Cek IC power',
            'garansi' => 30,
        ]);

        // Sample kas
        Kas::create(['tipe' => 'masuk', 'kategori' => 'Modal', 'jml' => 500000, 'ket' => 'Modal awal kas', 'waktu' => '2025-06-15 08:00:00', 'saldo' => 500000, 'metode' => 'Cash']);
        Kas::create(['tipe' => 'masuk', 'kategori' => 'DP Servis', 'jml' => 200000, 'ket' => 'DP SVC-250615-001 - Budi Santoso', 'ref' => 'SVC-250615-001', 'waktu' => '2025-06-15 09:30:00', 'saldo' => 700000, 'metode' => 'Cash']);
        Kas::create(['tipe' => 'masuk', 'kategori' => 'DP Servis', 'jml' => 100000, 'ket' => 'DP SVC-250615-002 - Siti Aminah', 'ref' => 'SVC-250615-002', 'waktu' => '2025-06-15 10:00:00', 'saldo' => 800000, 'metode' => 'Cash']);
        Kas::create(['tipe' => 'keluar', 'kategori' => 'Beli Sparepart', 'jml' => 180000, 'ket' => 'Beli LCD iPhone 11', 'waktu' => '2025-06-15 11:00:00', 'saldo' => 620000, 'metode' => 'Cash']);
    }
}
