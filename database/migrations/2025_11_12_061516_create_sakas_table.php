<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sakas', function (Blueprint $table) {
        $table->id();
        $table->string('name');            
        $table->text('description');       
        $table->string('photo_url');        
        $table->integer('price');          
        $table->timestamps();
    });
    }

    public function down(): void
    {
        Schema::dropIfExists('sakas');
    }
};
