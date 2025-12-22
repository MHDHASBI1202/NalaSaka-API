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
        

        $store = \DB::table('stores')->where('user_id', $user->id)->first();
        // Hitung jumlah
        $followersCount = $user->followers()->count();
        $followingCount = $user->following()->count();

        $photoUrl = $user->photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=D57B0E&color=fff';

        return response()->json([
            'error' => false,
            'message' => 'Detail Profil dimuat.',
            'user' => [
                'userId' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photoUrl' => $photoUrl,
                'phoneNumber' => $user->phone_number,
                'address' => $user->address,
                'role' => $user->role ?: 'customer',
                'storeName' => $user->store_name,
                'store_address' => $store ? $store->address : null,
                'verificationStatus' => $user->verification_status ?? 'none',
                'followersCount' => $followersCount,
                'followingCount' => $followingCount,
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
            // store_name opsional, hanya divalidasi jika user sudah jadi seller
            'store_name' => 'sometimes|nullable|string|max:255|unique:users,store_name,'.$user->id,
        ]);
        
        // Jika user adalah seller, kita izinkan update store_name
        if ($user->role === 'seller' && isset($validated['store_name'])) {
            $user->store_name = $validated['store_name'];
            unset($validated['store_name']);
        }

        $user->update($validated);

        // Pastikan photoUrl juga dikirim di response update agar konsisten
        $photoUrl = $user->photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=D57B0E&color=fff';

        // Ambil data terbaru untuk dikembalikan ke klien
        return response()->json([
            'error' => false,
            'message' => 'Profil berhasil diperbarui.',
            'user' => [
                'userId' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photoUrl' => $photoUrl,
                'phoneNumber' => $user->phone_number,
                'address' => $user->address,
                'role' => $user->role,
                'storeName' => $user->store_name,
                'verificationStatus' => $user->verification_status ?? 'none',
            ]
        ]);
    }

    // Merespons POST /api/user/activate-seller
    public function activateSellerMode(Request $request)
    {
        $user = $request->user();

        // Cek apakah user sudah jadi seller
        if ($user->role === 'seller') {
            $photoUrl = $user->photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=D57B0E&color=fff';
            
            return response()->json([
                'error' => true,
                'message' => 'Anda sudah terdaftar sebagai penjual.',
                'user' => [
                    'userId' => (string) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'photoUrl' => $photoUrl,
                    'phoneNumber' => $user->phone_number,
                    'address' => $user->address,
                    'role' => $user->role,
                    'storeName' => $user->store_name,
                    'verificationStatus' => $user->verification_status ?? 'none',
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

        $photoUrl = $user->photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=D57B0E&color=fff';

        // Ambil data terbaru untuk dikembalikan ke klien
        return response()->json([
            'error' => false,
            'message' => 'Mode penjual berhasil diaktifkan.',
            'user' => [
                'userId' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photoUrl' => $photoUrl,
                'phoneNumber' => $user->phone_number,
                'address' => $user->address,
                'role' => $user->role,
                'storeName' => $user->store_name,
                'verificationStatus' => $user->verification_status ?? 'none',
            ]
        ]);

        
    }

    // Merespons POST /api/user/upload-certification
    public function uploadCertification(Request $request)
    {
        $user = $request->user();

        // Validasi: Hanya role seller yang butuh verifikasi
        if ($user->role !== 'seller') {
            return response()->json(['error' => true, 'message' => 'Hanya penjual yang dapat mengajukan verifikasi.'], 403);
        }

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $path = $file->store('certifications', 'public');
            $url = url('storage/' . $path);

            $user->update([
                'certification_url' => $url,
                'verification_status' => 'verified' // [SIMULASI] Langsung verified agar bisa langsung dites di Android
            ]);

            return response()->json([
                'error' => false,
                'message' => 'Dokumen berhasil diunggah! Akun Anda kini Terverifikasi.',
                'user' => [
                    // Return data user terupdate (sama seperti response profile)
                    'userId' => (string) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'photoUrl' => $user->photo_url,
                    'role' => $user->role,
                    'storeName' => $user->store_name,
                    'verificationStatus' => $user->verification_status // Kirim status baru
                ]
            ]);
        }

        return response()->json(['error' => true, 'message' => 'Gagal mengunggah file.'], 400);
    }
        public function updateAddress(Request $request) {
            $user = $request->user();
            $request->validate(['address' => 'required|string']);
            $user->update(['address' => $request->address]);
            return response()->json(['message' => 'Alamat utama berhasil diperbarui']);
        }
        public function updateStoreLocation(Request $request) {
        $request->validate([
            'address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // Simpan atau Update data toko milik user yang sedang login
        $store = \App\Models\Store::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]
        );

        return response()->json([
            'message' => 'Lokasi toko berhasil diperbarui',
            'data' => $store
        ]);
    }
    
    public function updateFcmToken(Request $request) {
        $request->validate(['fcm_token' => 'required|string']);
        $request->user()->update(['fcm_token' => $request->fcm_token]);
        return response()->json(['message' => 'Token updated']);
    }
}