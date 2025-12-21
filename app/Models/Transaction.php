<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'saka_id', 'quantity', 'total_price', 
        'status', 'current_location', 'resi_number',
        'payment_method'
    ];

    // Relasi: Transaksi milik satu Produk (Saka)
    public function saka()
    {
        return $this->belongsTo(Saka::class, 'saka_id');
    }

    // Relasi: Transaksi milik satu User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}