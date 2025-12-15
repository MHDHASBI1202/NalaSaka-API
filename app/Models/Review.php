<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $table = 'reviews';

    protected $fillable = [
        'user_id',
        'saka_id',
        'rating',
        'comment',
        'image_url'
    ];

    // Relasi: Review milik satu User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi: Review milik satu Produk (Saka)
    public function saka()
    {
        return $this->belongsTo(Saka::class, 'saka_id');
    }
}