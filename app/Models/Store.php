<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model {
    protected $fillable = ['user_id', 'address', 'latitude', 'longitude'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}