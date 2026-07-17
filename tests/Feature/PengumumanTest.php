<?php

namespace Tests\Feature;

use App\Models\Pengumuman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PengumumanTest extends TestCase
{
    use RefreshDatabase;

    /** ---------- INDEX (publik, tanpa login) ---------- */

    public function test_index_bisa_diakses_tanpa_login()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Pengumuman::create([
            'id_user'          => $admin->id,
            'judul_pengumuman' => 'Libur Semester',
            'isi_pengumuman'   => 'Sekolah libur mulai minggu depan',
            'tanggal'          => '2026-07-01',
            'kategori'         => 'Libur',
        ]);

        $response = $this->getJson('/api/pengumuman');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    public function test_index_mengurutkan_dari_terbaru()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);

        $lama = Pengumuman::create([
            'id_user' => $admin->id, 'judul_pengumuman' => 'Pengumuman Lama',
            'isi_pengumuman' => 'Isi lama', 'tanggal' => '2026-01-01', 'kategori' => 'Info',
        ]);
        $lama->created_at = now()->subDays(5);
        $lama->save();

        Pengumuman::create([
            'id_user' => $admin->id, 'judul_pengumuman' => 'Pengumuman Baru',
            'isi_pengumuman' => 'Isi baru', 'tanggal' => '2026-07-01', 'kategori' => 'Info',
        ]);

        $response = $this->getJson('/api/pengumuman');

        $response->assertStatus(200);
        $this->assertEquals('Pengumuman Baru', $response->json('data.0.judul_pengumuman'));
    }

    /** ---------- SHOW (publik) ---------- */

    public function test_show_mengembalikan_404_jika_tidak_ditemukan()
    {
        $response = $this->getJson('/api/pengumuman/9999');

        $response->assertStatus(404);
    }

    public function test_show_mengembalikan_detail_pengumuman_tanpa_login()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $pengumuman = Pengumuman::create([
            'id_user' => $admin->id, 'judul_pengumuman' => 'Libur Semester',
            'isi_pengumuman' => 'Isi', 'tanggal' => '2026-07-01', 'kategori' => 'Libur',
        ]);

        $response = $this->getJson('/api/pengumuman/' . $pengumuman->id_pengumuman);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['judul_pengumuman' => 'Libur Semester'],
                 ]);
    }

    /** ---------- STORE ---------- */

    public function test_store_gagal_tanpa_login()
    {
        $response = $this->postJson('/api/pengumuman', [
            'judul_pengumuman' => 'Libur Semester',
            'isi_pengumuman'   => 'Isi',
            'tanggal'          => '2026-07-01',
            'kategori'         => 'Libur',
        ]);

        $response->assertStatus(401);
    }

    public function test_store_gagal_jika_field_wajib_kosong()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/pengumuman', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['judul_pengumuman', 'isi_pengumuman', 'tanggal', 'kategori']);
    }

    public function test_store_gagal_jika_kategori_tidak_valid()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/pengumuman', [
            'judul_pengumuman' => 'Libur Semester',
            'isi_pengumuman'   => 'Isi',
            'tanggal'          => '2026-07-01',
            'kategori'         => 'Random',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['kategori']);
    }

    public function test_store_berhasil_dan_id_user_diambil_dari_token()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/pengumuman', [
            'judul_pengumuman' => 'Libur Semester',
            'isi_pengumuman'   => 'Sekolah libur mulai minggu depan',
            'tanggal'          => '2026-07-01',
            'kategori'         => 'Libur',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('pengumuman', [
            'judul_pengumuman' => 'Libur Semester',
            'id_user'          => $admin->id,
        ]);
    }

    /** ---------- UPDATE ---------- */

    public function test_update_gagal_tanpa_login()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $pengumuman = Pengumuman::create([
            'id_user' => $admin->id, 'judul_pengumuman' => 'Judul', 'isi_pengumuman' => 'Isi',
            'tanggal' => '2026-07-01', 'kategori' => 'Info',
        ]);

        $response = $this->putJson('/api/pengumuman/' . $pengumuman->id_pengumuman, [
            'judul_pengumuman' => 'Judul Baru',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_gagal_jika_kategori_tidak_valid()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);

        $pengumuman = Pengumuman::create([
            'id_user' => $admin->id, 'judul_pengumuman' => 'Judul', 'isi_pengumuman' => 'Isi',
            'tanggal' => '2026-07-01', 'kategori' => 'Info',
        ]);

        $response = $this->putJson('/api/pengumuman/' . $pengumuman->id_pengumuman, [
            'kategori' => 'Random',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['kategori']);
    }

    public function test_update_berhasil_dengan_sebagian_field_saja()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);

        $pengumuman = Pengumuman::create([
            'id_user' => $admin->id, 'judul_pengumuman' => 'Judul Lama', 'isi_pengumuman' => 'Isi Lama',
            'tanggal' => '2026-07-01', 'kategori' => 'Info',
        ]);

        $response = $this->putJson('/api/pengumuman/' . $pengumuman->id_pengumuman, [
            'judul_pengumuman' => 'Judul Baru',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => [
                         'judul_pengumuman' => 'Judul Baru',
                         'isi_pengumuman'   => 'Isi Lama', // tetap, karena tidak dikirim
                     ],
                 ]);
    }

    /** ---------- DESTROY ---------- */

    public function test_destroy_gagal_tanpa_login()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $pengumuman = Pengumuman::create([
            'id_user' => $admin->id, 'judul_pengumuman' => 'Judul', 'isi_pengumuman' => 'Isi',
            'tanggal' => '2026-07-01', 'kategori' => 'Info',
        ]);

        $response = $this->deleteJson('/api/pengumuman/' . $pengumuman->id_pengumuman);

        $response->assertStatus(401);
    }

    public function test_destroy_berhasil_menghapus_pengumuman()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);

        $pengumuman = Pengumuman::create([
            'id_user' => $admin->id, 'judul_pengumuman' => 'Judul', 'isi_pengumuman' => 'Isi',
            'tanggal' => '2026-07-01', 'kategori' => 'Info',
        ]);

        $response = $this->deleteJson('/api/pengumuman/' . $pengumuman->id_pengumuman);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Data berhasil dihapus',
                 ]);

        $this->assertDatabaseMissing('pengumuman', ['id_pengumuman' => $pengumuman->id_pengumuman]);
    }
}