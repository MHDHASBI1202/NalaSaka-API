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
                'sakaId' => (string) $item->saka_id,
                'productName' => $productName,
                'productImage' => $productImage,
                'price' => $item->total_price,
                'status' => $item->status, 
                'date' => $item->created_at->format('d M Y'),
                'paymentMethod' => $item->payment_method,
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
    $user = $request->user();
    $request->validate([
        'saka_id' => 'required',
        'quantity' => 'required|integer',
        'payment_method' => 'required|string',
        'full_address' => 'required|string',
        'subtotal' => 'required|integer',
        'total_amount' => 'required|integer',
        'shipping_method' => 'required|string',
    ]);

    // Jika transfer, status 'pending_payment'. Jika COD, 'processed'.
    $status = ($request->payment_method === 'transfer') ? 'pending_payment' : 'processed';

    $transaction = Transaction::create(array_merge(
        $request->all(), 
        ['user_id' => $user->id, 'status' => $status]
    ));

    return response()->json(['error' => false, 'message' => 'Pesanan berhasil dibuat!', 'transaction' => $transaction]);
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
    public function sellerOrders(Request $request) {
    $sellerId = $request->user()->id;

        // Ambil transaksi dimana saka_id dimiliki oleh seller ini
        $orders = \App\Models\Transaction::whereHas('saka', function($query) use ($sellerId) {
                $query->where('user_id', $sellerId);
            })
            ->with(['user', 'saka']) // Load data pembeli dan produk
            ->latest()
            ->get();

        return response()->json($orders->map(function($order) {
            return [
                'id' => $order->id,
                'product_name' => $order->saka->name,
                'quantity' => $order->quantity,
                'status' => $order->status,
                'resi_number' => $order->resi_number,
                'current_location' => $order->current_location, // Lokasi pengiriman dari pembeli
                'buyer_name' => $order->user->name
            ];
        }));
    }
}