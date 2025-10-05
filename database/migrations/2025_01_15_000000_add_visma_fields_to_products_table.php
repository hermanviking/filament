<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('inventory_id')->nullable()->after('id');
            $table->string('status')->nullable()->after('inventory_id');
            $table->string('product_type')->nullable()->after('status');
            $table->longText('body')->nullable()->after('description');
            $table->string('brand')->nullable()->after('body');
            $table->string('short_description')->nullable()->after('brand');
            $table->string('volume')->nullable()->after('short_description');
            $table->string('color_code')->nullable()->after('volume');
            $table->boolean('is_hazardous')->default(false)->after('color_code');
            $table->boolean('is_display_only')->default(false)->after('is_hazardous');
            $table->boolean('is_parent')->default(false)->after('is_display_only');
            $table->string('kasselov_code')->nullable()->after('is_parent');
            $table->boolean('is_web_item')->default(false)->after('kasselov_code');
            $table->boolean('is_web_item_b2b')->default(false)->after('is_web_item');
            $table->boolean('is_web_item_b2c')->default(false)->after('is_web_item_b2b');
            $table->boolean('stock_item')->default(false)->after('is_web_item_b2c');
            $table->boolean('kit_item')->default(false)->after('stock_item');
            $table->string('base_unit')->nullable()->after('kit_item');
            $table->string('sales_unit')->nullable()->after('base_unit');
            $table->string('purchase_unit')->nullable()->after('sales_unit');
            $table->string('default_warehouse_id')->nullable()->after('purchase_unit');
            $table->string('default_issue_from')->nullable()->after('default_warehouse_id');
            $table->string('default_receipt_to')->nullable()->after('default_issue_from');
            $table->decimal('weight', 12, 4)->nullable()->after('default_receipt_to');
            $table->string('weight_uom')->nullable()->after('weight');
            $table->decimal('volume_value', 12, 4)->nullable()->after('weight_uom');
            $table->string('volume_uom')->nullable()->after('volume_value');
            $table->string('country_of_origin')->nullable()->after('volume_uom');
            $table->string('supplementary_measure_unit')->nullable()->after('country_of_origin');
            $table->decimal('current_cost', 12, 2)->nullable()->after('supplementary_measure_unit');
            $table->decimal('last_cost', 12, 2)->nullable()->after('current_cost');
            $table->decimal('recommended_price', 12, 2)->nullable()->after('last_cost');
            $table->string('vat_code_id')->nullable()->after('recommended_price');
            $table->string('vat_code_description')->nullable()->after('vat_code_id');
            $table->string('item_class_id')->nullable()->after('category');
            $table->string('item_class_description')->nullable()->after('item_class_id');
            $table->string('price_class_id')->nullable()->after('item_price_class_id');
            $table->string('price_class_description')->nullable()->after('price_class_id');
            $table->json('attributes_data')->nullable()->after('rating_count');
            $table->json('warehouse_details')->nullable()->after('attributes_data');
            $table->json('cross_references')->nullable()->after('warehouse_details');
            $table->decimal('quantity_on_hand', 12, 2)->nullable()->after('cross_references');
            $table->decimal('quantity_available', 12, 2)->nullable()->after('quantity_on_hand');
            $table->decimal('quantity_available_for_shipment', 12, 2)->nullable()->after('quantity_available');
            $table->timestamp('last_modified_at')->nullable()->after('quantity_available_for_shipment');
            $table->string('visma_timestamp')->nullable()->after('last_modified_at');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('inventory_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['inventory_id']);
            $table->dropColumn([
                'inventory_id',
                'status',
                'product_type',
                'body',
                'brand',
                'short_description',
                'volume',
                'color_code',
                'is_hazardous',
                'is_display_only',
                'is_parent',
                'kasselov_code',
                'is_web_item',
                'is_web_item_b2b',
                'is_web_item_b2c',
                'stock_item',
                'kit_item',
                'base_unit',
                'sales_unit',
                'purchase_unit',
                'default_warehouse_id',
                'default_issue_from',
                'default_receipt_to',
                'weight',
                'weight_uom',
                'volume_value',
                'volume_uom',
                'country_of_origin',
                'supplementary_measure_unit',
                'current_cost',
                'last_cost',
                'recommended_price',
                'vat_code_id',
                'vat_code_description',
                'item_class_id',
                'item_class_description',
                'price_class_id',
                'price_class_description',
                'attributes_data',
                'warehouse_details',
                'cross_references',
                'quantity_on_hand',
                'quantity_available',
                'quantity_available_for_shipment',
                'last_modified_at',
                'visma_timestamp',
            ]);
        });
    }
};
