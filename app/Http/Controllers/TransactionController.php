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
        $user = $request->user();
        $userId = $user->id;

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
    public function store(Request $request) {
        // Ambil user yang sedang login dari Token
        $user = $request->user(); 

        // 1. Validasi (Hapus 'user_id' dari sini agar tidak error 400)
        $request->validate([
            'saka_id'        => 'required',
            'quantity'       => 'required|integer',
            'payment_method' => 'required|string',
            'full_address'   => 'required|string',
            'subtotal'       => 'required|integer',
            'total_price'    => 'required|integer',
            'shipping_method'=> 'required|string',
        ]);

        try {
            // 2. Simpan ke Database
            $transaction = Transaction::create([
                'user_id'          => $user->id, // Diambil otomatis dari sistem login
                'saka_id'          => $request->saka_id,
                'quantity'         => $request->quantity,
                'payment_method'   => $request->payment_method,
                'full_address'     => $request->full_address,
                'subtotal'         => $request->subtotal,
                'total_price'      => $request->total_price,
                'shipping_method'  => $request->shipping_method,
                'status'           => 'processed',
                'current_location' => 'Diproses Penjual'
            ]);

            // 3. Hapus item dari Cart (Sinkronisasi agar cart kosong)
            \App\Models\Cart::where('user_id', $user->id)
                            ->where('saka_id', $request->saka_id)
                            ->delete();

            // --- 4. KIRIM NOTIFIKASI KE SELLER (Logic Firebase) ---
            $this->sendNotificationToSeller($transaction);

            return response()->json([
                'error' => false, 
                'message' => 'Pesanan berhasil dibuat!', 
                'transaction' => $transaction
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Gagal simpan: ' . $e->getMessage()
            ], 500);
        }
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
    private function sendNotificationToSeller($transaction) {
        try {
            // Cari produk dan penjualnya
            $product = \App\Models\Saka::find($transaction->saka_id);
            $seller = \App\Models\User::find($product->user_id);

            if ($seller && $seller->fcm_token) {
                $messaging = app('firebase.messaging');
                
                $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $seller->fcm_token)
                    ->withNotification(\Kreait\Firebase\Messaging\Notification::create(
                        'Ada Pesanan Baru!', 
                        'Produk ' . $product->name . ' telah dipesan.'
                    ))
                    ->withData([
                        'transaction_id' => (string) $transaction->id,
                        'click_action' => 'OPEN_SELLER_ORDERS'
                    ]);

                $messaging->send($message);
            }
        } catch (\Exception $e) {
            \Log::error("Gagal kirim notif: " . $e->getMessage());
        }
    }
}