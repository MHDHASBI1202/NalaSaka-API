<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            // Siapa yang beli (User ID)
            $table->unsignedBigInteger('user_id'); 
            
            // Beli apa (Saka/Product ID)
            $table->unsignedBigInteger('saka_id'); 

            $table->integer('quantity')->default(1);
            $table->integer('total_price');
            
            // Status Pesanan: PENDING, DIPROSES, DIKIRIM, SELESAI, BATAL
            $table->string('status')->default('PENDING');
            
            // Pelacakan Lokasi (Misal: "Gudang Padang", "Sedang di Jalan")
            $table->string('current_location')->nullable();
            
            // Nomor Resi (Nanti diisi admin kalau sudah kirim)
            $table->string('resi_number')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};