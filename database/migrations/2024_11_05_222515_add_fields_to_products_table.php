<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToProductsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Adding new fields
            $table->string('description');
            $table->string('category')->after('description');
            $table->string('image')->after('category');
            $table->decimal('rating_rate', 3, 2)->nullable()->after('image');
            $table->integer('rating_count')->nullable()->after('rating_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Dropping the columns if the migration is rolled back
            $table->dropColumn(['category', 'image', 'rating_rate', 'rating_count']);
        });
    }
}
