<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sakas', function (Blueprint $table) {
            // Menambahkan kolom stock dengan default 0
            $table->integer('stock')->default(0)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('sakas', function (Blueprint $table) {
            $table->dropColumn('stock');
        });
    }
};