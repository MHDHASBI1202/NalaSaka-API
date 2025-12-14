<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sakas', function (Blueprint $table) {
            // Tambahkan kolom user_id setelah id
            $table->unsignedBigInteger('user_id')->after('id')->nullable();
            
            // Jadikan foreign key (opsional tapi disarankan)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('sakas', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};