<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VariantCombination>
 */
class VariantCombinationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_variant_id' => \App\Models\ProductVariant::factory(),
            'variation_variant_id' => \App\Models\VariationVariant::factory(),
        ];
    }
}
