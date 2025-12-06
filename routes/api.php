<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SakaController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Rute Publik (Login/Register)
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Rute yang Membutuhkan Token
Route::middleware('auth:sanctum')->group(function () {
    
    // Rute Baru: Logout
    Route::post('logout', [AuthController::class, 'logout']); 

    // Rute Produk (Saka) - Berdasarkan Sumber Asli
    Route::get('saka', [SakaController::class, 'index']);
    Route::get('saka/{sakaId}', [SakaController::class, 'show']);
    Route::post('saka', [SakaController::class, 'store']); // Tambah Produk (Upload Foto Barang)
    
    // Rute Profil
    Route::get('user/profile', [UserController::class, 'profile']); // Menggunakan 'profile' sesuai sumber asli

    // Rute Baru: Mulai Menjual (Verifikasi Data Penjual)
    Route::post('user/become-seller', [UserController::class, 'becomeSeller']);
    
    // Rute Transaksi (Riwayat & Beli)
    Route::get('/transactions', [TransactionController::class, 'index']); 
    Route::post('/transactions', [TransactionController::class, 'store']); // Beli Barang (Checkout/Pesan Ulang)
    Route::post('/transactions/update/{id}', [TransactionController::class, 'updateStatus']); // Update Status
});