<?php

namespace App\Http\Controllers;

use App\Models\Saka;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage; // Pastikan import ini ada

class SakaController extends Controller
{
    public function index()
    {
        $sakas = Saka::all();
        $listSaka = $sakas->map(function($item) {
            return [
                'id' => (string) $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'price' => $item->price,
                'photoUrl' => $item->photo_url,
                'sellerId' => (string) $item->user_id, // Opsional: kirim info penjual
            ];
        });

        return response()->json([
            'error' => false,
            'message' => 'Daftar produk berhasil dimuat',
            'listSaka' => $listSaka
        ], 200);
    }

    // NEW: Endpoint untuk mengambil produk milik user yang sedang login (Toko Saya)
    public function myProducts(Request $request)
    {
        $user = $request->user();
        
        // Ambil produk dimana user_id = ID user login
        $sakas = Saka::where('user_id', $user->id)->get();

        $listSaka = $sakas->map(function($item) {
            return [
                'id' => (string) $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'price' => $item->price,
                'photoUrl' => $item->photo_url,
            ];
        });

        return response()->json([
            'error' => false,
            'message' => 'Stok produk toko berhasil dimuat',
            'listSaka' => $listSaka
        ]);
    }

    public function show($id)
    {
        $saka = Saka::find($id);
        if (!$saka) {
            return response()->json(['error' => true, 'message' => 'Produk tidak ditemukan'], 404);
        }
        
        return response()->json([
            'error' => false,
            'message' => 'Detail produk ditemukan',
            'saka' => [
                'id' => (string) $saka->id,
                'name' => $saka->name,
                'description' => $saka->description,
                'price' => $saka->price,
                'photoUrl' => $saka->photo_url,
                'sellerId' => (string) $saka->user_id
            ]
        ], 200);
    }

    // UPDATE FUNGSI STORE (UPLOAD)
    public function store(Request $request)
    {
        $user = $request->user();

        // 1. Cek apakah user adalah Seller
        if ($user->role !== 'seller') {
            return response()->json([
                'error' => true,
                'message' => 'Hanya akun Penjual yang boleh mengunggah barang.'
            ], 403);
        }

        // 2. Validasi
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()], 400);
        }

        // 3. Upload File
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            // Simpan di storage/app/public/sakas
            $path = $file->store('sakas', 'public'); 
            // Buat URL lengkap
            $photoUrl = url('storage/' . $path);
        } else {
            return response()->json(['error' => true, 'message' => 'File foto wajib diunggah'], 400);
        }

        // 4. Simpan ke Database dengan user_id
        $saka = Saka::create([
            'user_id' => $user->id, // PENTING: Menandai pemilik barang
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'photo_url' => $photoUrl,
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Produk berhasil diunggah ke Toko Anda!',
            'saka' => $saka
        ], 201);
    }
}