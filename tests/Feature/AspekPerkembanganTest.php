<?php

namespace Tests\Feature;

use App\Models\AspekPerkembangan;
use App\Models\IndikatorPenilaian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AspekPerkembanganTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);
    }

    /** ---------- INDEX ---------- */

    public function test_index_mengembalikan_semua_aspek_beserta_indikator()
    {
        $aspek = AspekPerkembangan::create([
            'nama_aspek'     => 'Nilai Agama dan Moral',
            'definisi_aspek' => 'Definisi aspek',
        ]);

        IndikatorPenilaian::create([
            'id_aspek'       => $aspek->id_aspek,
            'nama_indikator' => 'Berdoa sebelum makan',
            'nama_kegiatan'  => 'Makan bersama',
        ]);

        $response = $this->getJson('/api/aspek');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonCount(1, 'data.0.indikator');
    }

    /** ---------- STORE ---------- */

    public function test_store_gagal_jika_nama_aspek_kosong()
    {
        $response = $this->postJson('/api/aspek', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nama_aspek']);
    }

    public function test_store_berhasil_menyimpan_aspek()
    {
        $response = $this->postJson('/api/aspek', [
            'nama_aspek'     => 'Bahasa',
            'definisi_aspek' => 'Kemampuan berbahasa anak',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('aspek_perkembangan', [
            'nama_aspek' => 'Bahasa',
        ]);
    }

    public function test_store_berhasil_tanpa_definisi_aspek()
    {
        $response = $this->postJson('/api/aspek', [
            'nama_aspek' => 'Fisik Motorik',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('aspek_perkembangan', ['nama_aspek' => 'Fisik Motorik']);
    }

    /** ---------- SHOW ---------- */

    public function test_show_mengembalikan_404_jika_tidak_ditemukan()
    {
        $response = $this->getJson('/api/aspek/9999');

        $response->assertStatus(404);
    }

    public function test_show_mengembalikan_detail_aspek()
    {
        $aspek = AspekPerkembangan::create(['nama_aspek' => 'Kognitif']);

        $response = $this->getJson('/api/aspek/' . $aspek->id_aspek);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['nama_aspek' => 'Kognitif'],
                 ]);
    }

    /** ---------- UPDATE ---------- */

    public function test_update_gagal_jika_tidak_ditemukan()
    {
        $response = $this->putJson('/api/aspek/9999', ['nama_aspek' => 'Update']);

        $response->assertStatus(404);
    }

    public function test_update_berhasil_mengubah_aspek()
    {
        $aspek = AspekPerkembangan::create(['nama_aspek' => 'Sosial Emosional']);

        $response = $this->putJson('/api/aspek/' . $aspek->id_aspek, [
            'nama_aspek'     => 'Sosial Emosional Anak',
            'definisi_aspek' => 'Definisi baru',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['nama_aspek' => 'Sosial Emosional Anak'],
                 ]);

        $this->assertDatabaseHas('aspek_perkembangan', [
            'id_aspek'   => $aspek->id_aspek,
            'nama_aspek' => 'Sosial Emosional Anak',
        ]);
    }

    /** ---------- DESTROY ---------- */

    public function test_destroy_gagal_jika_tidak_ditemukan()
    {
        $response = $this->deleteJson('/api/aspek/9999');

        $response->assertStatus(404);
    }

    public function test_destroy_berhasil_menghapus_aspek()
    {
        $aspek = AspekPerkembangan::create(['nama_aspek' => 'Seni']);

        $response = $this->deleteJson('/api/aspek/' . $aspek->id_aspek);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Data berhasil dihapus',
                 ]);

        $this->assertDatabaseMissing('aspek_perkembangan', ['id_aspek' => $aspek->id_aspek]);
    }
}