<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Http\Controllers\NotificationController;
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

        $transactions = Transaction::with('saka') 
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        $history = $transactions->map(function($item) {
            
            $productName = $item->saka ? $item->saka->name : 'Produk Tidak Ditemukan';
            $productImage = $item->saka ? $item->saka->photo_url : 'https://placehold.co/100x100?text=No+Image';

            return [
                'id' => (string) $item->id,
                'sakaId' => (string) $item->saka_id,
                'productName' => $productName,
                'productImage' => $productImage,
                'price' => $item->total_price,
                'status' => strtoupper($item->status),
                'date' => $item->created_at->format('d M Y'),
                'shipping_method' => $item->shipping_method,
            'pickup_code' => $item->pickup_code,
            'store_name' => $item->saka->user->store->name ?? 'Toko Penjual',
            'store_address' => $item->saka->user->store->address ?? 'Alamat Toko',
            'latitude' => $item->latitude ? (double)$item->latitude : 0.0,
            'longitude' => $item->longitude ? (double)$item->longitude : 0.0,
            
            'tracking' => [
                'location' => $item->current_location ?? 'Diproses',
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

    public function store(Request $request) {
        $user = $request->user(); 

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
            $transaction = Transaction::create([
                'user_id'          => $user->id, 
                'saka_id'          => $request->saka_id,
                'quantity'         => $request->quantity,
                'payment_method'   => $request->payment_method,
                'full_address'     => $request->full_address,
                'subtotal'         => $request->subtotal,
                'total_price'      => $request->total_price,
                'shipping_method'  => $request->shipping_method,
                'status'           => 'PROSES',
                'current_location' => 'Diproses Penjual'
            ]);

            \App\Models\Cart::where('user_id', $user->id)
                            ->where('saka_id', $request->saka_id)
                            ->delete();

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

    public function updateStatus(Request $request, $id) {
    $trx = Transaction::with('user')->find($id);

    if (!$trx) {
        return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
    }

    $statusInput = $request->input('status'); 
    
    if (!$statusInput) {
        return response()->json([
            'error' => true,
            'message' => 'Data status wajib dikirim melalui Body Postman'
        ], 400);
    }

    $oldStatus = strtoupper($trx->status);
    $newStatus = strtoupper($statusInput);

    $trx->update([
        'status' => $newStatus,
        'current_location' => $request->location ?? $trx->current_location
    ]);

    if ($oldStatus === 'PROSES' && $newStatus === 'DIKIRIM') {
        if ($trx->user && $trx->user->fcm_token) {
            try {
                $notif = new NotificationController();
                $notif->sendShippingNotification($trx->user, $trx->id);
            } catch (\Exception $e) {
                \Log::error("Gagal kirim notif: " . $e->getMessage());
            }
        }
    }

    return response()->json([
        'error' => false,
        'message' => 'Status berhasil diperbarui menjadi ' . $newStatus,
        'data' => $trx
    ]);
}

    public function sellerOrders(Request $request) {
    $sellerId = $request->user()->id;

        $orders = \App\Models\Transaction::whereHas('saka', function($query) use ($sellerId) {
                $query->where('user_id', $sellerId);
            })
            ->with(['user', 'saka']) 
            ->latest()
            ->get();

        return response()->json($orders->map(function($order) {
            return [
                'id' => $order->id,
                'product_name' => $order->saka->name,
                'quantity' => $order->quantity,
                'status' => $order->status,
                'resi_number' => $order->resi_number,
                'current_location' => $order->current_location,
                'buyer_name' => $order->user->name
            ];
        }));
    }


    private function sendNotificationToSeller($transaction) {
        try {
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