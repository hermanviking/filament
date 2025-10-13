<?php
// database/migrations/2025_10_13_170000_add_webhook_meta_to_visma_settings.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('visma_settings', function (Blueprint $t) {
            if (!Schema::hasColumn('visma_settings', 'finance_webhook_subscription_id')) {
                $t->string('finance_webhook_subscription_id')->nullable();
            }
            if (!Schema::hasColumn('visma_settings', 'finance_webhook_shared_secret')) {
                $t->string('finance_webhook_shared_secret')->nullable();
            }
        });
    }
    public function down(): void
    {
        Schema::table('visma_settings', function (Blueprint $t) {
            // optional: $t->dropColumn(['finance_webhook_subscription_id', 'finance_webhook_shared_secret']);
        });
    }
};
