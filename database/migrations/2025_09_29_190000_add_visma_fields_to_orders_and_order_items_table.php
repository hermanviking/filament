<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('visma_sales_order_number')->nullable()->after('status');
            $table->string('visma_status')->nullable()->after('visma_sales_order_number');
            $table->timestamp('visma_last_synced_at')->nullable()->after('visma_status');
            $table->json('visma_payload')->nullable()->after('visma_last_synced_at');
            $table->index('visma_sales_order_number', 'orders_visma_sales_order_number_index');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('visma_line_number')->nullable()->after('discounted_price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('visma_line_number');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_visma_sales_order_number_index');
            $table->dropColumn([
                'visma_sales_order_number',
                'visma_status',
                'visma_last_synced_at',
                'visma_payload',
            ]);
        });
    }
};
