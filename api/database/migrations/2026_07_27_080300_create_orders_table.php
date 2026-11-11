<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('customer_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('address');
            $table->enum('shipping_area', ['inside', 'outside'])->default('inside');

            // All money columns are in poisha (1 BDT = 100 poisha).
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('shipping_charge');
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('total');

            $table->string('coupon_code')->nullable();
            $table->enum('payment_method', ['cod', 'bkash', 'nagad', 'sslcommerz'])->default('cod');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('transaction_id')->nullable();

            $table->enum('status', [
                'pending', 'confirmed', 'processing', 'in_transit',
                'out_for_delivery', 'delivered', 'cancelled', 'returned',
            ])->default('pending');

            $table->string('carrier')->nullable();
            $table->string('tracking_code')->nullable();
            $table->date('estimated_delivery_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
