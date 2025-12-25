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
            $table->unsignedBigInteger('user_id'); 
            
            $table->unsignedBigInteger('saka_id'); 

            $table->integer('quantity')->default(1);
            $table->integer('total_price');
            
            $table->string('status')->default('PENDING');
            
            $table->string('current_location')->nullable();
            
            $table->string('resi_number')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};