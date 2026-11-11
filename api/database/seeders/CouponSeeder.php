<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Support\Money;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::updateOrCreate(['code' => 'CLASSY10'], [
            'type' => 'percent',
            'value' => 10,
            'min_order_amount' => Money::toPoisha(1000),
            'max_discount_amount' => Money::toPoisha(500),
            'usage_limit' => 500,
            'expires_at' => now()->addMonths(3),
            'is_active' => true,
        ]);

        Coupon::updateOrCreate(['code' => 'FREESHIP'], [
            'type' => 'fixed',
            'value' => 70,
            'min_order_amount' => Money::toPoisha(2000),
            'usage_limit' => null,
            'expires_at' => now()->addMonths(6),
            'is_active' => true,
        ]);
    }
}
