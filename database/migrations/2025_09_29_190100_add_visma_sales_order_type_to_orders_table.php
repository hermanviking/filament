<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'visma_sales_order_type')) {
                $table->string('visma_sales_order_type')->nullable()->after('visma_sales_order_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'visma_sales_order_type')) {
                $table->dropColumn('visma_sales_order_type');
            }
        });
    }
};