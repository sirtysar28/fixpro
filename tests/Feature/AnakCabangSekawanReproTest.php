<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Role;
use App\Models\Stok;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Repro bug yang dilaporkan user (grup Sekawan, anak cabang Sekawan5):
 * 1. Role "Admin Cabang Anak" tidak bisa melakukan transaksi POS penjualan sparepart.
 * 2. Cari sparepart (POS search API) error untuk anak cabang.
 * 3. Di daftar stok, saat cari merk data "pindah" ke cabang lain.
 */
class AnakCabangSekawanReproTest extends TestCase
{
    use RefreshDatabase;

    private Cabang $pusatSekawan;
    private Cabang $sekawan5;
    private User $anakCabangSekawan5;
    private User $enterpriseAdminSekawan;

    protected function setUp(): void
    {
        parent::setUp();

        // FIXPRO Pusat (milik owner aplikah = cabang lain / toko lain)
        Cabang::create(['nama' => 'FIXPRO Pusat', 'alamat' => 'Pusat', 'telepon' => '081', 'aktif' => true]);

        // Grup SEKAWAN (enterprise): 1 pusat + beberapa anak, Sekawan5 = anak
        $this->pusatSekawan = Cabang::create(['nama' => 'Sekawan Pusat', 'alamat' => 'Sekawan', 'telepon' => '082', 'aktif' => true]);
        $this->sekawan5 = Cabang::create([
            'nama' => 'Sekawan5', 'alamat' => 'Sekawan5', 'telepon' => '083', 'aktif' => true,
            'parent_cabang_id' => $this->pusatSekawan->id,
        ]);

        $roleAdmin = Role::firstOrCreate(['name' => 'Admin'], ['description' => 'Admin']);
        Role::firstOrCreate(['name' => 'Admin Cabang Anak'], ['description' => 'Anak cabang']);
        Role::firstOrCreate(['name' => 'Staff'], ['description' => 'Staff']);
        Role::firstOrCreate(['name' => 'User'], ['description' => 'User']);

        // Admin enterprise Sekawan (pemilik grup) — pembuat semua cabang grup
        $this->enterpriseAdminSekawan = User::create([
            'name' => 'Owner Sekawan', 'email' => 'owner@sekawan.test',
            'password' => 'password', 'role_id' => $roleAdmin->id,
            'cabang_id' => $this->pusatSekawan->id,
            'paket' => 'enterprise', 'is_active' => true,
        ]);
        $this->pusatSekawan->update(['created_by_user_id' => $this->enterpriseAdminSekawan->id]);
        $this->sekawan5->update(['created_by_user_id' => $this->enterpriseAdminSekawan->id]);

        // Akun anak cabang Sekawan5
        $roleAnak = Role::where('name', 'Admin Cabang Anak')->first();
        $this->anakCabangSekawan5 = User::create([
            'name' => 'Admin Sekawan5', 'email' => 'sekawan5@sekawan.test',
            'password' => 'password', 'role_id' => $roleAnak->id,
            'cabang_id' => $this->sekawan5->id,
            'is_active' => true,
        ]);

        // Stok: barang di Sekawan5 dan barang SEJENIS di cabang lain (Sekawan pusat & FIXPRO Pusat)
        foreach ([1 => 'FIXPRO Pusat', $this->pusatSekawan->id => 'Sekawan Pusat', $this->sekawan5->id => 'Sekawan5'] as $cid => $label) {
            Stok::create([
                'kode' => 'LCD-VIV-' . $cid, 'nama' => "LCD Vivo Y20 ({$label})",
                'kategori' => 'LCD', 'merk_hp' => 'Vivo',
                'stok' => 5, 'modal' => 150000, 'jual' => 250000, 'min_alert' => 2, 'cabang_id' => $cid,
            ]);
            Stok::create([
                'kode' => 'BTC-SAM-' . $cid, 'nama' => "Baterai Samsung A51 ({$label})",
                'kategori' => 'Baterai', 'merk_hp' => 'Samsung',
                'stok' => 0, 'modal' => 80000, 'jual' => 150000, 'min_alert' => 2, 'cabang_id' => $cid,
            ]);
        }
    }

    // ============================================================
    // BUG #1 — Anak cabang tidak bisa transaksi POS (search error)
    // ============================================================

    public function test_anak_cabang_bisa_buka_pos_penjualan_sparepart(): void
    {
        $this->actingAs($this->anakCabangSekawan5);
        $response = $this->get(route('penjualan-sparepart.index'));
        $response->assertOk();
    }

    public function test_anak_cabang_bisa_search_sparepart_pos(): void
    {
        $this->actingAs($this->anakCabangSekawan5);
        $response = $this->getJson(route('penjualan-sparepart.api.search-suggest', ['q' => 'vivo']));
        $response->assertOk();
        $response->assertJsonCount(1, 'products'); // hanya milik Sekawan5
        $this->assertSame('LCD Vivo Y20 (Sekawan5)', $response->json('products.0.nama'));
    }

    public function test_anak_cabang_bisa_checkout_transaksi_pos(): void
    {
        $this->actingAs($this->anakCabangSekawan5);
        $item = Stok::where('cabang_id', $this->sekawan5->id)->where('stok', '>', 0)->first();

        $response = $this->postJson(route('penjualan-sparepart.store-cart'), [
            'items' => [[
                'stok_id' => $item->id,
                'qty' => 1,
                'harga_satuan' => 250000,
            ]],
            'metode_bayar' => 'Cash',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('penjualan_sparepart', [
            'stok_id' => $item->id,
            'cabang_id' => $this->sekawan5->id,
            'qty' => 1,
        ]);
        $this->assertSame(4, (int) $item->fresh()->stok, 'Stok Sekawan5 harus berkurang');
    }

    // ============================================================
    // BUG #2 — Daftar stok: cari merk tiba-tiba data pindah cabang
    // ============================================================

    public function test_anak_cabang_cari_merk_data_tetap_di_cabang_sendiri(): void
    {
        $this->actingAs($this->anakCabangSekawan5);
        $response = $this->get(route('stok.index', ['search' => 'Vivo', 'merk' => 'Vivo']));

        $response->assertOk();
        $response->assertViewHas('stoks', fn ($stoks) => $stoks->count() === 1
            && $stoks->first()->cabang_id === $this->sekawan5->id);
    }

    public function test_enterprise_admin_filter_cabang_anak_tidak_melompat_cabang_lain(): void
    {
        $this->actingAs($this->enterpriseAdminSekawan);

        // Owner pusat membuka stok cabang anak Sekawan5 lalu mencari merk
        $response = $this->get(route('stok.index', ['cabang' => $this->sekawan5->id, 'search' => 'Vivo', 'merk' => 'Vivo']));

        $response->assertOk();
        $response->assertViewHas('stoks', fn ($stoks) => $stoks->count() === 1
            && $stoks->first()->cabang_id === $this->sekawan5->id);

        // Dropdown merk juga hanya berisi merk yang ada di cabang terpilih
        $response->assertViewHas('merkList', fn ($list) => $list->count() > 0);
    }

    public function test_enterprise_admin_submit_filter_tanpa_param_cabang_tetap_pusat(): void
    {
        $this->actingAs($this->enterpriseAdminSekawan);
        // Submit form tanpa param cabang (mis. user tak sengaja) → harus jatuh ke cabang aktif sendiri, BUKAN cabang 1 milik toko lain
        $response = $this->get(route('stok.index', ['search' => 'Samsung']));

        $response->assertOk();
        $response->assertViewHas('stoks', fn ($stoks) => $stoks->every(fn ($s) => $s->cabang_id === $this->pusatSekawan->id));
    }

    // ============================================================
    // BUG #3 — Fallback ?? 1 (anak cabang dgn cabang_id NULL nyasar ke cabang 1 toko lain)
    // ============================================================

    public function test_anak_cabang_cabang_null_tidak_diarahkan_ke_cabang_toko_lain(): void
    {
        $user = $this->anakCabangSekawan5;
        $user->update(['cabang_id' => null]);

        $this->actingAs($user);

        // getActiveCabangId HARUS tidak mengembalikan 1 (milik toko lain)
        $active = $user->fresh()->getActiveCabangId();
        $this->assertNotSame(1, $active, 'Anak cabang tanpa cabang_id tidak boleh dilempar ke cabang 1 (toko lain)');
    }

    // ============================================================
    // Dashboard notif stok alert — anak cabang hanya notif cabangnya
    // ============================================================

    public function test_dashboard_stok_alerts_anak_cabang_hanya_cabang_sendiri(): void
    {
        $this->actingAs($this->anakCabangSekawan5);
        $response = $this->getJson(route('api.stok-alerts'));

        $response->assertOk();
        $items = collect($response->json('items'));
        $this->assertTrue($items->isNotEmpty(), 'Notif stok habis harus muncul (baterai stok 0)');
        $ids = Stok::whereIn('id', $items->pluck('id'))->pluck('cabang_id')->unique();
        $this->assertTrue(
            $ids->isNotEmpty() && $ids->every(fn ($cid) => (int) $cid === $this->sekawan5->id),
            'Semua notif harus milik Sekawan5'
        );
    }
}
