<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'saka_id', 'quantity'];

    public function saka()
    {
        return $this->belongsTo(Saka::class, 'saka_id');
    }
}