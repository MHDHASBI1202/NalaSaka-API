<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Saka;
use App\Models\User;

class UserController extends Controller
{
    // Merespons GET /api/user/profile
    public function profile(Request $request)
    {
        $user = $request->user(); // Mengambil data user dari token

        // Mocking Data Profil (sesuai format ProfileResponse.kt)
        return response()->json([
            'error' => false,
            'message' => 'Detail Profil dimuat (API LIVE)',
            'user' => [
                'userId' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photoUrl' => 'https://i.imgur.com/K1S2Y9C.png', // Placeholder
                'phoneNumber' => $user->phone_number,
                'address' => $user->address,
                'totalSaka' => $totalSaka,
            ]
        ]);
    }

    public function profile()
    {
        // Ambil user yang sedang login (membutuhkan middleware auth:sanctum)
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // --- LOGIKA PENGAMBILAN DATA STATISTIK ---
        $totalSaka = $user->sakas()->count(); // Hitung total Saka milik user

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            // --- DATA STATISTIK DITAMBAHKAN DI SINI ---
            'total_saka' => $totalSaka,
        ];

        return response()->json([
            'error' => false,
            'message' => 'User profile with statistics retrieved successfully',
            'user' => $data
        ]);
    }
}