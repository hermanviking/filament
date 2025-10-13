<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            // Core/meta
            if (!Schema::hasColumn('customers','status')) $t->string('status')->nullable()->after('name');
            if (!Schema::hasColumn('customers','customer_class_id')) $t->string('customer_class_id')->nullable();
            if (!Schema::hasColumn('customers','customer_class_description')) $t->string('customer_class_description')->nullable();
            if (!Schema::hasColumn('customers','price_class_id')) $t->string('price_class_id')->nullable();
            if (!Schema::hasColumn('customers','price_class_description')) $t->string('price_class_description')->nullable();
            if (!Schema::hasColumn('customers','currency_id')) $t->string('currency_id')->nullable();
            if (!Schema::hasColumn('customers','language_id')) $t->string('language_id')->nullable();
            if (!Schema::hasColumn('customers','sales_person_id')) $t->string('sales_person_id')->nullable();
            if (!Schema::hasColumn('customers','branch_id')) $t->string('branch_id')->nullable();
            if (!Schema::hasColumn('customers','default_location_id')) $t->string('default_location_id')->nullable();

            // Finance
            if (!Schema::hasColumn('customers','credit_limit')) $t->decimal('credit_limit', 15, 2)->nullable();
            if (!Schema::hasColumn('customers','credit_hold')) $t->boolean('credit_hold')->default(false);
            if (!Schema::hasColumn('customers','balance')) $t->decimal('balance', 15, 2)->nullable();
            if (!Schema::hasColumn('customers','overdue_balance')) $t->decimal('overdue_balance', 15, 2)->nullable();
            if (!Schema::hasColumn('customers','terms_id')) $t->string('terms_id')->nullable();
            if (!Schema::hasColumn('customers','payment_method_id')) $t->string('payment_method_id')->nullable();
            if (!Schema::hasColumn('customers','cash_discount_id')) $t->string('cash_discount_id')->nullable();

            // Tax
            if (!Schema::hasColumn('customers','tax_zone_id')) $t->string('tax_zone_id')->nullable();
            if (!Schema::hasColumn('customers','vat_code_id')) $t->string('vat_code_id')->nullable();
            if (!Schema::hasColumn('customers','vat_registration_id')) $t->string('vat_registration_id')->nullable();
            if (!Schema::hasColumn('customers','vat_exempt')) $t->boolean('vat_exempt')->nullable();

            // Shipping
            if (!Schema::hasColumn('customers','ship_via_id')) $t->string('ship_via_id')->nullable();
            if (!Schema::hasColumn('customers','delivery_terms_id')) $t->string('delivery_terms_id')->nullable();

            // E-invoice / EDI
            if (!Schema::hasColumn('customers','einvoice_participant_id')) $t->string('einvoice_participant_id')->nullable();
            if (!Schema::hasColumn('customers','einvoice_address')) $t->string('einvoice_address')->nullable();
            if (!Schema::hasColumn('customers','einvoice_operator')) $t->string('einvoice_operator')->nullable();
            if (!Schema::hasColumn('customers','edoc_email')) $t->string('edoc_email')->nullable();
            if (!Schema::hasColumn('customers','edoc_enabled')) $t->boolean('edoc_enabled')->nullable();

            // Extra flattened address/contact fields
            if (!Schema::hasColumn('customers','main_address_line2')) $t->string('main_address_line2')->nullable();
            if (!Schema::hasColumn('customers','main_country_id')) $t->string('main_country_id')->nullable();
            if (!Schema::hasColumn('customers','invoice_address_line2')) $t->string('invoice_address_line2')->nullable();
            if (!Schema::hasColumn('customers','invoice_country_id')) $t->string('invoice_country_id')->nullable();
            if (!Schema::hasColumn('customers','delivery_address_line2')) $t->string('delivery_address_line2')->nullable();
            if (!Schema::hasColumn('customers','delivery_country_id')) $t->string('delivery_country_id')->nullable();
            if (!Schema::hasColumn('customers','main_contact_phone2')) $t->string('main_contact_phone2')->nullable();

            // JSON blobs
            if (!Schema::hasColumn('customers','payment_settings')) $t->json('payment_settings')->nullable();
            if (!Schema::hasColumn('customers','financial_information')) $t->json('financial_information')->nullable();
            if (!Schema::hasColumn('customers','attributes_data')) $t->json('attributes_data')->nullable();
            if (!Schema::hasColumn('customers','main_address_json')) $t->json('main_address_json')->nullable();
            if (!Schema::hasColumn('customers','invoice_address_json')) $t->json('invoice_address_json')->nullable();
            if (!Schema::hasColumn('customers','delivery_address_json')) $t->json('delivery_address_json')->nullable();
            if (!Schema::hasColumn('customers','main_contact_json')) $t->json('main_contact_json')->nullable();
            if (!Schema::hasColumn('customers','invoice_contact_json')) $t->json('invoice_contact_json')->nullable();
            if (!Schema::hasColumn('customers','delivery_contact_json')) $t->json('delivery_contact_json')->nullable();
            if (!Schema::hasColumn('customers','custom_fields')) $t->json('custom_fields')->nullable();
            if (!Schema::hasColumn('customers','raw_payload')) $t->json('raw_payload')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            // Optional: drop columns here if you want to fully rollback.
        });
    }
};