<?php

namespace App\Http\Controllers;

use App\Models\Saka; // Pastikan baris ini ada agar nyambung ke Model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SakaController extends Controller
{
    // 1. FUNGSI UNTUK MENGAMBIL DATA (GET)
    public function index()
    {
        // Ambil semua data dari tabel 'sakas' di database
        $sakas = Saka::all();

        // Ubah format data agar sesuai permintaan Aplikasi Android (camelCase)
        $listSaka = $sakas->map(function($item) {
            return [
                'id' => (string) $item->id,        // Android minta ID bentuk string
                'name' => $item->name,
                'description' => $item->description,
                'price' => $item->price,
                'photoUrl' => $item->photo_url,     // Android minta 'photoUrl', Database punya 'photo_url'
            ];
        });

        // Kirim paket JSON ke Android
        return response()->json([
            'error' => false,
            'message' => 'Daftar produk berhasil dimuat dari Database',
            'listSaka' => $listSaka
        ], 200);
    }

    // 2. FUNGSI UNTUK DETAIL (GET ONE) - Opsional buat nanti
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
            ]
        ], 200);
    }
}