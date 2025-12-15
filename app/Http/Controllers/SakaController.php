<?php

namespace App\Http\Controllers;

use App\Models\Saka;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class SakaController extends Controller
{
    public function index()
    {
        $sakas = Saka::all();
        $listSaka = $sakas->map(function($item) {
            return $this->formatSaka($item);
        });

        return response()->json([
            'error' => false,
            'message' => 'Daftar produk berhasil dimuat',
            'listSaka' => $listSaka
        ], 200);
    }

    public function myProducts(Request $request)
    {
        $user = $request->user();
        $sakas = Saka::where('user_id', $user->id)->get();

        $listSaka = $sakas->map(function($item) {
            return $this->formatSaka($item);
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
            'saka' => $this->formatSaka($saka)
        ], 200);
    }

    // UPDATE: Tambah Input Kategori
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'seller') {
            return response()->json(['error' => true, 'message' => 'Hanya akun Penjual yang boleh mengunggah barang.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category' => 'required|string', // Validasi Kategori
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()], 400);
        }

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $path = $file->store('sakas', 'public'); 
            $photoUrl = url('storage/' . $path);
        } else {
            return response()->json(['error' => true, 'message' => 'File foto wajib diunggah'], 400);
        }

        $saka = Saka::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'category' => $request->category, // Simpan Kategori
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'photo_url' => $photoUrl,
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Produk berhasil diunggah ke Toko Anda!',
            'saka' => $this->formatSaka($saka)
        ], 201);
    }

    // NEW: Update Stok Barang
    public function updateStock(Request $request, $id)
    {
        $user = $request->user();
        $saka = Saka::find($id);

        if (!$saka) {
            return response()->json(['error' => true, 'message' => 'Produk tidak ditemukan'], 404);
        }

        // Pastikan yang update adalah pemilik barang
        if ($saka->user_id !== $user->id) {
            return response()->json(['error' => true, 'message' => 'Anda tidak berhak mengubah produk ini'], 403);
        }

        $validator = Validator::make($request->all(), [
            'stock' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()], 400);
        }

        $saka->update(['stock' => $request->stock]);

        return response()->json([
            'error' => false,
            'message' => 'Stok berhasil diperbarui',
            'saka' => $this->formatSaka($saka)
        ]);
    }

    // NEW: Hapus Barang
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $saka = Saka::find($id);

        if (!$saka) {
            return response()->json(['error' => true, 'message' => 'Produk tidak ditemukan'], 404);
        }

        if ($saka->user_id !== $user->id) {
            return response()->json(['error' => true, 'message' => 'Anda tidak berhak menghapus produk ini'], 403);
        }

        // Hapus file foto (opsional, agar storage tidak penuh)
        // $photoPath = str_replace(url('storage/'), '', $saka->photo_url);
        // Storage::disk('public')->delete($photoPath);

        $saka->delete();

        return response()->json([
            'error' => false,
            'message' => 'Produk berhasil dihapus'
        ]);
    }

    // Helper Private untuk format JSON
    private function formatSaka($item) {
        return [
            'id' => (string) $item->id,
            'name' => $item->name,
            'category' => $item->category, // Kirim Kategori
            'description' => $item->description,
            'price' => (int) $item->price,
            'stock' => (int) $item->stock,
            'photoUrl' => $item->photo_url,
            'sellerId' => (string) $item->user_id,
        ];
    }
}