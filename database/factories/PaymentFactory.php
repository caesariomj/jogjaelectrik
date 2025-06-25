<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => \App\Models\Order::factory(),
            'xendit_invoice_id' => fake()->uuid(),
            'xendit_invoice_url' => fake()->url(),
            'method' => fake()->randomElement(['bank_transfer', 'ewallet']),
            'status' => 'unpaid',
            'reference_number' => (string) fake()->unique()->numerify('##########'),
            'paid_at' => null,
        ];
    }
}
