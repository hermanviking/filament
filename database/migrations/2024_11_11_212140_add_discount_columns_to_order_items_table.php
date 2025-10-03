<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->default(0)->after('price'); // Discount percentage for each item
            $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_percent'); // Discount amount for each item
            $table->decimal('discounted_price', 10, 2)->default(0)->after('discount_amount'); // Price after discount
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('discount_percent');
            $table->dropColumn('discount_amount');
            $table->dropColumn('discounted_price');
        });
    }
};
