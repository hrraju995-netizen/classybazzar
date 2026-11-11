<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_wholesale')->default(false)->after('discount_percentage')->index();
            $table->unsignedBigInteger('wholesale_price')->nullable()->after('is_wholesale');
            $table->unsignedInteger('wholesale_min_quantity')->default(10)->after('wholesale_price');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->unsignedBigInteger('wholesale_price')->nullable()->after('price');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('pricing_type', 20)->default('retail')->after('price')->index();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['pricing_type']);
            $table->dropColumn('pricing_type');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('wholesale_price');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_wholesale']);
            $table->dropColumn(['is_wholesale', 'wholesale_price', 'wholesale_min_quantity']);
        });
    }
};
