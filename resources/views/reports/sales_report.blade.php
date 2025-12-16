<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penjualan</title>
    <style>
        body { font-family: sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .details { margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid black; }
        th, td { padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #f2f2f2; }
        .total { text-align: right; font-weight: bold; margin-top: 10px; }
        .footer { margin-top: 30px; font-size: 10px; text-align: center; color: gray; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Penjualan NalaSaka</h2>
        <p>Toko: {{ $user->store_name }} ({{ $user->name }})</p>
    </div>

    <div class="details">
        <strong>Periode:</strong> {{ $period }}<br>
        <strong>Tanggal Cetak:</strong> {{ $date }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Produk</th>
                <th>Qty</th>
                <th>Harga Satuan</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $index => $trx)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $trx->created_at->format('d/m/Y') }}</td>
                <td>{{ $trx->saka->name ?? 'Produk Dihapus' }}</td>
                <td>{{ $trx->quantity }}</td>
                <td>Rp {{ number_format($trx->saka->price ?? 0, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($trx->total_price, 0, ',', '.') }}</td>
                <td>{{ $trx->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        <p>Total Item Terjual: {{ $totalSold }}</p>
        <p>Total Pendapatan: Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
    </div>

    <div class="footer">
        Dicetak otomatis oleh sistem NalaSaka Mobile App.
    </div>
</body>
</html>