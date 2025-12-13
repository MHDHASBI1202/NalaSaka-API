<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    // Merespons GET /api/user/profile
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'error' => false,
            'message' => 'Detail Profil dimuat dari database.',
            'user' => [
                'userId' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photoUrl' => 'https://i.imgur.com/K1S2Y9C.png', // Placeholder
                'phoneNumber' => $user->phone_number,
                'address' => $user->address,
                'role' => $user->role ?: 'customer', // NEW: Default 'customer'
                'storeName' => $user->store_name, // NEW
            ]
        ]);
    }
    
    // Merespons PATCH /api/user/profile untuk update profil
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone_number' => 'sometimes|required|string|max:15',
            'address' => 'sometimes|required|string|max:255',
            // NEW: store_name opsional, hanya divalidasi jika user sudah jadi seller
            'store_name' => 'sometimes|nullable|string|max:255|unique:users,store_name,'.$user->id,
        ]);
        
        // Jika user adalah seller, kita izinkan update store_name
        if ($user->role === 'seller' && isset($validated['store_name'])) {
            $user->store_name = $validated['store_name'];
            unset($validated['store_name']);
        }

        $user->update($validated);

        // Ambil data terbaru untuk dikembalikan ke klien
        return response()->json([
            'error' => false,
            'message' => 'Profil berhasil diperbarui.',
            'user' => [
                'userId' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photoUrl' => 'https://i.imgur.com/K1S2Y9C.png',
                'phoneNumber' => $user->phone_number,
                'address' => $user->address,
                'role' => $user->role,
                'storeName' => $user->store_name,
            ]
        ]);
    }

    // NEW: Metode untuk mengaktifkan mode penjual
    // Merespons POST /api/user/activate-seller
    public function activateSellerMode(Request $request)
    {
        $user = $request->user();

        // Cek apakah user sudah jadi seller
        if ($user->role === 'seller') {
            return response()->json([
                'error' => true,
                'message' => 'Anda sudah terdaftar sebagai penjual.',
                'user' => [
                    'userId' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'photoUrl' => 'https://i.imgur.com/K1S2Y9C.png',
                    'phoneNumber' => $user->phone_number,
                    'address' => $user->address,
                    'role' => $user->role,
                    'storeName' => $user->store_name,
                ]
            ], 400); 
        }

        // Validasi input untuk nama toko
        $validated = $request->validate([
            'store_name' => 'required|string|max:255|unique:users,store_name',
        ]);
        
        // Update role menjadi 'seller' dan simpan nama toko
        $user->update([
            'role' => 'seller',
            'store_name' => $validated['store_name']
        ]);

        // Ambil data terbaru untuk dikembalikan ke klien
        return response()->json([
            'error' => false,
            'message' => 'Mode penjual berhasil diaktifkan.',
            'user' => [
                'userId' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photoUrl' => 'https://i.imgur.com/K1S2Y9C.png',
                'phoneNumber' => $user->phone_number,
                'address' => $user->address,
                'role' => $user->role,
                'storeName' => $user->store_name,
            ]
        ]);
    }
}