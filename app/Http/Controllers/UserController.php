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

        // Mengambil data user dari database (sesuai Model User.php)
        // MENGHILANGKAN DATA MOCK
        return response()->json([
            'error' => false,
            'message' => 'Detail Profil dimuat dari database.',
            'user' => [
                'userId' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photoUrl' => 'https://i.imgur.com/K1S2Y9C.png', // Placeholder (URL foto tidak ada di kolom User yang tersedia)
                'phoneNumber' => $user->phone_number, // Menggunakan data dari DB
                'address' => $user->address, // Menggunakan data dari DB
            ]
        ]);
    }
    
    // Merespons PATCH /api/user/profile untuk update profil (name, phone_number, address)
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        // Validasi input. 'sometimes' agar user bisa hanya mengupdate satu field.
        // 'email' tidak diizinkan untuk di-update di sini, sesuai permintaan.
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone_number' => 'sometimes|required|string|max:15',
            'address' => 'sometimes|required|string|max:255',
        ]);

        // Update data user
        $user->update($validated);

        // Ambil data terbaru untuk dikembalikan ke klien
        return response()->json([
            'error' => false,
            'message' => 'Profil berhasil diperbarui.',
            'user' => [
                'userId' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photoUrl' => 'https://i.imgur.com/K1S2Y9C.png', // Placeholder
                'phoneNumber' => $user->phone_number,
                'address' => $user->address,
            ]
        ]);
    }
}