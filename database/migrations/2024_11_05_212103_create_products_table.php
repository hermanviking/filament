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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique(); // SKU is unique for each product
            $table->string('name'); // This will map to title
            $table->decimal('price', 8, 2);
            $table->text('description'); // Use text for potentially long descriptions
            $table->string('category'); // New column for category
            $table->string('image'); // New column for image URL
            $table->decimal('rating_rate', 3, 2)->nullable(); // New column for rating rate
            $table->integer('rating_count')->nullable(); // New column for rating count
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
