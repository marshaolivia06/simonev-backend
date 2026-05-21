<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\VerifikasiController;

// ── Public (tidak perlu login) ──────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ── Protected (wajib login, kirim token) ────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::get('/profil',   [AuthController::class, 'profile']);
    Route::put('/profil',   [AuthController::class, 'updateProfile']);

    // Guru CRUD
    Route::get('/guru',              [GuruController::class, 'index']);
    Route::get('/guru/{id}',         [GuruController::class, 'show']);
    Route::post('/guru',             [GuruController::class, 'store']);
    Route::put('/guru/{guru}',       [GuruController::class, 'update']);
    Route::delete('/guru/{guru}',    [GuruController::class, 'destroy']);

    // Kelas CRUD
    Route::get('/kelas',           [KelasController::class, 'index']);
    Route::get('/kelas/{id}',      [KelasController::class, 'show']);
    Route::post('/kelas',          [KelasController::class, 'store']);
    Route::put('/kelas/{id}',      [KelasController::class, 'update']);
    Route::delete('/kelas/{id}',   [KelasController::class, 'destroy']);

    // Verifikasi akun (admin)
    Route::get('/verifikasi',                    [VerifikasiController::class, 'index']);
    Route::post('/verifikasi/{id}/accept',       [VerifikasiController::class, 'accept']);
    Route::post('/verifikasi/{id}/reject',       [VerifikasiController::class, 'reject']);
});