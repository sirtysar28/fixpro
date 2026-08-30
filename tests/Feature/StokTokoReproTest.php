<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Role;
use App\Models\Stok;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StokTokoReproTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $roleName = 'Admin', ?int $cabangId = null): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['description' => 'test']);
        $cabang = Cabang::firstOrCreate(
            ['nama' => 'Toko Test'],
            ['alamat' => 'Test', 'telepon' => '0812', 'created_by_user_id' => null]
        );

        return User::create([
            'name' => 'User ' . $roleName,
            'email' => uniqid('user') . '@test.com',
            'password' => 'password',
            'role_id' => $role->id,
            'cabang_id' => $cabangId ?? $cabang->id,
            'is_active' => true,
        ]);
    }

    private function formData(array $override = []): array
    {
        return array_merge([
            'kode' => 'LCD-TEST1',
            'nama' => 'LCD Test',
            'barcode' => '',
            'kategori' => 'LCD',
            'merk_hp' => 'Apple',
            'stok' => '5',
            'min_alert' => '3',
            'modal' => '150000',
            'jual' => '250000',
        ], $override);
    }

    /** Admin toko bisa tambah barang (kasus normal) */
    public function test_admin_toko_bisa_tambah_barang(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        $response = $this->post(route('stok.store'), $this->formData());

        $response->assertRedirect(route('stok.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('stoks', [
            'kode' => 'LCD-TEST1',
            'stok' => 5,
            'cabang_id' => $user->cabang_id,
        ]);
    }

    /** Harga berformat titik ribuan (JS formatter tidak jalan) tetap tersimpan benar */
    public function test_tambah_barang_dengan_harga_berformat_titik(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        $response = $this->post(route('stok.store'), $this->formData([
            'modal' => '1.500.000',
            'jual' => '2.000.000',
            'stok' => '10',
        ]));

        $response->assertRedirect(route('stok.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('stoks', [
            'kode' => 'LCD-TEST1',
            'stok' => 10,
            'modal' => 1500000,
            'jual' => 2000000,
        ]);
    }

    /** Quick update +/- 1 via route yang benar */
    public function test_quick_update_stok(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        $s = Stok::create([
            'kode' => 'BRG-1', 'nama' => 'Barang Satu', 'kategori' => 'LCD',
            'stok' => 3, 'cabang_id' => $user->cabang_id,
        ]);

        $response = $this->postJson('/quick-stok', ['id' => $s->id, 'delta' => 2]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertEquals(5, $s->fresh()->stok);
    }

    /** Edit jumlah stok via form edit */
    public function test_admin_toko_bisa_update_stok(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        $s = Stok::create([
            'kode' => 'BRG-1', 'nama' => 'Barang Satu', 'kategori' => 'LCD',
            'stok' => 3, 'cabang_id' => $user->cabang_id,
        ]);

        $response = $this->put(route('stok.update', $s), [
            'kode' => 'BRG-1',
            'nama' => 'Barang Satu',
            'barcode' => $s->barcode,
            'kategori' => 'LCD',
            'merk_hp' => '',
            'stok' => '10',
            'min_alert' => '3',
            'modal' => '100.000',
            'jual' => '150.000',
        ]);

        $response->assertRedirect(route('stok.index'));
        $this->assertEquals(10, $s->fresh()->stok);
        $this->assertEquals(100000, (float) $s->fresh()->modal);
    }

    /** Kode duplikat se-cabang → error validasi terlihat (bukan diam-diam) */
    public function test_kode_duplikat_validasi_error(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        Stok::create([
            'kode' => 'LCD-DUP', 'nama' => 'Lama', 'kategori' => 'LCD',
            'stok' => 1, 'cabang_id' => $user->cabang_id,
        ]);

        $response = $this->from(route('stok.create'))->post(route('stok.store'), $this->formData(['kode' => 'LCD-DUP']));

        $response->assertRedirect(route('stok.create'));
        $response->assertSessionHasErrors('kode');
        $this->assertDatabaseMissing('stoks', ['nama' => 'LCD Test']);
    }

    /** Admin Cabang Anak dengan cabang valid juga bisa tambah barang */
    public function test_admin_cabang_anak_bisa_tambah_barang(): void
    {
        $user = $this->makeUser('Admin Cabang Anak');
        $this->actingAs($user);

        $response = $this->post(route('stok.store'), $this->formData());

        $response->assertRedirect(route('stok.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('stoks', [
            'kode' => 'LCD-TEST1',
            'cabang_id' => $user->cabang_id,
        ]);
    }

    /** Admin Cabang Anak dengan cabang_id NULL tidak menyimpan ke cabang 0 */
    public function test_admin_cabang_anak_null_cabang_tidak_nyasim_ke_cabang_0(): void
    {
        $pusat = Cabang::firstOrCreate(
            ['nama' => 'Toko Pusat Default'],
            ['alamat' => 'Test', 'telepon' => '0812', 'created_by_user_id' => null]
        );
        $user = $this->makeUser('Admin Cabang Anak', null);
        $user->update(['cabang_id' => null]); // akun bermasalah

        $this->actingAs($user);
        $response = $this->post(route('stok.store'), $this->formData());

        $response->assertRedirect(route('stok.index'));
        $this->assertDatabaseHas('stoks', ['kode' => 'LCD-TEST1']);
        $this->assertDatabaseMissing('stoks', ['kode' => 'LCD-TEST1', 'cabang_id' => 0]);
    }
}
