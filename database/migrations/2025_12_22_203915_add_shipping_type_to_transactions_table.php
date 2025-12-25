<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'shipping_type')) {
                $table->string('shipping_type')->default('Diantar')->after('status');
            }
            
            if (!Schema::hasColumn('transactions', 'pickup_code')) {
                $table->string('pickup_code')->nullable()->after('shipping_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['shipping_type', 'pickup_code']);
        });
    }
};
