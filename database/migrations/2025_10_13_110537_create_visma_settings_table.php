<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('visma_settings', function (Blueprint $t) {
            $t->id();

            // Switch
            $t->enum('environment', ['dev', 'live'])->default('dev');

            // Base URLs
            $t->string('dev_finance_base_url')->nullable();   // default: https://api.finance.visma.net/v1
            $t->string('live_finance_base_url')->nullable();
            $t->string('dev_sales_base_url')->nullable();     // default: https://salesorder.visma.net/api/v3
            $t->string('live_sales_base_url')->nullable();

            // Tenant IDs
            $t->string('dev_tenant_id')->nullable();
            $t->string('live_tenant_id')->nullable();

            // OAuth client — keep secrets encrypted
            $t->string('client_id')->nullable();
            $t->text('client_secret')->nullable(); // encrypted cast in model

            // Scopes
            $t->string('scope_read')->nullable();   // default: vismanet_erp_service_api:read
            $t->string('scope_write')->nullable();  // default: vismanet_erp_service_api:read visma.net.erp.salesorder:write

            // Defaults used when creating orders
            $t->string('default_terms_id')->nullable();     // e.g. NET30 or "14"
            $t->string('default_location_id')->nullable();  // e.g. Main
            $t->string('default_currency')->nullable();     // e.g. NOK
            $t->string('default_order_type')->nullable();   // e.g. BB

            // Flags
            $t->boolean('http_debug')->default(false);
            $t->boolean('use_finance_v1')->default(true); // keep for future API versions

            $t->timestamps();
        });

        // Seed a single row with sensible defaults
        DB::table('visma_settings')->insert([
            'environment'           => 'dev',
            'dev_finance_base_url'  => 'https://api.finance.visma.net/v1',
            'live_finance_base_url' => 'https://api.finance.visma.net/v1',
            'dev_sales_base_url'    => 'https://salesorder.visma.net/api/v3',
            'live_sales_base_url'   => 'https://salesorder.visma.net/api/v3',
            'scope_read'            => 'vismanet_erp_service_api:read',
            'scope_write'           => 'vismanet_erp_service_api:read visma.net.erp.salesorder:write',
            'default_currency'      => 'NOK',
            'default_order_type'    => 'BB',
            'http_debug'            => false,
            'use_finance_v1'        => true,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('visma_settings');
    }
};
