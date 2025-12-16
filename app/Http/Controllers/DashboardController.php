<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Saka;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function sellerStats(Request $request)
    {
        $user = $request->user();

        // 1. Ringkasan Total (Tetap Sama)
        $revenue = Transaction::whereHas('saka', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->whereIn('status', ['SELESAI', 'DIKIRIM', 'DIPROSES'])->sum('total_price');

        $soldItems = Transaction::whereHas('saka', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->whereIn('status', ['SELESAI', 'DIKIRIM', 'DIPROSES'])->sum('quantity');

        $totalProducts = Saka::where('user_id', $user->id)->count();

        // 2. Data Grafik Harian (Tetap Sama)
        $sevenDaysAgo = Carbon::now()->subDays(6)->startOfDay();
        $dailySalesRaw = Transaction::whereHas('saka', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereIn('status', ['SELESAI', 'DIKIRIM', 'DIPROSES'])
            ->where('created_at', '>=', $sevenDaysAgo)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_price) as total')
            )
            ->groupBy('date')
            ->get();

        $chartData = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $sevenDaysAgo->copy()->addDays($i)->format('Y-m-d');
            $dayName = $sevenDaysAgo->copy()->addDays($i)->format('D');
            $sales = $dailySalesRaw->firstWhere('date', $date);
            $chartData[] = [
                'day' => $dayName,
                'amount' => $sales ? (int)$sales->total : 0
            ];
        }

        // 3. [BARU] Performa Tiap Produk
        // Ambil semua produk user, hitung statistik penjualannya
        $allProducts = Saka::where('user_id', $user->id)->get();
        
        $productPerformance = $allProducts->map(function($saka) {
            // Ambil transaksi valid untuk produk ini
            $validTrans = Transaction::where('saka_id', $saka->id)
                ->whereIn('status', ['SELESAI', 'DIKIRIM', 'DIPROSES'])
                ->get();

            return [
                'name' => $saka->name,
                'image_url' => $saka->photo_url,
                'sold_qty' => (int) $validTrans->sum('quantity'),
                'total_revenue' => (int) $validTrans->sum('total_price')
            ];
        })
        ->sortByDesc('sold_qty') // Urutkan dari yang paling laku
        ->values(); // Reset index array

        return response()->json([
            'error' => false,
            'message' => 'Statistik berhasil dimuat',
            'stats' => [
                'revenue' => (int) $revenue,
                'sold' => (int) $soldItems,
                'product_count' => (int) $totalProducts,
                'daily_sales' => $chartData,
                'product_performance' => $productPerformance // Kirim data baru
            ]
        ]);
    }
}