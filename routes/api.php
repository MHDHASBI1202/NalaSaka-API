<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SakaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Rute Publik (Login/Register)
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('password/forgot', [PasswordController::class, 'forgotPassword']);
Route::post('password/reset', [PasswordController::class, 'resetPassword']);

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

    // === RUTE AUTHENTICATED (Perlu Login) ===
    Route::post('user/change-password', [PasswordController::class, 'changePassword']);

    // --- MODUL DASHBOARD ---
    Route::get('seller/stats', [DashboardController::class, 'sellerStats']);

    // --- MODUL TRANSAKSI ---
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::post('/transactions/update/{id}', [TransactionController::class, 'updateStatus']);

    // --- MODUL REPUTASI ---
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/saka/{sakaId}/reviews', [ReviewController::class, 'index']);

    // --- MODUL WISHLIST ---
    Route::post('wishlist/toggle', [App\Http\Controllers\WishlistController::class, 'toggle']);
    Route::get('wishlist/check/{sakaId}', [App\Http\Controllers\WishlistController::class, 'check']);
    Route::get('wishlist', [App\Http\Controllers\WishlistController::class, 'index']);

    // --- MODUL KERANJANG ---
    Route::get('cart', [App\Http\Controllers\CartController::class, 'index']);
    Route::post('cart', [App\Http\Controllers\CartController::class, 'addToCart']);
    Route::patch('cart/{id}', [App\Http\Controllers\CartController::class, 'updateQuantity']);
    Route::delete('cart/{id}', [App\Http\Controllers\CartController::class, 'destroy']);
    Route::post('cart/checkout', [App\Http\Controllers\CartController::class, 'checkout']);

    // Fitur Follow
    Route::post('user/follow', [FollowController::class, 'toggle']);
    Route::get('user/follow/check/{targetId}', [FollowController::class, 'checkStatus']);
    Route::put('/user/address', [UserController::class, 'updateAddress']);

    // Simpan token FCM dari HP
    Route::post('user/fcm-token', [UserController::class, 'updateFcmToken']);
    
    // Testing kirim notifikasi (Hanya untuk testing)
    Route::get('test-notif/{id}', [NotificationController::class, 'sendFollowedStoreNotification']);
    Route::post('/store/location', [App\Http\Controllers\UserController::class, 'updateStoreLocation']);
    
    // Route baru untuk mendapatkan daftar pesanan seller
    Route::get('/seller/orders', [App\Http\Controllers\TransactionController::class, 'sellerOrders']);
});