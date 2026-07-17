<?php

namespace Tests\Feature;

use App\Models\Anak;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\OrangTua;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnakTest extends TestCase
{
    use RefreshDatabase;

    protected Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();

        $adminUser = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($adminUser, ['*']);

        $guruUser = User::factory()->create(['role' => 'guru', 'status' => 'approved']);
        $guru = Guru::create([
            'id_user'   => $guruUser->id,
            'nik'       => '1234567890123456',
            'nama_guru' => 'Bu Siska',
            'no_telp'   => '081234567890',
            'email'     => $guruUser->email,
        ]);

        $this->kelas = Kelas::create([
            'id_guru'      => $guru->id_guru,
            'nama_kelas'   => 'Kelas A',
            'tahun_ajaran' => '2025/2026',
        ]);
    }

    /**
     * Helper: buat orang tua dengan status user tertentu, kembalikan model OrangTua.
     */
    protected function buatOrangTua(string $status = 'approved'): OrangTua
    {
        $ortuUser = User::factory()->create(['role' => 'orang_tua', 'status' => $status]);

        return OrangTua::create([
            'id_user'       => $ortuUser->id,
            'nik'           => (string) fake()->unique()->numerify('################'), // 16 digit unik
            'nama_orangtua' => 'Ibu Ani',
            'no_telp'       => '081298765432',
        ]);
    }

    /** ---------- INDEX ---------- */

    public function test_index_hanya_menampilkan_anak_dengan_orangtua_approved()
    {
        $orangTuaApproved = $this->buatOrangTua('approved');
        Anak::create([
            'id_kelas'      => $this->kelas->id_kelas,
            'id_orangtua'   => $orangTuaApproved->id_orangtua,
            'nama_anak'     => 'Budi',
            'jenis_kelamin' => 'L',
        ]);

        $orangTuaPending = $this->buatOrangTua('pending');
        Anak::create([
            'id_kelas'      => $this->kelas->id_kelas,
            'id_orangtua'   => $orangTuaPending->id_orangtua,
            'nama_anak'     => 'Doni',
            'jenis_kelamin' => 'L',
        ]);

        $response = $this->getJson('/api/anak');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonFragment(['nama_anak' => 'Budi']);
    }

    public function test_index_bisa_difilter_berdasarkan_id_kelas()
    {
        $kelasLain = Kelas::create([
            'id_guru'      => $this->kelas->id_guru,
            'nama_kelas'   => 'Kelas B',
            'tahun_ajaran' => '2025/2026',
        ]);

        $orangTua = $this->buatOrangTua('approved');
        Anak::create([
            'id_kelas'      => $this->kelas->id_kelas,
            'id_orangtua'   => $orangTua->id_orangtua,
            'nama_anak'     => 'Budi',
            'jenis_kelamin' => 'L',
        ]);

        $orangTua2 = $this->buatOrangTua('approved');
        Anak::create([
            'id_kelas'      => $kelasLain->id_kelas,
            'id_orangtua'   => $orangTua2->id_orangtua,
            'nama_anak'     => 'Sari',
            'jenis_kelamin' => 'P',
        ]);

        $response = $this->getJson('/api/anak?id_kelas=' . $this->kelas->id_kelas);

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonFragment(['nama_anak' => 'Budi']);
    }

    /** ---------- STORE ---------- */

    public function test_store_gagal_jika_field_wajib_kosong()
    {
        $response = $this->postJson('/api/anak', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['id_kelas', 'nama_anak', 'jenis_kelamin']);
    }

    public function test_store_gagal_jika_id_kelas_tidak_ada()
    {
        $response = $this->postJson('/api/anak', [
            'id_kelas'      => 9999,
            'nama_anak'     => 'Budi',
            'jenis_kelamin' => 'L',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['id_kelas']);
    }

    public function test_store_gagal_jika_jenis_kelamin_tidak_valid()
    {
        $response = $this->postJson('/api/anak', [
            'id_kelas'      => $this->kelas->id_kelas,
            'nama_anak'     => 'Budi',
            'jenis_kelamin' => 'X',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['jenis_kelamin']);
    }

    public function test_store_berhasil_menyimpan_anak()
    {
        $response = $this->postJson('/api/anak', [
            'id_kelas'      => $this->kelas->id_kelas,
            'nama_anak'     => 'Budi',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2020-01-01',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('anak', [
            'nama_anak'     => 'Budi',
            'id_kelas'      => $this->kelas->id_kelas,
            'jenis_kelamin' => 'L',
        ]);
    }

    /** ---------- SHOW ---------- */

    public function test_show_mengembalikan_404_jika_tidak_ditemukan()
    {
        $response = $this->getJson('/api/anak/9999');

        $response->assertStatus(404);
    }

    public function test_show_mengembalikan_detail_anak()
    {
        $anak = Anak::create([
            'id_kelas'      => $this->kelas->id_kelas,
            'nama_anak'     => 'Budi',
            'jenis_kelamin' => 'L',
        ]);

        $response = $this->getJson('/api/anak/' . $anak->id_anak);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['nama_anak' => 'Budi'],
                 ]);
    }

    /** ---------- UPDATE ---------- */

    public function test_update_gagal_jika_tidak_ditemukan()
    {
        $response = $this->putJson('/api/anak/9999', ['nama_anak' => 'Budi Update']);

        $response->assertStatus(404);
    }

    public function test_update_berhasil_mengubah_nama_anak()
    {
        $anak = Anak::create([
            'id_kelas'      => $this->kelas->id_kelas,
            'nama_anak'     => 'Budi',
            'jenis_kelamin' => 'L',
        ]);

        $response = $this->putJson('/api/anak/' . $anak->id_anak, [
            'nama_anak' => 'Budi Santoso',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['nama_anak' => 'Budi Santoso'],
                 ]);

        $this->assertDatabaseHas('anak', [
            'id_anak'   => $anak->id_anak,
            'nama_anak' => 'Budi Santoso',
        ]);
    }

    /** ---------- DESTROY ---------- */

    public function test_destroy_gagal_jika_tidak_ditemukan()
    {
        $response = $this->deleteJson('/api/anak/9999');

        $response->assertStatus(404);
    }

    public function test_destroy_berhasil_menghapus_anak()
    {
        $anak = Anak::create([
            'id_kelas'      => $this->kelas->id_kelas,
            'nama_anak'     => 'Budi',
            'jenis_kelamin' => 'L',
        ]);

        $response = $this->deleteJson('/api/anak/' . $anak->id_anak);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Data berhasil dihapus',
                 ]);

        $this->assertDatabaseMissing('anak', ['id_anak' => $anak->id_anak]);
    }
}