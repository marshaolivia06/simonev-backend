<?php

namespace Tests\Feature;

use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfilSekolahTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Sanctum::actingAs($admin, ['*']);
    }

    /** ---------- SHOW ---------- */

    public function test_show_mengembalikan_null_jika_belum_ada_profil()
    {
        $response = $this->getJson('/api/profil-sekolah');

        $response->assertStatus(200)
                 ->assertJson(['success' => true, 'data' => null]);
    }

    public function test_show_mengembalikan_profil_yang_sudah_ada()
    {
        ProfilSekolah::create(['nama_sekolah' => 'TK Al-Muhajirin Dotamana']);

        $response = $this->getJson('/api/profil-sekolah');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['nama_sekolah' => 'TK Al-Muhajirin Dotamana'],
                 ]);
    }

    /** ---------- UPDATE ---------- */

    public function test_update_gagal_jika_nama_sekolah_kosong()
    {
        $response = $this->putJson('/api/profil-sekolah', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nama_sekolah']);
    }

    public function test_update_gagal_jika_email_tidak_valid()
    {
        $response = $this->putJson('/api/profil-sekolah', [
            'nama_sekolah' => 'TK Al-Muhajirin Dotamana',
            'email'        => 'bukan-email',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_update_membuat_profil_baru_jika_belum_ada()
    {
        $response = $this->putJson('/api/profil-sekolah', [
            'nama_sekolah'        => 'TK Al-Muhajirin Dotamana',
            'email'               => 'tk@example.com',
            'nama_kepala_sekolah' => 'Bu Siska',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Profil sekolah berhasil diperbarui',
                 ]);

        $this->assertDatabaseCount('profil_sekolah', 1);
        $this->assertDatabaseHas('profil_sekolah', [
            'nama_sekolah' => 'TK Al-Muhajirin Dotamana',
            'email'        => 'tk@example.com',
        ]);
    }

    public function test_update_memperbarui_profil_yang_sudah_ada_tanpa_duplikat()
    {
        ProfilSekolah::create(['nama_sekolah' => 'TK Lama']);

        $response = $this->putJson('/api/profil-sekolah', [
            'nama_sekolah' => 'TK Al-Muhajirin Dotamana',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => ['nama_sekolah' => 'TK Al-Muhajirin Dotamana'],
                 ]);

        $this->assertDatabaseCount('profil_sekolah', 1);
    }

    public function test_update_berhasil_upload_foto_ttd()
    {
        Storage::fake('public');

        $foto = UploadedFile::fake()->image('ttd.jpg');

        $response = $this->putJson('/api/profil-sekolah', [
            'nama_sekolah' => 'TK Al-Muhajirin Dotamana',
            'foto_ttd_ks'  => $foto,
        ]);

        $response->assertStatus(200);

        $profil = ProfilSekolah::first();
        $this->assertNotNull($profil->foto_ttd_ks);
        Storage::disk('public')->assertExists($profil->foto_ttd_ks);
    }

    public function test_update_menghapus_foto_lama_saat_upload_foto_baru()
    {
        Storage::fake('public');

        $fotoLama = UploadedFile::fake()->image('lama.jpg')->store('ttd', 'public');
        ProfilSekolah::create([
            'nama_sekolah' => 'TK Al-Muhajirin Dotamana',
            'foto_ttd_ks'  => $fotoLama,
        ]);

        $fotoBaru = UploadedFile::fake()->image('baru.jpg');

        $response = $this->putJson('/api/profil-sekolah', [
            'nama_sekolah' => 'TK Al-Muhajirin Dotamana',
            'foto_ttd_ks'  => $fotoBaru,
        ]);

        $response->assertStatus(200);

        Storage::disk('public')->assertMissing($fotoLama);

        $profil = ProfilSekolah::first();
        Storage::disk('public')->assertExists($profil->foto_ttd_ks);
    }
}