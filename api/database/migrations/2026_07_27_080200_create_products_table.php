<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('brand')->nullable();
            $table->string('sku')->nullable()->unique();

            // Money is stored in poisha (minor units) to avoid float rounding bugs.
            $table->unsignedBigInteger('price');
            $table->decimal('discount_percentage', 5, 2)->default(0);

            $table->unsignedInteger('stock')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedBigInteger('sold_count')->default(0);

            $table->string('thumbnail')->nullable();
            $table->json('images')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_flash_sale')->default(false);
            $table->timestamp('flash_sale_ends_at')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'category_id']);
            $table->index(['is_active', 'is_featured']);
            $table->index(['is_active', 'is_flash_sale']);
            $table->index('price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
