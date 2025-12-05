<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Saka extends Model
{
    use HasFactory;
    
    // Nama tabel di database
    protected $table = 'sakas';
    
    // Kolom yang boleh diisi
    protected $fillable = ['name', 'description', 'price', 'photo_url'];
}