<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;

class NotificationController extends Controller
{
    /**
     * Trigger Notifikasi Manual untuk Testing
     * GET /api/test-notif/{userId}
     */
    public function sendFollowedStoreNotification($userId)
    {
        // Load user beserta toko yang di-follow
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
            
        // Ambil 1 toko secara acak dari yang di-follow
        $randomStore = $user->following->random();

        $title = "Kabar dari " . ($randomStore->store_name ?: $randomStore->name);
        $body = "Ada produk segar baru hari ini, Yang Mulia! Cek sekarang sebelum kehabisan.";

        return $this->sendFCM($user->fcm_token, $title, $body);
    }

    /**
     * Fungsi Inti Pengiriman FCM menggunakan HTTP v1 API
     */
    private function sendFCM($fcmToken, $title, $body)
    {
        $filePath = storage_path('app/firebase-auth.json');
        
        if (!file_exists($filePath)) {
            return response()->json(['error' => true, 'message' => 'File firebase-auth.json tidak ditemukan di storage/app'], 500);
        }

        // 1. Authentikasi dengan Google Service Account
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
        $credentials = new ServiceAccountCredentials($scopes, $filePath);
        $accessToken = $credentials->fetchAuthToken(HttpHandlerFactory::build())['access_token'];

        // 2. Ambil Project ID dari file JSON
        $projectInfo = json_decode(file_get_contents($filePath), true);
        $projectId = $projectInfo['project_id'];

        // 3. Susun Payload Notifikasi
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        
        $data = [
            "message" => [
                "token" => $fcmToken,
                "notification" => [
                    "title" => $title,
                    "body" => $body
                ],
                "data" => [
                    "type" => "promo", // Data tambahan untuk navigasi di Android jika perlu
                    "store_id" => "123"
                ]
            ]
        ];

        // 4. Kirim Request ke Firebase
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
}