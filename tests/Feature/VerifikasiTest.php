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

class VerifikasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Login sebagai admin untuk semua request (endpoint verifikasi hanya untuk admin)
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);
    }

    /** ---------- INDEX ---------- */

    public function test_index_mengembalikan_user_non_admin_saja()
    {
        User::factory()->create(['role' => 'guru', 'status' => 'pending']);
        User::factory()->create(['role' => 'orang_tua', 'status' => 'approved']);
        // admin dari setUp() tidak boleh ikut muncul

        $response = $this->getJson('/api/verifikasi');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    public function test_index_menyertakan_detail_guru_atau_orangtua()
    {
        $userGuru = User::factory()->create(['role' => 'guru', 'status' => 'pending']);
        Guru::create([
            'id_user'   => $userGuru->id,
            'nik'       => '1234567890123456',
            'nama_guru' => 'Bu Siska',
            'no_telp'   => '081234567890',
            'email'     => $userGuru->email,
        ]);

        $response = $this->getJson('/api/verifikasi');

        $response->assertStatus(200);
        $data = collect($response->json('data'));
        $entry = $data->firstWhere('id', $userGuru->id);

        $this->assertNotNull($entry['detail']);
        $this->assertEquals('Bu Siska', $entry['detail']['nama_guru']);
    }

    /** ---------- ACCEPT ---------- */

    public function test_accept_gagal_jika_user_tidak_ditemukan()
    {
        $response = $this->postJson('/api/verifikasi/9999/accept');

        $response->assertStatus(404);
    }

    public function test_accept_gagal_jika_status_bukan_pending()
    {
        $user = User::factory()->create(['role' => 'guru', 'status' => 'approved']);

        $response = $this->postJson("/api/verifikasi/{$user->id}/accept");

        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Akun ini sudah diproses sebelumnya.',
                 ]);
    }

    public function test_accept_berhasil_untuk_guru()
    {
        $user = User::factory()->create(['role' => 'guru', 'status' => 'pending']);

        $response = $this->postJson("/api/verifikasi/{$user->id}/accept");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => "Akun {$user->username} berhasil diverifikasi.",
                 ]);

        $this->assertDatabaseHas('users', [
            'id'     => $user->id,
            'status' => 'approved',
        ]);
    }

    public function test_accept_berhasil_untuk_orang_tua_dan_membuat_data_anak_jika_kelas_cocok()
    {
        $guruUser = User::factory()->create(['role' => 'guru', 'status' => 'approved']);
        $guru = Guru::create([
            'id_user'   => $guruUser->id,
            'nik'       => '1234567890123456',
            'nama_guru' => 'Bu Siska',
            'no_telp'   => '081234567890',
            'email'     => $guruUser->email,
        ]);

        $kelas = Kelas::create([
            'id_guru'      => $guru->id_guru,
            'nama_kelas'   => 'Kelas A',
            'tahun_ajaran' => '2025/2026',
        ]);

        $ortuUser = User::factory()->create(['role' => 'orang_tua', 'status' => 'pending']);
        $orangTua = OrangTua::create([
            'id_user'            => $ortuUser->id,
            'nik'                => '9876543210123456',
            'nama_orangtua'      => 'Ibu Ani',
            'no_telp'            => '081298765432',
            'nama_anak'          => 'Budi',
            'kelas_anak'         => 'Kelas A',
            'tanggal_lahir_anak' => '2020-01-01',
            'jenis_kelamin_anak' => 'L',
        ]);

        $response = $this->postJson("/api/verifikasi/{$ortuUser->id}/accept");

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'id'     => $ortuUser->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('anak', [
            'id_kelas'      => $kelas->id_kelas,
            'id_orangtua'   => $orangTua->id_orangtua,
            'nama_anak'     => 'Budi',
            'jenis_kelamin' => 'L',
        ]);
    }

    public function test_accept_untuk_orang_tua_tidak_membuat_anak_jika_kelas_tidak_cocok()
    {
        $ortuUser = User::factory()->create(['role' => 'orang_tua', 'status' => 'pending']);
        OrangTua::create([
            'id_user'            => $ortuUser->id,
            'nik'                => '9876543210123456',
            'nama_orangtua'      => 'Ibu Ani',
            'no_telp'            => '081298765432',
            'nama_anak'          => 'Budi',
            'kelas_anak'         => 'Kelas Yang Tidak Ada',
            'tanggal_lahir_anak' => '2020-01-01',
            'jenis_kelamin_anak' => 'L',
        ]);

        $response = $this->postJson("/api/verifikasi/{$ortuUser->id}/accept");

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'id'     => $ortuUser->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseCount('anak', 0);
    }

    /** ---------- REJECT ---------- */

    public function test_reject_gagal_jika_user_tidak_ditemukan()
    {
        $response = $this->postJson('/api/verifikasi/9999/reject');

        $response->assertStatus(404);
    }

    public function test_reject_gagal_jika_status_bukan_pending()
    {
        $user = User::factory()->create(['role' => 'guru', 'status' => 'rejected']);

        $response = $this->postJson("/api/verifikasi/{$user->id}/reject");

        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Akun ini sudah diproses sebelumnya.',
                 ]);
    }

    public function test_reject_gagal_jika_alasan_melebihi_500_karakter()
    {
        $user = User::factory()->create(['role' => 'guru', 'status' => 'pending']);

        $response = $this->postJson("/api/verifikasi/{$user->id}/reject", [
            'alasan' => str_repeat('a', 501),
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['alasan']);
    }

    public function test_reject_berhasil_tanpa_alasan()
    {
        $user = User::factory()->create(['role' => 'guru', 'status' => 'pending']);

        $response = $this->postJson("/api/verifikasi/{$user->id}/reject");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => "Akun {$user->username} berhasil ditolak.",
                 ]);

        $this->assertDatabaseHas('users', [
            'id'     => $user->id,
            'status' => 'rejected',
        ]);
    }

    public function test_reject_berhasil_dengan_alasan()
    {
        $user = User::factory()->create(['role' => 'guru', 'status' => 'pending']);

        $response = $this->postJson("/api/verifikasi/{$user->id}/reject", [
            'alasan' => 'Data NIK tidak valid',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'id'     => $user->id,
            'status' => 'rejected',
        ]);
    }

    /** ---------- DESTROY ---------- */

    public function test_destroy_gagal_jika_user_tidak_ditemukan()
    {
        $response = $this->deleteJson('/api/verifikasi/9999');

        $response->assertStatus(404);
    }

    public function test_destroy_berhasil_menghapus_user()
    {
        $user = User::factory()->create(['role' => 'guru', 'status' => 'pending']);

        $response = $this->deleteJson("/api/verifikasi/{$user->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Akun berhasil dihapus.',
                 ]);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}