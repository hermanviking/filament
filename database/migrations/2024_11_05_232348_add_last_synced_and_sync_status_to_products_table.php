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
            $table->timestamp('last_synced')->nullable(); // To store the timestamp of the last sync
            $table->string('sync_status')->nullable();   // To store the sync status (e.g., 'success', 'failed')
        });       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('last_synced');
        $table->dropColumn('sync_status');
        });
    }
};
