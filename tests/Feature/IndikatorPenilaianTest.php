<?php

namespace Tests\Feature;

use App\Models\AspekPerkembangan;
use App\Models\IndikatorPenilaian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IndikatorPenilaianTest extends TestCase
{
    use RefreshDatabase;

    protected AspekPerkembangan $aspek;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);

        $this->aspek = AspekPerkembangan::create(['nama_aspek' => 'Nilai Agama dan Moral']);
    }

    /** ---------- INDEX ---------- */

    public function test_index_mengembalikan_semua_indikator_beserta_aspek()
    {
        IndikatorPenilaian::create([
            'id_aspek'       => $this->aspek->id_aspek,
            'nama_indikator' => 'Berdoa sebelum makan',
            'nama_kegiatan'  => 'Makan bersama',
        ]);

        $response = $this->getJson('/api/indikator');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonFragment(['nama_aspek' => 'Nilai Agama dan Moral']);
    }

    /** ---------- STORE ---------- */

    public function test_store_gagal_jika_field_wajib_kosong()
    {
        $response = $this->postJson('/api/indikator', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['id_aspek', 'nama_indikator']);
    }

    public function test_store_gagal_jika_id_aspek_tidak_ada()
    {
        $response = $this->postJson('/api/indikator', [
            'id_aspek'       => 9999,
            'nama_indikator' => 'Berdoa sebelum makan',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['id_aspek']);
    }

    public function test_store_berhasil_menyimpan_indikator()
    {
        $response = $this->postJson('/api/indikator', [
            'id_aspek'       => $this->aspek->id_aspek,
            'nama_indikator' => 'Mengucap salam',
            'nama_kegiatan'  => 'Kegiatan pembuka',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('indikator_penilaian', [
            'nama_indikator' => 'Mengucap salam',
            'id_aspek'       => $this->aspek->id_aspek,
        ]);
    }

    /** ---------- SHOW ---------- */

    public function test_show_mengembalikan_404_jika_tidak_ditemukan()
    {
        $response = $this->getJson('/api/indikator/9999');

        $response->assertStatus(404);
    }

    public function test_show_mengembalikan_detail_indikator()
    {
        $indikator = IndikatorPenilaian::create([
            'id_aspek'       => $this->aspek->id_aspek,
            'nama_indikator' => 'Berdoa sebelum makan',
        ]);

        $response = $this->getJson('/api/indikator/' . $indikator->id_indikator);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['nama_indikator' => 'Berdoa sebelum makan'],
                 ]);
    }

    /** ---------- UPDATE ---------- */

    public function test_update_gagal_jika_tidak_ditemukan()
    {
        $response = $this->putJson('/api/indikator/9999', ['nama_indikator' => 'Update']);

        $response->assertStatus(404);
    }

    public function test_update_berhasil_mengubah_indikator()
    {
        $indikator = IndikatorPenilaian::create([
            'id_aspek'       => $this->aspek->id_aspek,
            'nama_indikator' => 'Berdoa sebelum makan',
        ]);

        $response = $this->putJson('/api/indikator/' . $indikator->id_indikator, [
            'nama_indikator' => 'Berdoa sebelum dan sesudah makan',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['nama_indikator' => 'Berdoa sebelum dan sesudah makan'],
                 ]);

        $this->assertDatabaseHas('indikator_penilaian', [
            'id_indikator'   => $indikator->id_indikator,
            'nama_indikator' => 'Berdoa sebelum dan sesudah makan',
        ]);
    }

    /** ---------- DESTROY ---------- */

    public function test_destroy_gagal_jika_tidak_ditemukan()
    {
        $response = $this->deleteJson('/api/indikator/9999');

        $response->assertStatus(404);
    }

    public function test_destroy_berhasil_menghapus_indikator()
    {
        $indikator = IndikatorPenilaian::create([
            'id_aspek'       => $this->aspek->id_aspek,
            'nama_indikator' => 'Berdoa sebelum makan',
        ]);

        $response = $this->deleteJson('/api/indikator/' . $indikator->id_indikator);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Data berhasil dihapus',
                 ]);

        $this->assertDatabaseMissing('indikator_penilaian', ['id_indikator' => $indikator->id_indikator]);
    }
}