<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;

class NotificationController extends Controller
{
    public function sendFollowedStoreNotification($userId)
    {
        $user = User::with('following')->find($userId);
        
        if (!$user) {
            return response()->json(['error' => true, 'message' => 'User tidak ditemukan'], 404);
        }

        if (!$user->fcm_token) {
            return response()->json(['error' => true, 'message' => 'User tidak memiliki FCM Token'], 400);
        }

        if ($user->following->isEmpty()) {
            return response()->json(['error' => true, 'message' => 'User tidak mengikuti toko manapun'], 400);
        }
            
        $randomStore = $user->following->random();

        $title = "Kabar dari " . ($randomStore->store_name ?: $randomStore->name);
        $body = "Ada produk segar baru hari ini, Yang Mulia! Cek sekarang sebelum kehabisan.";

        return $this->sendFCM($user->fcm_token, $title, $body);
    }

    private function sendFCM($fcmToken, $title, $body)
    {
        $filePath = storage_path('app/firebase-auth.json');
        
        if (!file_exists($filePath)) {
            return response()->json(['error' => true, 'message' => 'File firebase-auth.json tidak ditemukan di storage/app'], 500);
        }

        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
        $credentials = new ServiceAccountCredentials($scopes, $filePath);
        $accessToken = $credentials->fetchAuthToken(HttpHandlerFactory::build())['access_token'];

        $projectInfo = json_decode(file_get_contents($filePath), true);
        $projectId = $projectInfo['project_id'];

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        
        $data = [
            "message" => [
                "token" => $fcmToken,
                "notification" => [
                    "title" => $title,
                    "body" => $body
                ],
                "data" => [
                    "type" => "promo",
                    "store_id" => "123"
                ]
            ]
        ];

        $response = Http::withToken($accessToken)->post($url, $data);

        if ($response->successful()) {
            return response()->json([
                'error' => false,
                'message' => 'Notifikasi berhasil dikirim ke Yang Mulia!',
                'firebase_response' => $response->json()
            ]);
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Gagal mengirim notifikasi',
                'details' => $response->json()
            ], $response->status());
        }
    }

    public function broadcastToFollowers(Request $request)
    {
        $seller = $request->user();

        if ($seller->role !== 'seller') {
            return response()->json(['error' => true, 'message' => 'Hanya penjual yang bisa broadcast'], 403);
        }

        if ($seller->last_broadcast_at && $seller->last_broadcast_at->diffInHours(now()) < 24) {
            $remainingHours = 24 - $seller->last_broadcast_at->diffInHours(now());
            return response()->json([
                'error' => true, 
                'message' => "Anda baru bisa broadcast lagi dalam $remainingHours jam."
            ], 429);
        }

        $followers = $seller->followers()->whereNotNull('fcm_token')->get();

        if ($followers->isEmpty()) {
            return response()->json(['error' => true, 'message' => 'Belum ada pengikut yang bisa menerima notifikasi'], 400);
        }

        $title = "Kabar Seru dari " . ($seller->store_name ?: $seller->name);
        $body = "Ada produk baru atau update menarik di toko kami, Yang Mulia! Cek sekarang.";

        foreach ($followers as $follower) {
            $this->sendFCM($follower->fcm_token, $title, $body);
        }

        $seller->update(['last_broadcast_at' => now()]);

        return response()->json([
            'error' => false,
            'message' => 'Promo berhasil disiarkan ke ' . $followers->count() . ' pengikut!'
        ]);
    }

    public function sendPromoNotification(Request $request)
    {
        $user = $request->user();
        
        $promoProduct = \App\Models\Saka::whereNotNull('discount_price')->inRandomOrder()->first();

        if (!$promoProduct) {
            return response()->json(['message' => 'Belum ada produk promo'], 404);
        }

        $title = "🤑 Promo NalaSaka: " . $promoProduct->name;
        $body = "Hanya hari ini! Dapatkan harga spesial " . number_format($promoProduct->discount_price, 0, ',', '.') . " (Harga normal: " . number_format($promoProduct->price, 0, ',', '.') . "). Cek sekarang!";

        return $this->sendFCM($user->fcm_token, $title, $body);
    }
    public function sendShippingNotification($buyer, $invoiceId)
    {
        if (!$buyer || !$buyer->fcm_token) return;

        $title = "Pesanan Dikirim! 🚚";
        $body = "Kabar gembira, Yang Mulia! Barang Anda sedang di jalan. Mohon ditunggu ya!";

        return $this->sendFCM($buyer->fcm_token, $title, $body);
    }
}