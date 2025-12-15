<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke User (Siapa yang mereview)
            $table->unsignedBigInteger('user_id');
            
            // Relasi ke Produk/Saka (Apa yang direview)
            $table->unsignedBigInteger('saka_id');

            // Rating bintang (1 sampai 5)
            $table->integer('rating');

            // Komentar teks
            $table->text('comment')->nullable();

            // URL Gambar ulasan (Opsional, sesuai fitur Yang Mulia)
            $table->string('image_url')->nullable();

            $table->timestamps();

            // Foreign Key Constraints (Opsional tapi disarankan agar data konsisten)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('saka_id')->references('id')->on('sakas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};