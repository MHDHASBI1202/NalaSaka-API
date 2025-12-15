<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sakas', function (Blueprint $table) {
            // Tambah kolom category setelah name
            $table->string('category')->default('Sayur')->after('name'); 
        });
    }

    public function down(): void
    {
        Schema::table('sakas', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};