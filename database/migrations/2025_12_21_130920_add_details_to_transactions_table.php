<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void {
    Schema::table('transactions', function (Blueprint $table) {
        $table->decimal('latitude', 10, 8)->nullable();
        $table->decimal('longitude', 11, 8)->nullable();
        $table->string('full_address')->nullable();
        $table->integer('subtotal')->default(0);
        $table->integer('shipping_cost')->default(0);
        $table->integer('total_amount')->default(0);
        $table->string('shipping_method')->nullable();
    });
}
};
