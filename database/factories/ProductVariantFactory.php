<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $variantSku = $this->faker->unique()->words(2, true);

        return [
            'product_id' => \App\Models\Product::factory(),
            'variant_sku' => str_replace(' ', '-', $variantSku),
            'price' => function () {
                return fake()->randomFloat(2, 1000, 1000000);
            },
            'price_discount' => function (array $attributes) {
                if (fake()->boolean(50)) {
                    return null;
                }

                return fake()->randomFloat(2, 1000, $attributes['price'] - 1);
            },
            'stock' => $this->faker->numberBetween(1, 100),
            'is_active' => $this->faker->boolean(80),
        ];
    }
}
