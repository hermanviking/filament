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
        Schema::create('visma_orders', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            // Identity
            $table->string('order_id')->unique();   // API: orderId
            $table->string('type')->nullable();     // API: type
            $table->string('status')->nullable();   // API: status

            // Dates
            $table->timestamp('date')->nullable();                    // date
            $table->timestamp('shipping_scheduled_date')->nullable(); // shippingScheduledDate
            $table->timestamp('request_on')->nullable();              // requestOn
            $table->timestamp('last_modified')->nullable();           // lastModified
            $table->timestamp('cancel_by')->nullable();               // cancelBy

            // Customer
            $table->string('customer_id')->nullable();   // customer.id or customerId
            $table->string('customer_name')->nullable(); // customer.name or customerName

            // Money-ish
            $table->decimal('order_total', 18, 4)->nullable(); // orderTotal or totals.orderTotal
            $table->decimal('tax_total', 18, 4)->nullable();   // taxTotal or totals.taxTotal
            $table->string('currency', 8)->nullable();         // currency

            // Misc top-level
            $table->string('location')->nullable();
            $table->string('customer_order')->nullable();
            $table->string('customer_ref_no')->nullable();
            $table->string('description')->nullable();
            $table->boolean('emailed')->nullable();

            // JSON blobs (keep rich structures for later)
            $table->json('parent_customer')->nullable();
            $table->json('branch')->nullable();
            $table->json('project')->nullable();
            $table->json('print')->nullable();
            $table->json('billing')->nullable();
            $table->json('payment_settings')->nullable();
            $table->json('financial_information')->nullable();
            $table->json('owner')->nullable();
            $table->json('origin')->nullable();
            $table->json('shipping')->nullable();
            $table->json('status_details')->nullable();
            $table->json('customer_block')->nullable(); // the nested "customer" object
            $table->json('totals')->nullable();
            $table->json('freight')->nullable();
            $table->json('sales_person')->nullable();
            $table->json('attachments')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('rot_rut')->nullable();
            $table->json('commissions')->nullable();
            $table->json('tax')->nullable();
            $table->json('shipment')->nullable();
            $table->json('discounts')->nullable();
            $table->json('payments')->nullable();

            // Entire raw entry (debug/auditing)
            $table->json('raw_payload')->nullable();

            $table->softDeletes();

            $table->index(['status', 'last_modified']);
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visma_orders');
    }
};
