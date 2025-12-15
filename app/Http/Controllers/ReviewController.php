<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Saka;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    // 1. TAMBAH / UPDATE ULASAN (POST)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'saka_id' => 'required|exists:sakas,id',
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'photo'   => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true, 
                'message' => $validator->errors()->first()
            ], 400);
        }

        $user = $request->user();

        // [LOGIKA BARU] Cek apakah user sudah pernah review produk ini?
        $existingReview = Review::where('user_id', $user->id)
                                ->where('saka_id', $request->saka_id)
                                ->first();

        // Jika ada, lakukan UPDATE (Edit)
        if ($existingReview) {
            $updateData = [
                'rating' => $request->rating,
                'comment' => $request->comment,
            ];

            // Update foto hanya jika ada upload baru
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $path = $file->store('reviews', 'public'); 
                $updateData['image_url'] = url('storage/' . $path);
            }

            $existingReview->update($updateData);
            
            return response()->json([
                'error' => false,
                'message' => 'Ulasan Anda berhasil diperbarui!', // Pesan beda untuk edit
                'review'  => $existingReview
            ], 200);
        }

        // Jika belum ada, buat BARU
        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $path = $file->store('reviews', 'public'); 
            $photoUrl = url('storage/' . $path);
        }

        $review = Review::create([
            'user_id'   => $user->id,
            'saka_id'   => $request->saka_id,
            'rating'    => $request->rating,
            'comment'   => $request->comment,
            'image_url' => $photoUrl
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Ulasan berhasil dikirim!',
            'review'  => $review
        ], 201);
    }

    // 2. LIHAT ULASAN (GET)
    public function index($sakaId)
    {
        $saka = Saka::find($sakaId);
        if (!$saka) {
            return response()->json(['error' => true, 'message' => 'Produk tidak ditemukan'], 404);
        }

        $reviews = Review::where('saka_id', $sakaId)
            ->with('user') // Ambil data user relasi
            ->orderBy('updated_at', 'desc') // Urutkan dari yang terakhir diedit/dibuat
            ->get();

        $formattedReviews = $reviews->map(function($item) {
            // Safety check jika user dihapus
            $userName = $item->user ? $item->user->name : 'Pengguna Dihapus';
            $userId = $item->user ? (string)$item->user->id : '';
            
            // Cek apakah kolom photo_url ada di tabel users (antisipasi error)
            $userPhoto = null;
            if ($item->user && isset($item->user->photo_url)) {
                $userPhoto = $item->user->photo_url;
            }
            
            // Fallback avatar jika kosong
            if (!$userPhoto) {
                $userPhoto = 'https://ui-avatars.com/api/?name=' . urlencode($userName) . '&background=random';
            }

            return [
                'id' => (string) $item->id,
                'userId' => $userId, // [PENTING] Kirim ID user untuk validasi di Android
                'userName' => $userName,
                'userPhoto' => $userPhoto,
                'rating' => (int) $item->rating,
                'comment' => $item->comment ?? "",
                'imageUrl' => $item->image_url,
                'date' => $item->updated_at->format('d M Y') // Gunakan updated_at agar tanggal edit terlihat
            ];
        });

        $avgRating = $reviews->avg('rating') ?? 0.0;
        $totalReviews = $reviews->count();

        return response()->json([
            'error' => false,
            'message' => 'Daftar ulasan dimuat',
            'data' => [
                'averageRating' => round($avgRating, 1),
                'totalReviews' => $totalReviews,
                'reviews' => $formattedReviews
            ]
        ]);
    }
}