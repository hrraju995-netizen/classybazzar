<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Mobile & Gadgets', 'slug' => 'mobile-gadgets'],
            ['name' => 'Electronics', 'slug' => 'electronics'],
            ['name' => 'Fashion', 'slug' => 'fashion'],
            ['name' => 'Home & Kitchen', 'slug' => 'home-kitchen'],
            ['name' => 'Beauty & Personal Care', 'slug' => 'beauty-personal-care'],
            ['name' => 'Groceries', 'slug' => 'groceries'],
            ['name' => 'Sports & Outdoor', 'slug' => 'sports-outdoor'],
            ['name' => 'Baby & Toys', 'slug' => 'baby-toys'],
            ['name' => 'Books & Stationery', 'slug' => 'books-stationery'],
            ['name' => 'Health & Wellness', 'slug' => 'health-wellness'],
            ['name' => 'Furniture', 'slug' => 'furniture'],
        ];

        foreach ($categories as $index => $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'image' => "https://picsum.photos/seed/{$category['slug']}/400/400",
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
}
