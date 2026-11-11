<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        // Money settings are stored in poisha, matching every other money
        // column in the schema. 7000 poisha = 70 BDT.
        $settings = [
            'site.name' => 'Classybazar',
            'site.currency' => 'BDT',
            'site.currency_symbol' => '৳',
            'site.support_phone' => '+8801700000000',
            'site.support_email' => 'support@classybazar.com',
            'shipping.inside_dhaka' => 7000,
            'shipping.outside_dhaka' => 13000,
            'shipping.free_above' => 0,
            'order.prefix' => 'CB',
            'payment.methods' => ['cod'],
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
