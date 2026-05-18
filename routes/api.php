<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profil', [AuthController::class, 'profile']);
    Route::put('/profil', [AuthController::class, 'updateProfile']);
});