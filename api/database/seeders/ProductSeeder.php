<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Prices below are written in taka for readability and converted to
     * poisha on insert.
     *
     * @var array<string, array<int, array{0: string, 1: string, 2: float, 3: float, 4: int}>>
     */
    protected array $catalogue = [
        'mobile-gadgets' => [
            ['Xiaomi Redmi Note 14 (8/256GB)', 'Xiaomi', 26999, 8, 40],
            ['Samsung Galaxy A16 5G', 'Samsung', 23499, 5, 32],
            ['Realme C75 (6/128GB)', 'Realme', 18990, 12, 55],
            ['Anker Soundcore Life P3 Earbuds', 'Anker', 6490, 15, 70],
            ['Baseus 20000mAh Power Bank', 'Baseus', 3250, 10, 120],
            ['Xiaomi Smart Band 9', 'Xiaomi', 4890, 7, 85],
            ['UGREEN 65W GaN Charger', 'UGREEN', 3990, 0, 60],
        ],
        'electronics' => [
            ['Walton 43" Smart Android TV', 'Walton', 38500, 10, 14],
            ['Vision 1.5 Ton Inverter AC', 'Vision', 62900, 6, 8],
            ['Logitech MX Master 3S Mouse', 'Logitech', 12800, 5, 25],
            ['Havit HV-KB395L Mechanical Keyboard', 'Havit', 4200, 12, 45],
            ['JBL Go 4 Portable Speaker', 'JBL', 5290, 8, 38],
            ['TP-Link Archer C6 Router', 'TP-Link', 3150, 0, 52],
        ],
        'fashion' => [
            ['Premium Cotton Panjabi', 'Aarong', 2890, 15, 60],
            ['Half Sleeve Polo Shirt', 'Ecstasy', 1190, 20, 95],
            ['Slim Fit Denim Jeans', 'Levis', 3490, 10, 42],
            ['Handloom Jamdani Saree', 'Tangail Loom', 6500, 5, 18],
            ['Leather Formal Shoes', 'Bata', 3950, 12, 30],
            ['Unisex Sports Backpack', 'Nike', 2790, 18, 48],
        ],
        'home-kitchen' => [
            ['Miyako Rice Cooker 1.8L', 'Miyako', 3290, 10, 40],
            ['Nonstick Cookware Set (5 pcs)', 'Kiam', 4590, 15, 26],
            ['Vision Blender 2-in-1', 'Vision', 2450, 8, 55],
            ['Stainless Steel Water Bottle 1L', 'Lock&Lock', 890, 0, 130],
            ['Cotton Bedsheet Set (King)', 'Home Tex', 2190, 12, 44],
        ],
        'beauty-personal-care' => [
            ['Cetaphil Gentle Skin Cleanser 250ml', 'Cetaphil', 1650, 5, 70],
            ['The Ordinary Niacinamide 10%', 'The Ordinary', 1290, 0, 88],
            ['Himalaya Neem Face Wash 150ml', 'Himalaya', 420, 10, 200],
            ['Philips Beard Trimmer BT3211', 'Philips', 2790, 12, 36],
        ],
        'groceries' => [
            ['Teer Premium Atta 2kg', 'Teer', 145, 0, 300],
            ['Fresh Soybean Oil 5L', 'Fresh', 890, 3, 180],
            ['Radhuni Mixed Spice Combo', 'Radhuni', 320, 8, 150],
            ['Ispahani Mirzapore Tea 400g', 'Ispahani', 260, 0, 220],
        ],
        'sports-outdoor' => [
            ['Yonex Badminton Racket ZR100', 'Yonex', 2190, 10, 40],
            ['Adjustable Dumbbell Set 20kg', 'Nivia', 4890, 15, 22],
            ['Yoga Mat 6mm Anti-Slip', 'Decathlon', 1290, 5, 65],
        ],
        'baby-toys' => [
            ['Huggies Wonder Pants M (48 pcs)', 'Huggies', 1450, 8, 90],
            ['Wooden Building Blocks 100 pcs', 'Melissa & Doug', 1890, 12, 35],
            ['Baby Stroller Foldable', 'Baby Care', 7900, 10, 12],
        ],
        'books-stationery' => [
            ['Atomic Habits (English)', 'Penguin', 620, 15, 110],
            ['Himu Somogro - Humayun Ahmed', 'Onnoprokash', 950, 10, 75],
            ['Faber-Castell Colour Pencil 24', 'Faber-Castell', 480, 5, 140],
        ],
        'health-wellness' => [
            ['Omron Digital BP Monitor', 'Omron', 4290, 8, 28],
            ['Beurer Digital Weight Scale', 'Beurer', 2890, 10, 33],
            ['Whey Protein Isolate 2lbs', 'ON', 6900, 12, 20],
        ],
        'furniture' => [
            ['Ergonomic Office Chair', 'Regal', 12900, 15, 16],
            ['Study Table with Drawer', 'Hatil', 9800, 10, 11],
            ['3-Seater Fabric Sofa', 'Otobi', 32500, 8, 6],
        ],
    ];

    protected array $reviewers = [
        'Rakib Hasan', 'Nusrat Jahan', 'Tanvir Ahmed', 'Sadia Islam',
        'Mahmudul Karim', 'Farhana Akter', 'Imran Hossain', 'Sumaiya Rahman',
    ];

    public function run(): void
    {
        $categories = Category::query()->pluck('id', 'slug');
        $counter = 0;

        foreach ($this->catalogue as $slug => $products) {
            $categoryId = $categories[$slug] ?? null;

            if (! $categoryId) {
                continue;
            }

            foreach ($products as [$title, $brand, $price, $discount, $stock]) {
                $counter++;
                $seed = Str::slug($title);

                $product = Product::updateOrCreate(
                    ['sku' => 'CB-'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT)],
                    [
                        'category_id' => $categoryId,
                        'title' => $title,
                        'slug' => Str::slug($title),
                        'description' => $this->description($title, $brand),
                        'brand' => $brand,
                        'price' => Money::toPoisha($price),
                        'discount_percentage' => $discount,
                        'stock' => $stock,
                        'thumbnail' => "https://picsum.photos/seed/{$seed}/600/600",
                        'images' => [
                            "https://picsum.photos/seed/{$seed}-1/800/800",
                            "https://picsum.photos/seed/{$seed}-2/800/800",
                            "https://picsum.photos/seed/{$seed}-3/800/800",
                        ],
                        'is_active' => true,
                        // Roughly every third product is promoted somewhere.
                        'is_featured' => $counter % 3 === 0,
                        'is_flash_sale' => $discount >= 12,
                        'flash_sale_ends_at' => $discount >= 12 ? now()->addDays(3) : null,
                        'sold_count' => random_int(0, 400),
                    ]
                );

                $this->seedReviews($product);
            }
        }
    }

    protected function seedReviews(Product $product): void
    {
        if ($product->reviews()->exists()) {
            return;
        }

        $count = random_int(2, 5);
        $comments = [
            'Product quality onek valo, delivery o fast chilo.',
            'Exactly as described. Packaging was secure.',
            'Value for money. Would order again from Classybazar.',
            'Delivery took a day longer than expected but the product is fine.',
            'Great build quality for this price range.',
        ];

        for ($i = 0; $i < $count; $i++) {
            Review::create([
                'product_id' => $product->id,
                'reviewer_name' => $this->reviewers[array_rand($this->reviewers)],
                'rating' => random_int(3, 5),
                'comment' => $comments[array_rand($comments)],
                'is_approved' => true,
                'created_at' => now()->subDays(random_int(1, 90)),
            ]);
        }

        $stats = $product->reviews()->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')->first();

        $product->update([
            'rating' => round((float) $stats->avg_rating, 2),
            'reviews_count' => (int) $stats->total,
        ]);
    }

    protected function description(string $title, string $brand): string
    {
        return "{$title} from {$brand}. 100% authentic product sourced through "
            .'authorised channels, with nationwide cash-on-delivery across Bangladesh. '
            .'Inside Dhaka delivery within 1-2 working days, outside Dhaka within 3-4 '
            .'working days. 7-day easy return if the item arrives damaged or incorrect.';
    }
}
