<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('visma_settings', function (Blueprint $t) {
            if (!Schema::hasColumn('visma_settings', 'finance_webhook_secret')) {
                $t->string('finance_webhook_secret')->nullable();
            }
            if (!Schema::hasColumn('visma_settings', 'sales_webhook_secret')) {
                $t->string('sales_webhook_secret')->nullable();
            }
        });
    }
    public function down(): void
    {
        // optional: $t->dropColumn([...]);
    }
};
