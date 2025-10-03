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
        Schema::table('customers', function (Blueprint $table) {
            // General Address Fields
            $table->string('main_address_line1')->nullable();
            $table->string('main_postal_code')->nullable();
            $table->string('main_city')->nullable();
            $table->string('main_country')->nullable();
            $table->string('main_county')->nullable();

            // Invoice Address Fields
            $table->string('invoice_address_line1')->nullable();
            $table->string('invoice_postal_code')->nullable();
            $table->string('invoice_city')->nullable();
            $table->string('invoice_country')->nullable();
            $table->string('invoice_county')->nullable();

            // Delivery Address Fields
            $table->string('delivery_address_line1')->nullable();
            $table->string('delivery_postal_code')->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_country')->nullable();
            $table->string('delivery_county')->nullable();

            // Contact Fields for Each Address
            $table->string('main_contact_name')->nullable();
            $table->string('main_contact_attention')->nullable();
            $table->string('main_contact_email')->nullable();
            $table->string('main_contact_phone')->nullable();

            $table->string('invoice_contact_name')->nullable();
            $table->string('invoice_contact_attention')->nullable();
            $table->string('invoice_contact_email')->nullable();
            $table->string('invoice_contact_phone')->nullable();

            $table->string('delivery_contact_name')->nullable();
            $table->string('delivery_contact_attention')->nullable();
            $table->string('delivery_contact_email')->nullable();
            $table->string('delivery_contact_phone')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'main_address_line1', 'main_postal_code', 'main_city', 'main_country', 'main_county',
                'invoice_address_line1', 'invoice_postal_code', 'invoice_city', 'invoice_country', 'invoice_county',
                'delivery_address_line1', 'delivery_postal_code', 'delivery_city', 'delivery_country', 'delivery_county',
                'main_contact_name', 'main_contact_attention', 'main_contact_email', 'main_contact_phone',
                'invoice_contact_name', 'invoice_contact_attention', 'invoice_contact_email', 'invoice_contact_phone',
                'delivery_contact_name', 'delivery_contact_attention', 'delivery_contact_email', 'delivery_contact_phone',
            ]);
        });
    }
};
