<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // Fungsi 'getProfile' diubah menjadi 'profile' agar konsisten dengan rute
    public function profile()
    {
        return response()->json([
            'status' => true,
            'user' => Auth::user()
        ]);
    }

    // Fungsi baru: Verifikasi dan ubah menjadi penjual (dipertahankan)
    public function becomeSeller(Request $request)
    {
        $user = Auth::user();

        // Verifikasi data (sesuai permintaan, misal nama, nomor, email, alamat, dll.)
        $validateData = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:15',
            'address' => 'required|string',
        ]);

        if ($validateData->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal verifikasi data penjual.',
                'errors' => $validateData->errors()
            ], 400);
        }

        // Update data pengguna dan set is_seller menjadi true
        $user->name = $request->name;
        $user->phone_number = $request->phone_number;
        $user->address = $request->address;
        $user->is_seller = true;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Selamat, Anda berhasil menjadi penjual!',
            'user' => $user
        ], 200);
    }
}