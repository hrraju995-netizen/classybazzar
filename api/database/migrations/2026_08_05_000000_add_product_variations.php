<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type', 20)->default('simple')->after('slug')->index();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->json('attributes');
            $table->unsignedBigInteger('price');
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants')
                ->nullOnDelete();
            $table->string('variant_name')->nullable()->after('title');
            $table->string('variant_sku')->nullable()->after('variant_name');
            $table->json('variant_attributes')->nullable()->after('variant_sku');
        });

        // Existing random slugs become readable title slugs. A numeric URL
        // continues to resolve, while all generated storefront links use this.
        $products = DB::table('products')->orderBy('id')->get(['id', 'title']);

        foreach ($products as $product) {
            DB::table('products')->where('id', $product->id)->update([
                'slug' => '__slug-migration-'.$product->id,
            ]);
        }

        $used = [];
        foreach ($products as $product) {
            $base = Str::slug($product->title) ?: 'product-'.$product->id;
            $slug = $base;
            $suffix = 2;

            while (isset($used[$slug])) {
                $slug = $base.'-'.$suffix++;
            }

            $used[$slug] = true;
            DB::table('products')->where('id', $product->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
            $table->dropColumn(['variant_name', 'variant_sku', 'variant_attributes']);
        });

        Schema::dropIfExists('product_variants');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('product_type');
        });
    }
};
