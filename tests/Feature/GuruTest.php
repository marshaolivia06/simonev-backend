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
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GuruTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);
    }

    protected function buatGuru(string $userStatus = 'approved', ?string $nik = null): Guru
    {
        $user = User::factory()->create(['role' => 'guru', 'status' => $userStatus]);

        return Guru::create([
            'id_user'   => $user->id,
            'nik'       => $nik ?? (string) fake()->unique()->numerify('################'),
            'nama_guru' => 'Bu Siska',
            'no_telp'   => '081234567890',
            'email'     => $user->email,
        ]);
    }

    /** ---------- INDEX ---------- */

    public function test_index_menampilkan_guru_dengan_user_approved()
    {
        $this->buatGuru('approved');
        $this->buatGuru('pending');

        $response = $this->getJson('/api/guru');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    public function test_index_menampilkan_guru_tanpa_id_user()
    {
        Guru::create([
            'id_user'   => null,
            'nik'       => (string) fake()->unique()->numerify('################'),
            'nama_guru' => 'Guru Tanpa Akun',
            'no_telp'   => '081200000000',
        ]);

        $response = $this->getJson('/api/guru');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonFragment(['nama_guru' => 'Guru Tanpa Akun']);
    }

    /** ---------- SHOW ---------- */

    public function test_show_mengembalikan_404_jika_tidak_ditemukan()
    {
        $response = $this->getJson('/api/guru/9999');

        $response->assertStatus(404);
    }

    public function test_show_mengembalikan_detail_guru()
    {
        $guru = $this->buatGuru();

        $response = $this->getJson('/api/guru/' . $guru->id_guru);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['nama_guru' => 'Bu Siska'],
                 ]);
    }

    /** ---------- UPDATE ---------- */

    public function test_update_gagal_jika_nik_dipakai_guru_lain()
    {
        $guru1 = $this->buatGuru('approved', '1111222233334444');
        $guru2 = $this->buatGuru('approved', '5555666677778888');

        $response = $this->putJson('/api/guru/' . $guru2->id_guru, [
            'nik' => '1111222233334444', // punya guru1
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nik']);
    }

    public function test_update_berhasil_walau_nik_tidak_berubah()
    {
        $guru = $this->buatGuru('approved', '1111222233334444');

        $response = $this->putJson('/api/guru/' . $guru->id_guru, [
            'nik'       => '1111222233334444', // NIK sendiri, tidak boleh dianggap duplikat
            'nama_guru' => 'Bu Siska Update',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['nama_guru' => 'Bu Siska Update'],
                 ]);
    }

    public function test_update_berhasil_mengubah_data_guru()
    {
        $guru = $this->buatGuru();

        $response = $this->putJson('/api/guru/' . $guru->id_guru, [
            'jabatan' => 'Kepala Sekolah',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('guru', [
            'id_guru' => $guru->id_guru,
            'jabatan' => 'Kepala Sekolah',
        ]);
    }

    /** ---------- DESTROY ---------- */

    public function test_destroy_berhasil_menghapus_guru()
    {
        $guru = $this->buatGuru();

        $response = $this->deleteJson('/api/guru/' . $guru->id_guru);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Data guru berhasil dihapus.',
                 ]);

        $this->assertDatabaseMissing('guru', ['id_guru' => $guru->id_guru]);
    }

    /** ---------- DASHBOARD ---------- */

    public function test_dashboard_mengembalikan_nol_jika_guru_belum_punya_kelas()
    {
        $guruUser = User::factory()->create(['role' => 'guru', 'status' => 'approved']);
        Guru::create([
            'id_user'   => $guruUser->id,
            'nik'       => (string) fake()->unique()->numerify('################'),
            'nama_guru' => 'Bu Siska',
            'no_telp'   => '081234567890',
            'email'     => $guruUser->email,
        ]);

        Sanctum::actingAs($guruUser, ['*']);

        $response = $this->getJson('/api/dashboard-guru');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => [
                         'nama_kelas' => null,
                         'total_anak' => 0,
                         'BB' => 0, 'MB' => 0, 'BSH' => 0, 'BSB' => 0,
                         'belum_dinilai' => 0,
                     ],
                 ]);
    }

    public function test_dashboard_menghitung_skala_dan_belum_dinilai_dengan_benar()
    {
        $guruUser = User::factory()->create(['role' => 'guru', 'status' => 'approved']);
        $guru = Guru::create([
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

        $aspek = AspekPerkembangan::create([
            'nama_aspek'     => 'Nilai Agama dan Moral',
            'definisi_aspek' => 'Definisi',
        ]);
        $indikator = IndikatorPenilaian::create([
            'id_aspek'       => $aspek->id_aspek,
            'nama_indikator' => 'Berdoa',
            'nama_kegiatan'  => 'Makan bersama',
        ]);

        // Anak 1: nilai BB(1) dan BSH(3) → rata-rata 2 → MB
        $anak1 = Anak::create([
            'id_kelas'      => $kelas->id_kelas,
            'nama_anak'     => 'Budi',
            'jenis_kelamin' => 'L',
        ]);
        Observasi::create([
            'id_anak' => $anak1->id_anak, 'id_indikator' => $indikator->id_indikator,
            'id_guru' => $guru->id_guru, 'nilai' => 'BB', 'tanggal' => '2026-01-01', 'semester' => 'Ganjil',
        ]);
        Observasi::create([
            'id_anak' => $anak1->id_anak, 'id_indikator' => $indikator->id_indikator,
            'id_guru' => $guru->id_guru, 'nilai' => 'BSH', 'tanggal' => '2026-01-02', 'semester' => 'Ganjil',
        ]);

        // Anak 2: belum ada observasi sama sekali
        Anak::create([
            'id_kelas'      => $kelas->id_kelas,
            'nama_anak'     => 'Doni',
            'jenis_kelamin' => 'L',
        ]);

        Sanctum::actingAs($guruUser, ['*']);

        $response = $this->getJson('/api/dashboard-guru');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => [
                         'nama_kelas'    => 'Kelas A',
                         'total_anak'    => 2,
                         'BB' => 0, 'MB' => 1, 'BSH' => 0, 'BSB' => 0,
                         'belum_dinilai' => 1,
                     ],
                 ]);
    }

    /** ---------- ANAK BY SKALA ---------- */

    public function test_anak_by_skala_gagal_jika_skala_tidak_valid()
    {
        $guruUser = User::factory()->create(['role' => 'guru', 'status' => 'approved']);
        Guru::create([
            'id_user'   => $guruUser->id,
            'nik'       => (string) fake()->unique()->numerify('################'),
            'nama_guru' => 'Bu Siska',
            'no_telp'   => '081234567890',
            'email'     => $guruUser->email,
        ]);
        Sanctum::actingAs($guruUser, ['*']);

        $response = $this->getJson('/api/dashboard-guru/anak-by-skala?skala=X');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['skala']);
    }

    public function test_anak_by_skala_mengembalikan_anak_sesuai_skala()
    {
        $guruUser = User::factory()->create(['role' => 'guru', 'status' => 'approved']);
        $guru = Guru::create([
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

        $aspek = AspekPerkembangan::create(['nama_aspek' => 'NAM', 'definisi_aspek' => 'Definisi']);
        $indikator = IndikatorPenilaian::create([
            'id_aspek' => $aspek->id_aspek, 'nama_indikator' => 'Berdoa', 'nama_kegiatan' => 'Makan',
        ]);

        $anak = Anak::create([
            'id_kelas'      => $kelas->id_kelas,
            'nama_anak'     => 'Budi',
            'jenis_kelamin' => 'L',
        ]);
        Observasi::create([
            'id_anak' => $anak->id_anak, 'id_indikator' => $indikator->id_indikator,
            'id_guru' => $guru->id_guru, 'nilai' => 'BSB', 'tanggal' => '2026-01-01', 'semester' => 'Ganjil',
        ]);

        Sanctum::actingAs($guruUser, ['*']);

        $response = $this->getJson('/api/dashboard-guru/anak-by-skala?skala=BSB');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonFragment(['nama_anak' => 'Budi']);

        $responseKosong = $this->getJson('/api/dashboard-guru/anak-by-skala?skala=BB');
        $responseKosong->assertStatus(200)
                        ->assertJsonCount(0, 'data');
    }

    /** ---------- BY KELAS ---------- */

    public function test_by_kelas_mengembalikan_false_jika_kelas_tidak_ditemukan()
    {
        $response = $this->getJson('/api/guru/by-kelas/9999');

        $response->assertStatus(200)
                 ->assertJson(['success' => false, 'data' => null]);
    }

    public function test_by_kelas_mengembalikan_false_jika_kelas_tanpa_guru()
    {
        $kelas = Kelas::create([
            'id_guru'      => null,
            'nama_kelas'   => 'Kelas Tanpa Guru',
            'tahun_ajaran' => '2025/2026',
        ]);

        $response = $this->getJson('/api/guru/by-kelas/' . $kelas->id_kelas);

        $response->assertStatus(200)
                 ->assertJson(['success' => false, 'data' => null]);
    }

    public function test_by_kelas_mengembalikan_guru_yang_sesuai()
    {
        $guru = $this->buatGuru();
        $kelas = Kelas::create([
            'id_guru'      => $guru->id_guru,
            'nama_kelas'   => 'Kelas A',
            'tahun_ajaran' => '2025/2026',
        ]);

        $response = $this->getJson('/api/guru/by-kelas/' . $kelas->id_kelas);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['nama_guru' => 'Bu Siska'],
                 ]);
    }
}