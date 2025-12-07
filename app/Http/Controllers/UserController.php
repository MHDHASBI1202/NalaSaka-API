<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
                'phoneNumber' => '0812xxxxxx',
                'address' => 'Alamat Pengguna Mocking',
            ]
        ]);
    }
}