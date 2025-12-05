<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Saka;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        // Ambil ID user dari parameter
        $userId = $request->query('user_id');

        if(!$userId) {
            return response()->json(['error' => true, 'message' => 'User ID wajib diisi'], 400);
        }

        // Ambil transaksi milik user tersebut
        $transactions = Transaction::with('saka') // Include data produknya
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Format data untuk Android (VERSI AMAN)
        $history = $transactions->map(function($item) {
            
            // CEK DULU: Apakah produknya (saka) masih ada?
            // Kalau produk dihapus, kita kasih nama default agar tidak error.
            $productName = $item->saka ? $item->saka->name : 'Produk Tidak Ditemukan';
            $productImage = $item->saka ? $item->saka->photo_url : 'https://placehold.co/100x100?text=No+Image';

            return [
                'id' => (string) $item->id,
                'productName' => $productName,
                'productImage' => $productImage,
                'price' => $item->total_price,
                'status' => $item->status, 
                'date' => $item->created_at->format('d M Y'),
                'tracking' => [
                    'location' => $item->current_location ?? 'Belum ada update lokasi',
                    'resi' => $item->resi_number ?? '-'
                ]
            ];
        });

        return response()->json([
            'error' => false,
            'message' => 'Riwayat transaksi berhasil dimuat',
            'history' => $history
        ]);
    }

    // 2. BUAT PESANAN BARU (Checkout / Pesan Ulang)
    // POST /api/transactions
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'saka_id' => 'required', // ID Produk yang dibeli
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()], 400);
        }

        // Ambil harga produk
        $product = Saka::find($request->saka_id);
        if(!$product) {
            return response()->json(['error' => true, 'message' => 'Produk tidak ditemukan'], 404);
        }

        $totalPrice = $product->price * $request->quantity;

        // Simpan Transaksi
        $transaction = Transaction::create([
            'user_id' => $request->user_id,
            'saka_id' => $request->saka_id,
            'quantity' => $request->quantity,
            'total_price' => $totalPrice,
            'status' => 'DIPROSES', // Status awal
            'current_location' => 'Gudang Penjual', // Lokasi awal
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Pesanan berhasil dibuat!',
            'transaction_id' => $transaction->id
        ], 201);
    }
    
    // 3. UPDATE STATUS (Untuk Simulasi Admin/Kurir Update Lokasi)
    // POST /api/transactions/update/{id}
    public function updateStatus(Request $request, $id) {
        $trx = Transaction::find($id);
        if($trx) {
            $trx->update([
                'status' => $request->status ?? $trx->status,
                'current_location' => $request->location ?? $trx->current_location
            ]);
            return response()->json(['message' => 'Status berhasil diupdate']);
        }
        return response()->json(['message' => 'Transaksi tidak ditemukan']);
    }
}