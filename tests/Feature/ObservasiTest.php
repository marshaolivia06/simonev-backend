<?php

namespace Tests\Feature;

use App\Models\Anak;
use App\Models\AspekPerkembangan;
use App\Models\Guru;
use App\Models\IndikatorPenilaian;
use App\Models\Kelas;
use App\Models\Observasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ObservasiTest extends TestCase
{
    use RefreshDatabase;

    protected Guru $guru;
    protected Kelas $kelas;
    protected Anak $anak;
    protected AspekPerkembangan $aspek;
    protected IndikatorPenilaian $indikator;

    protected function setUp(): void
    {
        parent::setUp();

        // User + Guru yang akan login di setiap test
        $user = User::factory()->create(['role' => 'guru']);

        $this->guru = Guru::create([
            'id_user'   => $user->id,
            'nik'       => '1234567890123456',
            'nama_guru' => 'Bu Siska',
            'no_telp'   => '081234567890',
            'email'     => $user->email,
        ]);

        // Login sebagai guru ini di semua test (auth:sanctum)
        Sanctum::actingAs($user, ['*']);

        $this->kelas = Kelas::create([
            'id_guru'      => $this->guru->id_guru,
            'nama_kelas'   => 'Kelas A',
            'tahun_ajaran' => '2025/2026',
        ]);

        $this->anak = Anak::create([
            'id_kelas'      => $this->kelas->id_kelas,
            'nama_anak'     => 'Budi',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2020-01-01',
        ]);

        $this->aspek = AspekPerkembangan::create([
            'nama_aspek'     => 'Nilai Agama dan Moral',
            'definisi_aspek' => 'Aspek perkembangan nilai agama dan moral anak',
        ]);

        $this->indikator = IndikatorPenilaian::create([
            'id_aspek'       => $this->aspek->id_aspek,
            'nama_indikator' => 'Berdoa sebelum makan',
            'nama_kegiatan'  => 'Kegiatan makan bersama',
        ]);
    }

    /** ---------- INDEX ---------- */

    public function test_index_mengembalikan_semua_observasi()
    {
        Observasi::create([
            'id_anak'      => $this->anak->id_anak,
            'id_indikator' => $this->indikator->id_indikator,
            'id_guru'      => $this->guru->id_guru,
            'nilai'        => 'BSH',
            'tanggal'      => '2026-01-10',
            'semester'     => 'Ganjil',
        ]);

        $response = $this->getJson('/api/observasi');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonCount(1, 'data');
    }

    public function test_index_bisa_difilter_berdasarkan_id_anak()
    {
        $anakLain = Anak::create([
            'id_kelas'      => $this->kelas->id_kelas,
            'nama_anak'     => 'Ani',
            'jenis_kelamin' => 'P',
            'tanggal_lahir' => '2020-02-02',
        ]);

        Observasi::create([
            'id_anak'      => $this->anak->id_anak,
            'id_indikator' => $this->indikator->id_indikator,
            'id_guru'      => $this->guru->id_guru,
            'nilai'        => 'BSH',
            'tanggal'      => '2026-01-10',
            'semester'     => 'Ganjil',
        ]);

        Observasi::create([
            'id_anak'      => $anakLain->id_anak,
            'id_indikator' => $this->indikator->id_indikator,
            'id_guru'      => $this->guru->id_guru,
            'nilai'        => 'MB',
            'tanggal'      => '2026-01-11',
            'semester'     => 'Ganjil',
        ]);

        $response = $this->getJson('/api/observasi?id_anak=' . $this->anak->id_anak);

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    public function test_index_bisa_difilter_berdasarkan_semester()
    {
        Observasi::create([
            'id_anak'      => $this->anak->id_anak,
            'id_indikator' => $this->indikator->id_indikator,
            'id_guru'      => $this->guru->id_guru,
            'nilai'        => 'BSH',
            'tanggal'      => '2026-01-10',
            'semester'     => 'Ganjil',
        ]);

        Observasi::create([
            'id_anak'      => $this->anak->id_anak,
            'id_indikator' => $this->indikator->id_indikator,
            'id_guru'      => $this->guru->id_guru,
            'nilai'        => 'BSB',
            'tanggal'      => '2026-06-10',
            'semester'     => 'Genap',
        ]);

        $response = $this->getJson('/api/observasi?semester=Genap');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    /** ---------- STORE (single) ---------- */

    public function test_store_gagal_jika_field_wajib_kosong()
    {
        $response = $this->postJson('/api/observasi', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['id_anak', 'id_indikator', 'nilai', 'tanggal', 'semester']);
    }

    public function test_store_gagal_jika_nilai_bukan_pilihan_valid()
    {
        $response = $this->postJson('/api/observasi', [
            'id_anak'      => $this->anak->id_anak,
            'id_indikator' => $this->indikator->id_indikator,
            'nilai'        => 'X', // tidak valid
            'tanggal'      => '2026-01-10',
            'semester'     => 'Ganjil',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nilai']);
    }

    public function test_store_gagal_jika_id_anak_tidak_ada()
    {
        $response = $this->postJson('/api/observasi', [
            'id_anak'      => 9999, // tidak ada di DB
            'id_indikator' => $this->indikator->id_indikator,
            'nilai'        => 'BSH',
            'tanggal'      => '2026-01-10',
            'semester'     => 'Ganjil',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['id_anak']);
    }

    public function test_store_berhasil_menyimpan_observasi()
    {
        $response = $this->postJson('/api/observasi', [
            'id_anak'      => $this->anak->id_anak,
            'id_indikator' => $this->indikator->id_indikator,
            'nilai'        => 'BSH',
            'komentar'     => 'Sudah cukup baik',
            'tanggal'      => '2026-01-10',
            'semester'     => 'Ganjil',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('observasi', [
            'id_anak'      => $this->anak->id_anak,
            'id_indikator' => $this->indikator->id_indikator,
            'id_guru'      => $this->guru->id_guru,
            'nilai'        => 'BSH',
        ]);
    }

    public function test_store_berhasil_dengan_upload_foto()
    {
        Storage::fake('public');

        $foto = UploadedFile::fake()->image('observasi.jpg');

        $response = $this->postJson('/api/observasi', [
            'id_anak'      => $this->anak->id_anak,
            'id_indikator' => $this->indikator->id_indikator,
            'nilai'        => 'BSH',
            'tanggal'      => '2026-01-10',
            'semester'     => 'Ganjil',
            'foto'         => $foto,
        ]);

        $response->assertStatus(201);

        $observasi = Observasi::first();
        $this->assertNotNull($observasi->foto);
        Storage::disk('public')->assertExists($observasi->foto);
    }

    /** ---------- STORE BATCH ---------- */

    public function test_store_batch_gagal_jika_penilaian_kosong()
    {
        $response = $this->postJson('/api/observasi/batch', [
            'id_anak'   => $this->anak->id_anak,
            'semester'  => 'Ganjil',
            'tanggal'   => '2026-01-10',
            'penilaian' => [],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['penilaian']);
    }

    public function test_store_batch_gagal_jika_nilai_salah_satu_item_tidak_valid()
    {
        $response = $this->postJson('/api/observasi/batch', [
            'id_anak'   => $this->anak->id_anak,
            'semester'  => 'Ganjil',
            'tanggal'   => '2026-01-10',
            'penilaian' => [
                ['id_indikator' => $this->indikator->id_indikator, 'nilai' => 'BSH'],
                ['id_indikator' => $this->indikator->id_indikator, 'nilai' => 'SALAH'],
            ],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['penilaian.1.nilai']);
    }

    public function test_store_batch_berhasil_menyimpan_beberapa_observasi()
    {
        $indikator2 = IndikatorPenilaian::create([
            'id_aspek'       => $this->aspek->id_aspek,
            'nama_indikator' => 'Mengucap salam',
            'nama_kegiatan'  => 'Kegiatan pembuka',
        ]);

        $response = $this->postJson('/api/observasi/batch', [
            'id_anak'   => $this->anak->id_anak,
            'semester'  => 'Ganjil',
            'tanggal'   => '2026-01-10',
            'komentar'  => 'Perkembangan baik secara umum',
            'penilaian' => [
                ['id_indikator' => $this->indikator->id_indikator, 'nilai' => 'BSH'],
                ['id_indikator' => $indikator2->id_indikator, 'nilai' => 'MB'],
            ],
        ]);

        $response->assertStatus(201)
                 ->assertJson(['success' => true, 'message' => '2 observasi berhasil disimpan.']);

        $this->assertDatabaseCount('observasi', 2);
        $this->assertDatabaseHas('observasi', [
            'id_anak'      => $this->anak->id_anak,
            'id_indikator' => $this->indikator->id_indikator,
            'nilai'        => 'BSH',
            'komentar'     => 'Perkembangan baik secara umum',
        ]);
        $this->assertDatabaseHas('observasi', [
            'id_anak'      => $this->anak->id_anak,
            'id_indikator' => $indikator2->id_indikator,
            'nilai'        => 'MB',
        ]);
    }

    public function test_store_batch_menyimpan_keterangan_aspek()
    {
        $response = $this->postJson('/api/observasi/batch', [
            'id_anak'          => $this->anak->id_anak,
            'semester'         => 'Ganjil',
            'tanggal'          => '2026-01-10',
            'keterangan_aspek' => [(string) $this->aspek->id_aspek => 'Anak sudah mulai berani berdoa sendiri'],
            'penilaian'        => [
                ['id_indikator' => $this->indikator->id_indikator, 'nilai' => 'BSH'],
            ],
        ]);

        $response->assertStatus(201);

        $observasi = Observasi::first();
        $this->assertEquals(
            'Anak sudah mulai berani berdoa sendiri',
            $observasi->keterangan_aspek[(string) $this->aspek->id_aspek]
        );
    }

    /** ---------- BY ANAK ---------- */

    public function test_by_anak_mengembalikan_data_kosong_jika_belum_ada_observasi()
    {
        $response = $this->getJson('/api/observasi/anak/' . $this->anak->id_anak);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => [
                         'rekap_aspek' => [],
                         'riwayat'     => [],
                         'komentar'    => '',
                         'total'       => 0,
                     ],
                 ]);
    }

    public function test_by_anak_mengembalikan_rekap_dan_riwayat()
    {
        Observasi::create([
            'id_anak'      => $this->anak->id_anak,
            'id_indikator' => $this->indikator->id_indikator,
            'id_guru'      => $this->guru->id_guru,
            'nilai'        => 'BSH',
            'komentar'     => 'Anak menunjukkan kemajuan',
            'tanggal'      => '2026-01-10',
            'semester'     => 'Ganjil',
        ]);

        $response = $this->getJson('/api/observasi/anak/' . $this->anak->id_anak);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'komentar' => 'Anak menunjukkan kemajuan',
                         'total'    => 1,
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertCount(1, $data['rekap_aspek']);
        $this->assertEquals('Nilai Agama dan Moral', $data['rekap_aspek'][0]['aspek']);
    }

    public function test_by_anak_bisa_difilter_semester()
    {
        Observasi::create([
            'id_anak'      => $this->anak->id_anak,
            'id_indikator' => $this->indikator->id_indikator,
            'id_guru'      => $this->guru->id_guru,
            'nilai'        => 'BSH',
            'tanggal'      => '2026-01-10',
            'semester'     => 'Ganjil',
        ]);

        Observasi::create([
            'id_anak'      => $this->anak->id_anak,
            'id_indikator' => $this->indikator->id_indikator,
            'id_guru'      => $this->guru->id_guru,
            'nilai'        => 'BSB',
            'tanggal'      => '2026-06-10',
            'semester'     => 'Genap',
        ]);

        $response = $this->getJson('/api/observasi/anak/' . $this->anak->id_anak . '?semester=Genap');

        $response->assertStatus(200)
                 ->assertJson(['data' => ['total' => 1]]);
    }

    /** ---------- SHOW ---------- */

    public function test_show_mengembalikan_404_jika_tidak_ditemukan()
    {
        $response = $this->getJson('/api/observasi/9999');

        $response->assertStatus(404);
    }

    public function test_show_mengembalikan_detail_observasi()
    {
        $observasi = Observasi::create([
            'id_anak'      => $this->anak->id_anak,
            'id_indikator' => $this->indikator->id_indikator,
            'id_guru'      => $this->guru->id_guru,
            'nilai'        => 'BSH',
            'tanggal'      => '2026-01-10',
            'semester'     => 'Ganjil',
        ]);

        $response = $this->getJson('/api/observasi/' . $observasi->id_observasi);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['nilai' => 'BSH'],
                 ]);
    }

    /** ---------- UPDATE ---------- */

    public function test_update_gagal_jika_nilai_tidak_valid()
    {
        $observasi = Observasi::create([
            'id_anak'      => $this->anak->id_anak,
            'id_indikator' => $this->indikator->id_indikator,
            'id_guru'      => $this->guru->id_guru,
            'nilai'        => 'BSH',
            'tanggal'      => '2026-01-10',
            'semester'     => 'Ganjil',
        ]);

        $response = $this->putJson('/api/observasi/' . $observasi->id_observasi, [
            'nilai' => 'SALAH',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nilai']);
    }

    public function test_update_berhasil_mengubah_nilai_dan_komentar()
    {
        $observasi = Observasi::create([
            'id_anak'      => $this->anak->id_anak,
            'id_indikator' => $this->indikator->id_indikator,
            'id_guru'      => $this->guru->id_guru,
            'nilai'        => 'BB',
            'tanggal'      => '2026-01-10',
            'semester'     => 'Ganjil',
        ]);

        $response = $this->putJson('/api/observasi/' . $observasi->id_observasi, [
            'nilai'    => 'BSB',
            'komentar' => 'Perkembangan pesat',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['nilai' => 'BSB', 'komentar' => 'Perkembangan pesat'],
                 ]);

        $this->assertDatabaseHas('observasi', [
            'id_observasi' => $observasi->id_observasi,
            'nilai'        => 'BSB',
            'komentar'     => 'Perkembangan pesat',
        ]);
    }

    /** ---------- DESTROY ---------- */

    public function test_destroy_gagal_jika_tidak_ditemukan()
    {
        $response = $this->deleteJson('/api/observasi/9999');

        $response->assertStatus(404);
    }

    public function test_destroy_berhasil_menghapus_observasi()
    {
        $observasi = Observasi::create([
            'id_anak'      => $this->anak->id_anak,
            'id_indikator' => $this->indikator->id_indikator,
            'id_guru'      => $this->guru->id_guru,
            'nilai'        => 'BSH',
            'tanggal'      => '2026-01-10',
            'semester'     => 'Ganjil',
        ]);

        $response = $this->deleteJson('/api/observasi/' . $observasi->id_observasi);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('observasi', ['id_observasi' => $observasi->id_observasi]);
    }
}