<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PasswordController extends Controller
{
    // 1. REQUEST RESET PASSWORD (Kirim Token ke Log/Email)
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Email tidak ditemukan.'
            ], 404);
        }

        $token = Str::random(6); // Token pendek 6 karakter agar mudah diketik di HP
        $email = $request->email;

        // Simpan token ke tabel password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $token, 
                'created_at' => Carbon::now()
            ]
        );

        // --- SIMULASI LOG (Ganti dengan Mail::to() jika sudah ada SMTP) ---
        Log::info("TOKEN RESET UNTUK $email ADALAH: $token");

        return response()->json([
            'error' => false,
            'message' => 'Token reset telah dikirim (Cek Log Laravel Anda).',
            // Kita kirim token di response body HANYA untuk kemudahan testing Yang Mulia.
            // Di production, hapus baris di bawah ini.
            'debug_token' => $token 
        ]);
    }

    // 2. PROSES RESET PASSWORD (Verifikasi Token & Ganti Pass)
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed' // Butuh password_confirmation
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()], 400);
        }

        // Cek Token
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$resetRecord) {
            return response()->json(['error' => true, 'message' => 'Token salah atau kadaluarsa.'], 400);
        }

        // Update Password
        $user = User::where('email', $request->email)->first();
        $user->update(['password' => Hash::make($request->password)]);

        // Hapus token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'error' => false,
            'message' => 'Password berhasil direset. Silakan login kembali.'
        ]);
    }

    // 3. GANTI PASSWORD (Fitur Profil - Harus Login)
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed' // Butuh new_password_confirmation
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()], 400);
        }

        $user = $request->user();

        // Cek apakah password lama benar
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => true, 'message' => 'Password saat ini salah.'], 400);
        }

        // Update ke password baru
        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json([
            'error' => false,
            'message' => 'Password berhasil diubah.'
        ]);
    }
}