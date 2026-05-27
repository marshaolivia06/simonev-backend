<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\AnakController;
use App\Http\Controllers\OrangTuaController;
use App\Http\Controllers\VerifikasiController;
use App\Http\Controllers\ProfilSekolahController;
use App\Http\Controllers\AspekPerkembanganController;
use App\Http\Controllers\IndikatorPenilaianController;
use App\Http\Controllers\ObservasiController;
use App\Http\Controllers\PengumumanController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::get('/pengumuman',       [PengumumanController::class, 'index']);
Route::get('/pengumuman/{id}',  [PengumumanController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::get('/profil',   [AuthController::class, 'profile']);
    Route::put('/profil',   [AuthController::class, 'updateProfile']);

    // Guru
    Route::get('/dashboard-guru', [GuruController::class, 'dashboard']);
    Route::get('/guru',           [GuruController::class, 'index']);
    Route::get('/guru/{id}',      [GuruController::class, 'show']);
    Route::put('/guru/{guru}',    [GuruController::class, 'update']);
    Route::delete('/guru/{guru}', [GuruController::class, 'destroy']);

    // Kelas
    Route::get('/kelas', [KelasController::class, 'index']);
    Route::get('/kelas/{id}',      [KelasController::class, 'show']);
    Route::post('/kelas',          [KelasController::class, 'store']);
    Route::put('/kelas/{id}',      [KelasController::class, 'update']);
    Route::delete('/kelas/{id}',   [KelasController::class, 'destroy']);

    // Anak
    Route::get('/anak',         [AnakController::class, 'index']);
    Route::post('/anak',        [AnakController::class, 'store']);
    Route::get('/anak/{id}',    [AnakController::class, 'show']);
    Route::put('/anak/{id}',    [AnakController::class, 'update']);
    Route::delete('/anak/{id}', [AnakController::class, 'destroy']);

    // Orang Tua
    Route::get('/orang-tua',         [OrangTuaController::class, 'index']);
    Route::get('/orang-tua/{id}',    [OrangTuaController::class, 'show']);
    Route::put('/orang-tua/{id}',    [OrangTuaController::class, 'update']);
    Route::delete('/orang-tua/{id}', [OrangTuaController::class, 'destroy']);

    // Profil Sekolah
    Route::get('/profil-sekolah', [ProfilSekolahController::class, 'show']);
    Route::put('/profil-sekolah', [ProfilSekolahController::class, 'update']);

    // Verifikasi
    Route::get('/verifikasi',              [VerifikasiController::class, 'index']);
    Route::post('/verifikasi/{id}/accept', [VerifikasiController::class, 'accept']);
    Route::post('/verifikasi/{id}/reject', [VerifikasiController::class, 'reject']);
    Route::delete('/verifikasi/{id}', [VerifikasiController::class, 'destroy']);

    // Aspek Perkembangan
    Route::get('/aspek',          [AspekPerkembanganController::class, 'index']);
    Route::post('/aspek',         [AspekPerkembanganController::class, 'store']);
    Route::get('/aspek/{id}',     [AspekPerkembanganController::class, 'show']);
    Route::put('/aspek/{id}',     [AspekPerkembanganController::class, 'update']);
    Route::delete('/aspek/{id}',  [AspekPerkembanganController::class, 'destroy']);

    // Indikator Penilaian
    Route::get('/indikator',          [IndikatorPenilaianController::class, 'index']);
    Route::post('/indikator',         [IndikatorPenilaianController::class, 'store']);
    Route::get('/indikator/{id}',     [IndikatorPenilaianController::class, 'show']);
    Route::put('/indikator/{id}',     [IndikatorPenilaianController::class, 'update']);
    Route::delete('/indikator/{id}',  [IndikatorPenilaianController::class, 'destroy']);

    // Observasi
    Route::get('/observasi',                [ObservasiController::class, 'index']);
    Route::post('/observasi',               [ObservasiController::class, 'store']);
    Route::post('/observasi/batch', [ObservasiController::class, 'storeBatch']);
    Route::get('/observasi/anak/{id_anak}', [ObservasiController::class, 'byAnak']);
    Route::get('/observasi/{id}',           [ObservasiController::class, 'show']);
    Route::put('/observasi/{id}',           [ObservasiController::class, 'update']);
    Route::delete('/observasi/{id}',        [ObservasiController::class, 'destroy']);

    // Pengumuman
    Route::post('/pengumuman',        [PengumumanController::class, 'store']);
    Route::put('/pengumuman/{id}',    [PengumumanController::class, 'update']);
    Route::delete('/pengumuman/{id}', [PengumumanController::class, 'destroy']);
});