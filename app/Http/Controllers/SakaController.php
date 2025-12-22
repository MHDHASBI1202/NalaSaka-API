<?php

namespace App\Http\Controllers;

use App\Models\Saka;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class SakaController extends Controller
{
    /**
     * Menampilkan semua produk dengan opsi Sorting
     * GET /api/saka?sort=price_asc
     */
    public function index(Request $request)
    {
        $sort = $request->query('sort');

        // Eager Loading relasi user
        $query = Saka::with('user');

        // Logika Sorting
        if ($sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $sakas = $query->get();

        $listSaka = $sakas->map(function($item) {
            return $this->formatSaka($item);
        });

        return response()->json([
            'error' => false,
            'message' => 'Daftar produk berhasil dimuat',
            'listSaka' => $listSaka
        ], 200);
    }

    /**
     * Menampilkan produk milik seller yang sedang login
     */
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

    /**
     * Menampilkan detail satu produk
     */
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

    /**
     * Tambah Produk Baru (Oleh Seller)
     * POST /api/saka
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'seller') {
            return response()->json(['error' => true, 'message' => 'Hanya akun Penjual yang boleh mengunggah barang.'], 403);
        }

        // VALIDASI TERMASUK DISKON
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price', // Harus lebih kecil dari harga normal
            'stock' => 'required|integer|min:0',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'discount_price.lt' => 'Harga diskon harus lebih murah dari harga normal, Yang Mulia.'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()], 400);
        }

        // Handle Upload Foto
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $path = $file->store('sakas', 'public'); 
            $photoUrl = url('storage/' . $path);
        } else {
            return response()->json(['error' => true, 'message' => 'File foto wajib diunggah'], 400);
        }

        // SIMPAN KE DATABASE
        $saka = Saka::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'category' => $request->category,
            'description' => $request->description,
            'price' => $request->price,
            'discount_price' => $request->discount_price, // DATA DISKON DISIMPAN DISINI
            'stock' => $request->stock,
            'photo_url' => $photoUrl,
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Produk berhasil diunggah ke Toko Anda!',
            'saka' => $this->formatSaka($saka)
        ], 201);
    }

    /**
     * Update Stok Barang
     */
    public function updateStock(Request $request, $id)
    {
        $user = $request->user();
        $saka = Saka::find($id);

        if (!$saka) {
            return response()->json(['error' => true, 'message' => 'Produk tidak ditemukan'], 404);
        }

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

    /**
     * Hapus Barang
     */
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

        $saka->delete();

        return response()->json([
            'error' => false,
            'message' => 'Produk berhasil dihapus'
        ]);
    }

    /**
     * Helper Private untuk format JSON output agar sinkron dengan Android
     */
    private function formatSaka($item) {
        $seller = $item->user; 
        $isVerified = $seller ? ($seller->verification_status === 'verified') : false;
        $sellerName = $seller ? ($seller->store_name ?? $seller->name) : 'Unknown Seller';
        $sellerPhoto = $seller ? ($seller->photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($sellerName) . '&background=D57B0E&color=fff') : null;

        return [
            'id' => (string) $item->id,
            'name' => $item->name,
            'category' => $item->category,
            'description' => $item->description,
            'price' => (int) $item->price,
            'discountPrice' => $item->discount_price ? (int) $item->discount_price : null, // KIRIM KE ANDROID
            'stock' => (int) $item->stock,
            'photoUrl' => $item->photo_url,
            'sellerId' => (string) $item->user_id,
            'isSellerVerified' => $isVerified,
            'sellerName' => $sellerName,
            'sellerPhotoUrl' => $sellerPhoto
        ];
    }
}