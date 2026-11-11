<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@classybazar.com'],
            [
                'name' => 'Classybazar Admin',
                'phone' => '01700000000',
                'password' => 'password',
                'is_admin' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'customer@classybazar.com'],
            [
                'name' => 'Demo Customer',
                'phone' => '01812345678',
                'address' => 'House 12, Road 5, Dhanmondi',
                'city' => 'Dhaka',
                'password' => 'password',
                'is_admin' => false,
            ]
        );
    }
}
