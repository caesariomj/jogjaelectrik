<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'subcategory_id' => \App\Models\Subcategory::factory(),
            'name' => ucwords($name),
            'slug' => \Illuminate\Support\Str::slug($name),
            'description' => $this->faker->text(),
            'main_sku' => str_replace(' ', '-', $name),
            'base_price' => $this->faker->randomFloat(2, 1000, 1000000),
            'base_price_discount' => null,
            'is_active' => true,
            'warranty' => '1 tahun garansi toko',
            'material' => 'Plastik',
            'dimension' => '100x100x100',
            'package' => '1x unit',
            'weight' => $this->faker->numberBetween(1, 29999),
            'power' => $this->faker->numberBetween(1, 1000),
            'voltage' => '220-240',
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function ($product) {
            // 1. Buat ProductVariants
            $productVariants = \App\Models\ProductVariant::factory()
                ->count(fake()->numberBetween(2, 3))
                ->for($product)
                ->create();

            // 2. Buat Variations (tanpa relasi ke product)
            $variations = \App\Models\Variation::factory()
                ->count(fake()->numberBetween(1, 2))
                ->create();

            // 3. Buat VariationVariants untuk setiap Variation
            $variationVariants = collect();
            foreach ($variations as $variation) {
                $vv = \App\Models\VariationVariant::factory()
                    ->count(fake()->numberBetween(2, 3)) // Minimal 2 per variasi agar logis
                    ->for($variation)
                    ->create();

                $variationVariants = $variationVariants->merge($vv);
            }

            // 4. Buat VariantCombination: pasangkan ProductVariant × VariationVariant
            foreach ($productVariants as $pv) {
                // Ambil acak 1–2 VariationVariant untuk tiap varian
                $sampledVariants = $variationVariants->random(fake()->numberBetween(1, 2));

                foreach ($sampledVariants as $vv) {
                    \App\Models\VariantCombination::factory()->create([
                        'product_variant_id' => $pv->id,
                        'variation_variant_id' => $vv->id,
                    ]);
                }
            }

            // 5. Hitung base_price dan base_price_discount dari ProductVariant
            $activeVariants = $productVariants->filter(fn ($v) => $v->is_active);

            if ($activeVariants->isEmpty()) {
                $variant = $productVariants->first();
                if ($variant) {
                    $product->update([
                        'base_price' => $variant->price,
                        'base_price_discount' => $variant->price_discount,
                    ]);
                }

                return;
            }

            $discountedVariant = $activeVariants
                ->filter(fn ($v) => $v->price_discount !== null && $v->price_discount > 0)
                ->sortBy('price_discount')
                ->first();

            if ($discountedVariant) {
                $product->update([
                    'base_price' => $discountedVariant->price,
                    'base_price_discount' => $discountedVariant->price_discount,
                ]);
            } else {
                $cheapestVariant = $activeVariants->sortBy('price')->first();
                $product->update([
                    'base_price' => $cheapestVariant->price,
                    'base_price_discount' => null,
                ]);
            }

            // 6. Buat ProductImage
            $imageCount = fake()->numberBetween(2, 4);
            $productImages = \App\Models\ProductImage::factory()
                ->count($imageCount)
                ->for($product)
                ->create();

            $productImages->random()->update([
                'is_thumbnail' => true,
            ]);
        });
    }
}
