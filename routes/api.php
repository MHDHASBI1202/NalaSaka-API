<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SakaController; // Import baru
use App\Http\Controllers\UserController; // Import baru
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Rute Publik (Login/Register)
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Rute yang Membutuhkan Token (Semua rute di bawah ini akan diakses setelah Login)
Route::middleware('auth:sanctum')->group(function () {
    
    // Rute Produk (Home & Product Screen: GET /api/saka)
    Route::get('saka', [SakaController::class, 'index']);
    
    // [PERBAIKAN PENTING] 
    // Rute 'my-products' HARUS diletakkan SEBELUM 'saka/{sakaId}'
    // Agar kata "my-products" tidak dianggap sebagai ID produk.
    Route::get('saka/my-products', [SakaController::class, 'myProducts']);

    // Rute Detail Produk (Wildcard {sakaId} menangkap semua ID setelah /saka/)
    Route::get('saka/{sakaId}', [SakaController::class, 'show']);
    
    // Rute Tambah Produk (Upload Foto Barang)
    Route::post('saka', [SakaController::class, 'store']); 

    // Update Stok
    Route::patch('saka/{id}/stock', [SakaController::class, 'updateStock']);
    
    // Hapus Barang
    Route::delete('saka/{id}', [SakaController::class, 'destroy']);
    
    // Rute Profil (Profile Screen: GET /api/user/profile)
    Route::get('user/profile', [UserController::class, 'profile']);
    
    // Rute Update Profil
    Route::patch('user/profile', [UserController::class, 'updateProfile']);
    
    // Rute untuk mengaktifkan mode penjual
    Route::post('user/activate-seller', [UserController::class, 'activateSellerMode']);

    // Rute Statistik Dashboard Seller
    Route::get('seller/stats', [DashboardController::class, 'sellerStats']);

    // Lihat Riwayat
    Route::get('/transactions', [TransactionController::class, 'index']); 

    // Beli Barang (Checkout/Pesan Ulang)
    Route::post('/transactions', [TransactionController::class, 'store']); 

    // Update (Simulasi Admin)
    Route::post('/transactions/update/{id}', [TransactionController::class, 'updateStatus']);
});