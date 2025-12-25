<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function downloadReport(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user && $request->query('token')) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($request->query('token'));
            if ($accessToken) {
                $user = $accessToken->tokenable;
            }
        }

        if (!$user || $user->role !== 'seller') {
            return response()->json(['message' => 'Unauthorized Access'], 401);
        }

        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $transactions = Transaction::with('saka')
            ->whereHas('saka', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereIn('status', ['SELESAI', 'DIKIRIM', 'DIPROSES'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalRevenue = $transactions->sum('total_price');
        $totalSold = $transactions->sum('quantity');

        $data = [
            'title' => 'Laporan Penjualan NalaSaka',
            'date' => Carbon::now()->format('d F Y'),
            'period' => $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y'),
            'user' => $user,
            'transactions' => $transactions,
            'totalRevenue' => $totalRevenue,
            'totalSold' => $totalSold
        ];

        $pdf = Pdf::loadView('reports.sales_report', $data);
        
        return $pdf->download('laporan_penjualan.pdf');
    }
}