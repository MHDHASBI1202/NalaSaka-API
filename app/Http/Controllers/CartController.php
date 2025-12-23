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
        
        $carts = Cart::where('user_id', $user->id)->get();

        $data = $carts->map(function ($item) {
            $saka = $item->saka;
            $seller = $saka ? $saka->user : null;
            $store = $seller ? \DB::table('stores')->where('user_id', $seller->id)->first() : null;
            return [
                'cart_id' => $item->id,
                'saka_id' => $item->saka_id,
                'name' => $item->saka->name,
                'price' => $item->saka->price,
                'photo_url' => $item->saka->photo_url,
                'quantity' => $item->quantity,
                'stock_available' => $item->saka->stock,
                'store_name' => $item->saka->user->store_name,
                'storeAddress' => $store ? $store->address : 'Alamat belum diatur',
                'latitude' => $store ? (double)$store->latitude : 0.0,
                'longitude' => $store ? (double)$store->longitude : 0.0,
                
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
}