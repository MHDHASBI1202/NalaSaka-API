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
            ]
        ]);
    }
    
    // Fungsi REGISTER
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8',
        ]);
        
        // Buat user baru
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Enkripsi password
        ]);

        // Kirim respons sukses sesuai ResponseSaka.kt
        return response()->json([
            'error' => false,
            'message' => 'Pendaftaran berhasil. Silakan Login.',
        ], 201); // 201 Created
    }
}