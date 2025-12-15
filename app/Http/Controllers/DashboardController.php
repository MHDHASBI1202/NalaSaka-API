<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Saka;

class DashboardController extends Controller
{
    public function sellerStats(Request $request)
    {
        $user = $request->user();

        // 1. Hitung Pendapatan (Total harga transaksi yang statusnya SELESAI/DIKIRIM/DIPROSES milik produk user ini)
        // Kita gunakan whereHas untuk mencari transaksi yang saka_id-nya milik user yang login
        $revenue = Transaction::whereHas('saka', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->whereIn('status', ['SELESAI', 'DIKIRIM', 'DIPROSES'])->sum('total_price');

        // 2. Hitung Jumlah Terjual (Total quantity)
        $soldItems = Transaction::whereHas('saka', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->whereIn('status', ['SELESAI', 'DIKIRIM', 'DIPROSES'])->sum('quantity');

        // 3. Hitung Total Produk (Stok jenis barang)
        $totalProducts = Saka::where('user_id', $user->id)->count();

        return response()->json([
            'error' => false,
            'message' => 'Statistik berhasil dimuat',
            'stats' => [
                'revenue' => (int) $revenue,
                'sold' => (int) $soldItems,
                'product_count' => (int) $totalProducts
            ]
        ]);
    }
}