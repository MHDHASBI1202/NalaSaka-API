<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Transaction;
use App\Models\Saka;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    // 1. LIHAT KERANJANG
    public function index(Request $request)
{
    $user = $request->user();
    
    $carts = Cart::with(['saka.user'])->where('user_id', $user->id)->get();

    $data = $carts->map(function ($item) use ($request) {
        $saka = $item->saka;
        $seller = $saka ? $saka->user : null;
        
        $store = $seller ? \DB::table('stores')->where('user_id', $seller->id)->first() : null;
        
        $pickupCode = 'NALA-' . strtoupper(substr(md5($item->id), 0, 6));

        return [
            'cart_id' => $item->id,
            'saka_id' => (string) $item->saka_id,
            'name' => $saka ? $saka->name : 'Produk tidak tersedia',
            'price' => $saka ? $saka->price : 0,
            'photo_url' => $saka ? $saka->photo_url : '',
            'quantity' => $item->quantity,
            'stock_available' => $saka ? $saka->stock : 0,
            'store_name' => $seller ? $seller->store_name : 'Toko NalaSaka',
            'storeAddress' => $store ? $store->address : 'Alamat belum diatur',
            'latitude' => $store ? (double)$store->latitude : 0.0,
            'longitude' => $store ? (double)$store->longitude : 0.0,
            'shipping_type' => $request->shipping_type ?? 'Diantar', 
            'pickup_code' => $pickupCode,               
            'status' => ($request->shipping_type == "Ambil ke Toko") ? 'SIAP DIAMBIL' : 'DIPROSES',
        ];
    });

    return response()->json([
        'error' => false,
        'message' => 'Data keranjang dimuat',
        'data' => $data
    ]);
}

    // 2. TAMBAH KE KERANJANG
    public function addToCart(Request $request)
    {
        $request->validate([
            'saka_id' => 'required|exists:sakas,id',
            'quantity' => 'integer|min:1'
        ]);

        $user = $request->user();
        $qty = $request->quantity ?? 1;

        // Cek stok dulu
        $saka = Saka::find($request->saka_id);
        if ($saka->stock < $qty) {
            return response()->json(['error' => true, 'message' => 'Stok tidak mencukupi'], 400);
        }

        // Cek apakah barang sudah ada di keranjang?
        $cart = Cart::where('user_id', $user->id)
                    ->where('saka_id', $request->saka_id)
                    ->first();

        if ($cart) {
            // Update quantity
            $cart->quantity += $qty;
            $cart->save();
        } else {
            // Buat baru
            Cart::create([
                'user_id' => $user->id,
                'saka_id' => $request->saka_id,
                'quantity' => $qty
            ]);
        }

        return response()->json(['error' => false, 'message' => 'Masuk keranjang!']);
    }

    // 3. UPDATE JUMLAH ITEM (+ / -)
    public function updateQuantity(Request $request, $id)
    {
        $cart = Cart::find($id);
        if (!$cart) return response()->json(['message' => 'Item not found'], 404);

        $newQty = $request->quantity;
        
        if ($newQty <= 0) {
            $cart->delete(); // Hapus jika 0
        } else {
            // Cek stok real-time
            if ($cart->saka->stock < $newQty) {
                return response()->json(['error' => true, 'message' => 'Stok max tercapai'], 400);
            }
            $cart->quantity = $newQty;
            $cart->save();
        }

        return response()->json(['error' => false, 'message' => 'Updated']);
    }

    // 4. HAPUS ITEM
    public function destroy($id)
    {
        Cart::destroy($id);
        return response()->json(['error' => false, 'message' => 'Item dihapus']);
    }

    // 5. CHECKOUT SEMUA (Transaksi Masal)
    public function checkout(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:CASH,TRANSFER,EWALLET', // Validasi input
        ]);

        $user = $request->user();
        $cartItems = Cart::with('saka')->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['error' => true, 'message' => 'Keranjang kosong'], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($cartItems as $item) {
                // Cek stok terakhir
                if ($item->saka->stock < $item->quantity) {
                    throw new \Exception("Stok {$item->saka->name} habis/kurang.");
                }

                // Kurangi Stok Produk
                $item->saka->decrement('stock', $item->quantity);

                // Buat Transaksi
                Transaction::create([
                    'user_id' => $user->id,
                    'saka_id' => $item->saka_id,
                    'quantity' => $item->quantity,
                    'total_price' => $item->saka->price * $item->quantity,
                    'status' => 'DIPROSES',
                    'current_location' => 'Diproses Penjual',
                    'payment_method' => $request->payment_method
                ]);
            }

            // Kosongkan Keranjang setelah sukses semua
            Cart::where('user_id', $user->id)->delete();

            DB::commit();
            return response()->json(['error' => false, 'message' => 'Checkout Berhasil! Pesanan diproses.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => true, 'message' => $e->getMessage()], 400);
        }
    }

        public function store(Request $request) {
        $user = $request->user();

        // 1. Validasi data (Samakan dengan field yang dikirim dari Logcat Android Anda)
        $request->validate([
            'saka_id' => 'required',
            'quantity' => 'required|integer',
            'payment_method' => 'required|string',
            'full_address' => 'required|string',
            'subtotal' => 'required|integer',
            'total_price' => 'required|integer',
            'shipping_method' => 'required|string',
        ]);

        try {
            // 2. Simpan Transaksi
            // Pastikan kolom-kolom ini sudah ada di migration transactions Anda!
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'saka_id' => $request->saka_id,
                'quantity' => $request->quantity,
                'payment_method' => $request->payment_method,
                'full_address' => $request->full_address,
                'subtotal' => $request->subtotal,
                'total_price' => $request->total_price,
                'shipping_method' => $request->shipping_method,
                'status' => 'processed',
                'current_location' => 'Diproses Penjual'
            ]);

            // 3. Hapus item dari Cart setelah transaksi berhasil
            \App\Models\Cart::where('user_id', $user->id)
                            ->where('saka_id', $request->saka_id)
                            ->delete();

            // 4. Logika Notifikasi (Bungkus dengan TRY-CATCH agar jika notif gagal, transaksi tetap sukses)
            try {
                $product = \App\Models\Saka::find($request->saka_id);
                if ($product && $product->user && $product->user->fcm_token) {
                    $messaging = app('firebase.messaging');
                    $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $product->user->fcm_token)
                        ->withNotification(\Kreait\Firebase\Messaging\Notification::create(
                            'Pesanan Baru!', 
                            'Ada pesanan masuk untuk ' . $product->name
                        ));
                    $messaging->send($message);
                }
            } catch (\Exception $e) {
                // Biarkan saja jika notif gagal, yang penting transaksi masuk
                \Log::error("FCM Error: " . $e->getMessage());
            }

            return response()->json([
                'error' => false, 
                'message' => 'Pesanan berhasil dibuat!', 
                'transaction' => $transaction
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Gagal menyimpan transaksi: ' . $e->getMessage()
            ], 500);
        }
    }
}