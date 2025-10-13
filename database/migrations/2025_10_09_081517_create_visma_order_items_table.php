<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('visma_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('visma_order_id')
                ->constrained('visma_orders')
                ->cascadeOnDelete();

            // Identity
            $table->unsignedBigInteger('line_id')->nullable();   // lineId
            $table->unsignedInteger('sort_order')->nullable();   // sortOrder
            $table->string('line_type')->nullable();             // lineType
            $table->string('operation')->nullable();             // operation

            // Inventory
            $table->string('inventory_id')->nullable();          // inventory.id
            $table->string('inventory_description')->nullable(); // inventory.description
            $table->string('inventory_base_unit')->nullable();   // inventory.baseUnit

            // UoM
            $table->string('unit_of_measure')->nullable();       // unitOfMeasure

            // Quantities / prices
            $table->decimal('quantity', 18, 4)->default(0);
            $table->decimal('base_order_quantity', 18, 4)->nullable();
            $table->decimal('unit_cost', 18, 4)->nullable();
            $table->decimal('unit_price', 18, 4)->nullable();
            $table->decimal('extended_price', 18, 4)->nullable();
            $table->decimal('line_total_before_discount', 18, 4)->nullable();
            $table->decimal('discount_amount', 18, 4)->nullable();
            $table->decimal('discount_percent', 18, 4)->nullable();

            // Dates
            $table->timestamp('order_date')->nullable();
            $table->timestamp('ship_date')->nullable();
            $table->timestamp('request_date')->nullable();

            // Misc text
            $table->text('description')->nullable();
            $table->string('warehouse_id')->nullable();
            $table->string('tax_category_id')->nullable();
            $table->boolean('completed')->nullable();
            $table->boolean('free_item')->nullable();
            $table->boolean('open_line')->nullable();

            // JSON blobs for the rest
            $table->json('branch')->nullable();
            $table->json('shipping_rule')->nullable();
            $table->json('reason_code')->nullable();
            $table->json('warehouse_location')->nullable();
            $table->json('sales_person')->nullable();
            $table->json('supplier')->nullable();
            $table->json('attachments')->nullable();
            $table->json('allocations')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->index(['visma_order_id', 'line_id']);
            $table->index('inventory_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visma_order_items');
    }
};
