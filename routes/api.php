<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SakaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReportController; // Pastikan ini di-import
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Rute Publik (Login/Register)
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// --- [PERBAIKAN] RUTE KHUSUS CETAK PDF (DI LUAR MIDDLEWARE) ---
// Kita taruh di luar 'auth:sanctum' agar tidak dicegat middleware.
// Validasi token dilakukan manual di dalam ReportController.
Route::get('seller/report/download', [ReportController::class, 'downloadReport']);

// Rute yang Membutuhkan Token (Header Authorization)
Route::middleware('auth:sanctum')->group(function () {

    // --- MODUL PRODUK ---
    Route::get('saka', [SakaController::class, 'index']);
    Route::get('saka/my-products', [SakaController::class, 'myProducts']);
    Route::get('saka/{sakaId}', [SakaController::class, 'show']);
    Route::post('saka', [SakaController::class, 'store']);
    Route::patch('saka/{id}/stock', [SakaController::class, 'updateStock']);
    Route::delete('saka/{id}', [SakaController::class, 'destroy']);

    // --- MODUL PROFIL ---
    Route::get('user/profile', [UserController::class, 'profile']);
    Route::patch('user/profile', [UserController::class, 'updateProfile']);
    Route::post('user/activate-seller', [UserController::class, 'activateSellerMode']);
    Route::post('user/upload-certification', [UserController::class, 'uploadCertification']);

    // --- MODUL DASHBOARD ---
    Route::get('seller/stats', [DashboardController::class, 'sellerStats']);

    // --- MODUL TRANSAKSI ---
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::post('/transactions/update/{id}', [TransactionController::class, 'updateStatus']);

    // --- MODUL REPUTASI ---
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/saka/{sakaId}/reviews', [ReviewController::class, 'index']);
});