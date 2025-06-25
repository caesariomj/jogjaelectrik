<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Refund>
 */
class RefundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => \App\Models\Order::factory(),
            'xendit_refund_id' => fake()->uuid(),
            'status' => 'pending',
            'rejection_reason' => null,
            'approved_at' => null,
            'succeeded_at' => null,
        ];
    }
}
