<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['fixed', 'percentage']);

        return [
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->optional()->paragraph(),
            'code' => $this->faker->unique()->bothify('DISCOUNT-????'),
            'type' => $type,
            'value' => $type === 'fixed'
                ? $this->faker->randomFloat(2, 5, 100)
                : $this->faker->randomFloat(2, 5, 50),
            'start_date' => $this->faker->optional()->dateTimeBetween('-1 month', '+1 month'),
            'end_date' => $this->faker->optional()->dateTimeBetween('+1 month', '+6 months'),
            'usage_limit' => $this->faker->optional()->numberBetween(1, 100),
            'used_count' => 0,
            'minimum_purchase' => $this->faker->randomFloat(2, 0, 500),
            'is_active' => $this->faker->boolean(80),
        ];
    }
}
