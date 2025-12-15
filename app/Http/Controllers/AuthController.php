<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User; // Model default Laravel

class AuthController extends Controller
{
    // Fungsi LOGIN
    public function login(Request $request)
    {
        // Validasi input email dan password
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Coba otentikasi (memverifikasi kredensial di tabel users)
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'error' => true,
                'message' => 'Email atau Password salah.'
            ], 401); // 401 Unauthorized
        }

        // Otentikasi sukses, ambil user
        $user = $request->user();

        // Buat token (Pastikan kolom personal_access_tokens ada)
        $token = $user->createToken('auth_token')->plainTextToken;

        // Kirim respons sesuai format ResponseSaka.kt
        return response()->json([
            'error' => false,
            'message' => 'Login berhasil.',
            'loginResult' => [
                'userId' => $user->id,
                'name' => $user->name,
                'token' => $token,
                'role' => $user->role, // [PERBAIKAN] Kirim role (customer/seller) ke Android
            ]
        ]);
    }
    
    // Fungsi REGISTER
    public function register(Request $request)
    {
        // --- PERUBAHAN VALIDASI BARU ---
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'phone_number' => 'nullable|string|max:15', // Opsional, bisa diubah ke 'required'
            'address' => 'nullable|string|max:500', // Opsional, bisa diubah ke 'required'
            'password' => 'required|string|min:8|confirmed', // 'confirmed' akan mencari field 'password_confirmation'
        ]);
        
        // Buat user baru
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'password' => Hash::make($request->password), // Enkripsi password
            // role default sudah diatur di database migration sebagai 'customer'
        ]);
        // -----------------------------

        // Kirim respons sukses sesuai ResponseSaka.kt
        return response()->json([
            'error' => false,
            'message' => 'Pendaftaran berhasil. Silakan Login.',
        ], 201); // 201 Created
    }
}