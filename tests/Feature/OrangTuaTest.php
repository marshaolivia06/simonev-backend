<?php

namespace Tests\Feature;

use App\Models\Anak;
use App\Models\Kelas;
use App\Models\OrangTua;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrangTuaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);
    }

    protected function buatOrangTua(?string $nik = null): OrangTua
    {
        $user = User::factory()->create(['role' => 'orang_tua', 'status' => 'approved']);

        return OrangTua::create([
            'id_user'       => $user->id,
            'nik'           => $nik ?? (string) fake()->unique()->numerify('################'),
            'nama_orangtua' => 'Ibu Ani',
            'no_telp'       => '081298765432',
        ]);
    }

    /** ---------- INDEX ---------- */

    public function test_index_mengembalikan_semua_orang_tua()
    {
        $this->buatOrangTua();
        $this->buatOrangTua();

        $response = $this->getJson('/api/orang-tua');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    /** ---------- SHOW ---------- */

    public function test_show_mengembalikan_404_jika_tidak_ditemukan()
    {
        $response = $this->getJson('/api/orang-tua/9999');

        $response->assertStatus(404);
    }

    public function test_show_mengembalikan_detail_orang_tua()
    {
        $orangTua = $this->buatOrangTua();

        $response = $this->getJson('/api/orang-tua/' . $orangTua->id_orangtua);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['nama_orangtua' => 'Ibu Ani'],
                 ]);
    }

    /** ---------- UPDATE ---------- */

    public function test_update_gagal_jika_tidak_ditemukan()
    {
        $response = $this->putJson('/api/orang-tua/9999', ['nama_orangtua' => 'Ibu Baru']);

        $response->assertStatus(404);
    }

    public function test_update_berhasil_mengubah_data()
    {
        $orangTua = $this->buatOrangTua();

        $response = $this->putJson('/api/orang-tua/' . $orangTua->id_orangtua, [
            'nama_orangtua' => 'Ibu Ani Wijaya',
            'pekerjaan'     => 'Wiraswasta',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => [
                         'nama_orangtua' => 'Ibu Ani Wijaya',
                         'pekerjaan'     => 'Wiraswasta',
                     ],
                 ]);

        $this->assertDatabaseHas('orang_tua', [
            'id_orangtua'   => $orangTua->id_orangtua,
            'nama_orangtua' => 'Ibu Ani Wijaya',
        ]);
    }

    /** ---------- DESTROY ---------- */

    public function test_destroy_gagal_jika_tidak_ditemukan()
    {
        $response = $this->deleteJson('/api/orang-tua/9999');

        $response->assertStatus(404);
    }

    public function test_destroy_berhasil_menghapus_orang_tua()
    {
        $orangTua = $this->buatOrangTua();

        $response = $this->deleteJson('/api/orang-tua/' . $orangTua->id_orangtua);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Data berhasil dihapus',
                 ]);

        $this->assertDatabaseMissing('orang_tua', ['id_orangtua' => $orangTua->id_orangtua]);
    }

    /** ---------- PROFIL ANAK ---------- */

    public function test_profil_anak_mengembalikan_null_jika_belum_ada_data_orangtua()
    {
        $user = User::factory()->create(['role' => 'orang_tua', 'status' => 'approved']);
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/orang-tua/profil/anak');

        $response->assertStatus(200)
                 ->assertJson(['success' => true, 'data' => null]);
    }

    public function test_profil_anak_mengembalikan_data_anak_milik_orangtua_login()
    {
        $user = User::factory()->create(['role' => 'orang_tua', 'status' => 'approved']);
        $orangTua = OrangTua::create([
            'id_user'       => $user->id,
            'nik'           => (string) fake()->unique()->numerify('################'),
            'nama_orangtua' => 'Ibu Ani',
            'no_telp'       => '081298765432',
        ]);

        $guruUser = User::factory()->create(['role' => 'guru', 'status' => 'approved']);
        $guru = \App\Models\Guru::create([
            'id_user'   => $guruUser->id,
            'nik'       => (string) fake()->unique()->numerify('################'),
            'nama_guru' => 'Bu Siska',
            'no_telp'   => '081234567890',
            'email'     => $guruUser->email,
        ]);
        $kelas = Kelas::create([
            'id_guru'      => $guru->id_guru,
            'nama_kelas'   => 'Kelas A',
            'tahun_ajaran' => '2025/2026',
        ]);

        Anak::create([
            'id_kelas'      => $kelas->id_kelas,
            'id_orangtua'   => $orangTua->id_orangtua,
            'nama_anak'     => 'Budi',
            'jenis_kelamin' => 'L',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/orang-tua/profil/anak');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonFragment(['nama_anak' => 'Budi']);
    }
}