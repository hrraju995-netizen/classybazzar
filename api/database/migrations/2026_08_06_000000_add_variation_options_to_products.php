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
            $table->json('variation_options')->nullable()->after('product_type');
        });

        $products = DB::table('products')->where('product_type', 'variable')->get(['id']);

        foreach ($products as $product) {
            $groups = [];
            $variants = DB::table('product_variants')
                ->where('product_id', $product->id)
                ->orderBy('position')
                ->orderBy('id')
                ->get(['id', 'attributes']);

            foreach ($variants as $variant) {
                $attributes = json_decode($variant->attributes ?: '[]', true) ?: [];
                $normalized = [];

                foreach ($attributes as $name => $value) {
                    $name = Str::headline(trim((string) $name));
                    $value = trim((string) $value);

                    if ($name === '' || $value === '') {
                        continue;
                    }

                    $normalized[$name] = $value;
                    $key = mb_strtolower($name);
                    $groups[$key] ??= ['name' => $name, 'values' => []];

                    if (! collect($groups[$key]['values'])->contains(fn ($existing) => mb_strtolower($existing) === mb_strtolower($value))) {
                        $groups[$key]['values'][] = $value;
                    }
                }

                DB::table('product_variants')->where('id', $variant->id)->update([
                    'attributes' => json_encode($normalized),
                ]);
            }

            DB::table('products')->where('id', $product->id)->update([
                'variation_options' => json_encode(array_values($groups)),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('variation_options');
        });
    }
};
