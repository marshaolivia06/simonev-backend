<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KelasTest extends TestCase
{
    use RefreshDatabase;

    protected function buatGuru(): Guru
    {
        $user = User::factory()->create(['role' => 'guru', 'status' => 'approved']);

        return Guru::create([
            'id_user'   => $user->id,
            'nik'       => (string) fake()->unique()->numerify('################'),
            'nama_guru' => 'Bu Siska',
            'no_telp'   => '081234567890',
            'email'     => $user->email,
        ]);
    }

    /** ---------- INDEX ---------- */

    public function test_index_bisa_diakses_tanpa_login_dan_mengembalikan_semua_kelas()
    {
        Kelas::create(['nama_kelas' => 'Kelas A', 'tahun_ajaran' => '2025/2026']);
        Kelas::create(['nama_kelas' => 'Kelas B', 'tahun_ajaran' => '2025/2026']);

        $response = $this->getJson('/api/kelas');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    public function test_index_sebagai_admin_mengembalikan_semua_kelas()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);

        Kelas::create(['nama_kelas' => 'Kelas A', 'tahun_ajaran' => '2025/2026']);
        Kelas::create(['nama_kelas' => 'Kelas B', 'tahun_ajaran' => '2025/2026']);

        $response = $this->getJson('/api/kelas');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    public function test_index_sebagai_guru_hanya_mengembalikan_kelas_miliknya()
    {
        $guru1 = $this->buatGuru();
        $guru2 = $this->buatGuru();

        Kelas::create(['nama_kelas' => 'Kelas A', 'id_guru' => $guru1->id_guru, 'tahun_ajaran' => '2025/2026']);
        Kelas::create(['nama_kelas' => 'Kelas B', 'id_guru' => $guru2->id_guru, 'tahun_ajaran' => '2025/2026']);

        Sanctum::actingAs($guru1->user, ['*']);

        $response = $this->getJson('/api/kelas');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonFragment(['nama_kelas' => 'Kelas A']);
    }

    public function test_index_sebagai_guru_tanpa_data_guru_mengembalikan_kosong()
    {
        $user = User::factory()->create(['role' => 'guru', 'status' => 'approved']);
        Sanctum::actingAs($user, ['*']);

        Kelas::create(['nama_kelas' => 'Kelas A', 'tahun_ajaran' => '2025/2026']);

        $response = $this->getJson('/api/kelas');

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'data');
    }

    /** ---------- STORE ---------- */

    public function test_store_gagal_jika_field_wajib_kosong()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/kelas', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nama_kelas', 'tahun_ajaran']);
    }

    public function test_store_berhasil_tanpa_id_guru()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/kelas', [
            'nama_kelas'   => 'Kelas A',
            'tahun_ajaran' => '2025/2026',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('kelas', ['nama_kelas' => 'Kelas A']);
    }

    public function test_store_berhasil_dengan_id_guru()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);

        $guru = $this->buatGuru();

        $response = $this->postJson('/api/kelas', [
            'nama_kelas'   => 'Kelas A',
            'id_guru'      => $guru->id_guru,
            'tahun_ajaran' => '2025/2026',
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['nama_guru' => 'Bu Siska']);
    }

    public function test_store_gagal_jika_id_guru_tidak_ada()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/kelas', [
            'nama_kelas'   => 'Kelas A',
            'id_guru'      => 9999,
            'tahun_ajaran' => '2025/2026',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['id_guru']);
    }

    /** ---------- SHOW ---------- */

    public function test_show_gagal_tanpa_login()
    {
        $kelas = Kelas::create(['nama_kelas' => 'Kelas A', 'tahun_ajaran' => '2025/2026']);

        $response = $this->getJson('/api/kelas/' . $kelas->id_kelas);

        $response->assertStatus(401);
    }

    public function test_show_mengembalikan_404_jika_tidak_ditemukan()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/kelas/9999');

        $response->assertStatus(404);
    }

    public function test_show_mengembalikan_detail_kelas()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);

        $kelas = Kelas::create(['nama_kelas' => 'Kelas A', 'tahun_ajaran' => '2025/2026']);

        $response = $this->getJson('/api/kelas/' . $kelas->id_kelas);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['nama_kelas' => 'Kelas A'],
                 ]);
    }

    /** ---------- UPDATE ---------- */

    public function test_update_gagal_jika_field_wajib_kosong()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);

        $kelas = Kelas::create(['nama_kelas' => 'Kelas A', 'tahun_ajaran' => '2025/2026']);

        $response = $this->putJson('/api/kelas/' . $kelas->id_kelas, []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nama_kelas', 'tahun_ajaran']);
    }

    public function test_update_berhasil_mengubah_kelas()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);

        $kelas = Kelas::create(['nama_kelas' => 'Kelas A', 'tahun_ajaran' => '2025/2026']);

        $response = $this->putJson('/api/kelas/' . $kelas->id_kelas, [
            'nama_kelas'   => 'Kelas A Updated',
            'tahun_ajaran' => '2026/2027',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['nama_kelas' => 'Kelas A Updated'],
                 ]);

        $this->assertDatabaseHas('kelas', [
            'id_kelas'   => $kelas->id_kelas,
            'nama_kelas' => 'Kelas A Updated',
        ]);
    }

    /** ---------- DESTROY ---------- */

    public function test_destroy_gagal_jika_tidak_ditemukan()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->deleteJson('/api/kelas/9999');

        $response->assertStatus(404);
    }

    public function test_destroy_berhasil_menghapus_kelas()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);

        $kelas = Kelas::create(['nama_kelas' => 'Kelas A', 'tahun_ajaran' => '2025/2026']);

        $response = $this->deleteJson('/api/kelas/' . $kelas->id_kelas);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Data berhasil dihapus',
                 ]);

        $this->assertDatabaseMissing('kelas', ['id_kelas' => $kelas->id_kelas]);
    }
}