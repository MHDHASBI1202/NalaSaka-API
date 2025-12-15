<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Status: 'none', 'pending', 'verified', 'rejected'
            $table->string('verification_status')->default('none')->after('store_name');
            $table->string('certification_url')->nullable()->after('verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['verification_status', 'certification_url']);
        });
    }
};