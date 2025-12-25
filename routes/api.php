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

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('password/forgot', [PasswordController::class, 'forgotPassword']);
Route::post('password/reset', [PasswordController::class, 'resetPassword']);

Route::get('seller/report/download', [ReportController::class, 'downloadReport']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('saka', [SakaController::class, 'index']);
    Route::get('saka/my-products', [SakaController::class, 'myProducts']);
    Route::get('saka/{sakaId}', [SakaController::class, 'show']);
    Route::post('saka', [SakaController::class, 'store']);
    Route::patch('saka/{id}/stock', [SakaController::class, 'updateStock']);
    Route::delete('saka/{id}', [SakaController::class, 'destroy']);

    Route::get('user/profile', [UserController::class, 'profile']);
    Route::patch('user/profile', [UserController::class, 'updateProfile']);
    Route::post('user/activate-seller', [UserController::class, 'activateSellerMode']);
    Route::post('user/upload-certification', [UserController::class, 'uploadCertification']);

    Route::post('user/change-password', [PasswordController::class, 'changePassword']);

    Route::get('seller/stats', [DashboardController::class, 'sellerStats']);

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::post('/transactions/update/{id}', [TransactionController::class, 'updateStatus']);

    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/saka/{sakaId}/reviews', [ReviewController::class, 'index']);

    Route::post('wishlist/toggle', [App\Http\Controllers\WishlistController::class, 'toggle']);
    Route::get('wishlist/check/{sakaId}', [App\Http\Controllers\WishlistController::class, 'check']);
    Route::get('wishlist', [App\Http\Controllers\WishlistController::class, 'index']);

    Route::get('cart', [App\Http\Controllers\CartController::class, 'index']);
    Route::post('cart', [App\Http\Controllers\CartController::class, 'addToCart']);
    Route::patch('cart/{id}', [App\Http\Controllers\CartController::class, 'updateQuantity']);
    Route::delete('cart/{id}', [App\Http\Controllers\CartController::class, 'destroy']);
    Route::post('cart/checkout', [App\Http\Controllers\CartController::class, 'checkout']);

    Route::post('user/follow', [FollowController::class, 'toggle']);
    Route::get('user/follow/check/{targetId}', [FollowController::class, 'checkStatus']);
    Route::put('/user/address', [UserController::class, 'updateAddress']);

    Route::post('user/fcm-token', [UserController::class, 'updateFcmToken']);
    
    Route::get('test-notif/{id}', [NotificationController::class, 'sendFollowedStoreNotification']);
    Route::post('/store/location', [App\Http\Controllers\UserController::class, 'updateStoreLocation']);
    
    Route::get('/seller/orders', [App\Http\Controllers\TransactionController::class, 'sellerOrders']);
    Route::get('seller/orders', [TransactionController::class, 'sellerOrders']);

    Route::post('seller/broadcast', [NotificationController::class, 'broadcastToFollowers']);

    Route::post('test-promo-notif', [NotificationController::class, 'sendPromoNotification']);
});