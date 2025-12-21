<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('saka_id'); // ID Produk
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('saka_id')->references('id')->on('sakas')->onDelete('cascade');
            
            // Mencegah duplikasi: 1 user hanya punya 1 row untuk produk yg sama (update quantity saja)
            $table->unique(['user_id', 'saka_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};