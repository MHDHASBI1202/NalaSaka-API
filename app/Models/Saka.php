<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Saka extends Model
{
    use HasFactory;
    
    protected $table = 'sakas';
    
    protected $fillable = ['user_id', 'name', 'category', 'description', 'price', 'discount_price', 'stock', 'photo_url'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}