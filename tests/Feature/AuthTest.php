<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** ---------- REGISTER ---------- */

    public function test_register_gagal_jika_field_wajib_kosong()
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'username', 'email', 'password', 'role', 'nama', 'nik', 'no_telp',
                 ]);
    }

    public function test_register_gagal_jika_nik_bukan_16_digit()
    {
        $response = $this->postJson('/api/register', [
            'username' => 'guru01',
            'email'    => 'guru01@example.com',
            'password' => 'password123',
            'role'     => 'guru',
            'nama'     => 'Bu Siska',
            'nik'      => '12345', // kurang dari 16
            'no_telp'  => '081234567890',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nik']);
    }

    public function test_register_gagal_jika_role_tidak_valid()
    {
        $response = $this->postJson('/api/register', [
            'username' => 'user01',
            'email'    => 'user01@example.com',
            'password' => 'password123',
            'role'     => 'admin', // tidak diizinkan, hanya guru/orang_tua
            'nama'     => 'Test User',
            'nik'      => '1234567890123456',
            'no_telp'  => '081234567890',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['role']);
    }

    public function test_register_berhasil_sebagai_guru()
    {
        $response = $this->postJson('/api/register', [
            'username'      => 'guru01',
            'email'         => 'guru01@example.com',
            'password'      => 'password123',
            'role'          => 'guru',
            'nama'          => 'Bu Siska',
            'nik'           => '1234567890123456',
            'no_telp'       => '081234567890',
            'jenis_kelamin' => 'P',
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Pendaftaran berhasil, menunggu verifikasi admin',
                 ]);

        $this->assertDatabaseHas('users', [
            'username' => 'guru01',
            'email'    => 'guru01@example.com',
            'status'   => 'pending',
        ]);

        $this->assertDatabaseHas('guru', [
            'nama_guru' => 'Bu Siska',
            'nik'       => '1234567890123456',
        ]);
    }

    public function test_register_berhasil_sebagai_orang_tua()
    {
        $response = $this->postJson('/api/register', [
            'username' => 'ortu01',
            'email'    => 'ortu01@example.com',
            'password' => 'password123',
            'role'     => 'orang_tua',
            'nama'     => 'Ibu Ani',
            'nik'      => '9876543210123456',
            'no_telp'  => '081298765432',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orang_tua', [
            'nama_orangtua' => 'Ibu Ani',
            'nik'           => '9876543210123456',
        ]);
    }

    public function test_register_gagal_jika_username_atau_email_sudah_dipakai()
    {
        User::create([
            'username' => 'guru01',
            'email'    => 'guru01@example.com',
            'password' => Hash::make('password123'),
            'role'     => 'guru',
            'status'   => 'pending',
        ]);

        $response = $this->postJson('/api/register', [
            'username' => 'guru01', // duplikat
            'email'    => 'lain@example.com',
            'password' => 'password123',
            'role'     => 'guru',
            'nama'     => 'Guru Lain',
            'nik'      => '1111222233334444',
            'no_telp'  => '081200000000',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['username']);
    }

    /** ---------- LOGIN ---------- */

    public function test_login_gagal_jika_username_tidak_ditemukan()
    {
        $response = $this->postJson('/api/login', [
            'username' => 'tidakada',
            'password' => 'apapun123',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Username atau password salah',
                 ]);
    }

    public function test_login_gagal_jika_password_salah()
    {
        User::factory()->create([
            'username' => 'guru01',
            'password' => Hash::make('passwordbenar'),
            'status'   => 'approved',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'guru01',
            'password' => 'passwordsalah',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_gagal_jika_akun_masih_pending()
    {
        User::factory()->create([
            'username' => 'guru01',
            'password' => Hash::make('password123'),
            'status'   => 'pending',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'guru01',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Akun kamu masih menunggu verifikasi admin.',
                 ]);
    }

    public function test_login_gagal_jika_akun_ditolak()
    {
        User::factory()->create([
            'username' => 'guru02',
            'password' => Hash::make('password123'),
            'status'   => 'rejected',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'guru02',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Akun kamu ditolak oleh admin.',
                 ]);
    }

    public function test_login_berhasil_jika_status_approved()
    {
        User::factory()->create([
            'username' => 'guru01',
            'password' => Hash::make('password123'),
            'status'   => 'approved',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'guru01',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Login berhasil',
                 ])
                 ->assertJsonStructure(['token', 'user']);
    }

    /** ---------- LOGOUT ---------- */

    public function test_logout_gagal_tanpa_token()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    public function test_logout_berhasil_dengan_token_valid()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Logout berhasil',
                 ]);
    }

    /** ---------- PROFILE ---------- */

    public function test_profile_gagal_tanpa_token()
    {
        $response = $this->getJson('/api/profil');

        $response->assertStatus(401);
    }

    public function test_profile_berhasil_dengan_token_valid()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/profil');

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }
}