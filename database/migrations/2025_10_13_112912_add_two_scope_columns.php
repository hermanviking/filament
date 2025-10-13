<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('visma_settings', function (Blueprint $t) {
            if (!Schema::hasColumn('visma_settings', 'finance_scope')) {
                $t->string('finance_scope')->nullable(); // e.g. vismanet_erp_service_api:read
            }
            if (!Schema::hasColumn('visma_settings', 'salesorder_scope')) {
                $t->string('salesorder_scope')->nullable(); // e.g. visma.net.erp.salesorder:read visma.net.erp.salesorder:write
            }
        });
    }

    public function down(): void
    {
        Schema::table('visma_settings', function (Blueprint $t) {
            // optional: drop columns on rollback
            // $t->dropColumn(['finance_scope','salesorder_scope']);
        });
    }
};
