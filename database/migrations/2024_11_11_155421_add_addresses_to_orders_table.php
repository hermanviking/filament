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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('invoice_address')->nullable();
    $table->string('invoice_city')->nullable();
    $table->string('invoice_postal_code')->nullable();
    $table->string('delivery_address')->nullable();
    $table->string('delivery_city')->nullable();
    $table->string('delivery_postal_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
