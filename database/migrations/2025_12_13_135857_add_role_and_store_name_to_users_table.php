<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Menambahkan kolom role dengan default 'customer'
            $table->enum('role', ['customer', 'seller'])->default('customer')->after('password'); 
            
            // Menambahkan kolom store_name, diizinkan null
            $table->string('store_name')->nullable()->after('role')->unique(); 
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('store_name');
            $table->dropColumn('role');
        });
    }
};
