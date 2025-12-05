<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SakaController; // Import baru
use App\Http\Controllers\UserController; // Import baru
use Illuminate\Support\Facades\Route;

// Rute Publik (Login/Register)
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Rute yang Membutuhkan Token (Semua rute di bawah ini akan diakses setelah Login)
Route::middleware('auth:sanctum')->group(function () {
    
    // Rute Produk (Home & Product Screen: GET /api/saka)
    Route::get('saka', [SakaController::class, 'index']);
    
    // Rute Detail Produk
    Route::get('saka/{sakaId}', [SakaController::class, 'show']);
    
    // Rute Tambah Produk (Upload Foto Barang)
    Route::post('saka', [SakaController::class, 'store']); // Ini untuk addNewSaka
    
    // Rute Profil (Profile Screen: GET /api/user/profile)
    Route::get('profile', [UserController::class, 'profile']);
});
