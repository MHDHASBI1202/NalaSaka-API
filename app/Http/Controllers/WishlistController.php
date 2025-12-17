<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    // Toggle: Jika belum ada -> Tambah. Jika sudah ada -> Hapus.
    public function toggle(Request $request)
    {
        $request->validate([
            'saka_id' => 'required|exists:sakas,id'
        ]);

        $user = $request->user();
        $sakaId = $request->saka_id;

        $existing = Wishlist::where('user_id', $user->id)
                            ->where('saka_id', $sakaId)
                            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'error' => false,
                'message' => 'Dihapus dari wishlist',
                'isWishlist' => false
            ]);
        } else {
            Wishlist::create([
                'user_id' => $user->id,
                'saka_id' => $sakaId
            ]);
            return response()->json([
                'error' => false,
                'message' => 'Ditambahkan ke wishlist',
                'isWishlist' => true
            ]);
        }
    }

    // Cek status wishlist untuk UI Detail Page
    public function check($sakaId, Request $request)
    {
        $user = $request->user();
        $exists = Wishlist::where('user_id', $user->id)
                          ->where('saka_id', $sakaId)
                          ->exists();

        return response()->json([
            'error' => false,
            'isWishlist' => $exists
        ]);
    }

    // Ambil semua list wishlist user (Untuk halaman Wishlist nanti)
    public function index(Request $request)
    {
        $user = $request->user();
        $wishlists = Wishlist::with('saka')->where('user_id', $user->id)->get();

        // Format data agar sama dengan struktur saka biasa
        $listSaka = $wishlists->map(function($item) {
            return [
                'id' => (string) $item->saka->id,
                'name' => $item->saka->name,
                'photoUrl' => $item->saka->photo_url,
                'price' => $item->saka->price,
                'description' => $item->saka->description,
                'category' => $item->saka->category,
            ];
        });

        return response()->json([
            'error' => false,
            'message' => 'Wishlist dimuat',
            'listSaka' => $listSaka
        ]);
    }
}