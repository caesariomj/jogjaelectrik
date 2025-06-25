<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hasDiscount = fake()->boolean();
        $discount = null;

        if ($hasDiscount) {
            $discount = \App\Models\Discount::factory()->create();
        }

        $subtotalAmount = fake()->randomFloat(2, 10000, 1000000);
        $discountAmount = 0.0;

        if ($discount && $discount->type === 'fixed') {
            $discountAmount = $discount->value;
        }

        $shippingCostAmount = fake()->randomFloat(2, 10000, 100000);

        return [
            'user_id' => \App\Models\User::factory(),
            'discount_id' => $discount?->id,
            'status' => 'waiting_payment',
            'shipping_address' => \Illuminate\Support\Facades\Crypt::encryptString(fake()->address()),
            'shipping_courier' => 'jne-ctc',
            'estimated_shipping_min_days' => fake()->numberBetween(1, 3),
            'estimated_shipping_max_days' => fake()->numberBetween(3, 6),
            'shipment_tracking_number' => 'test-tracking-number',
            'note' => fake()->text(),
            'subtotal_amount' => $subtotalAmount,
            'discount_amount' => $discountAmount,
            'shipping_cost_amount' => $shippingCostAmount,
            'total_amount' => ($subtotalAmount - $discountAmount) + $shippingCostAmount,
            'cancelation_reason' => null,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function ($order) {
            $product = \App\Models\Product::factory()->create();

            $variants = $product->variants()->get();

            foreach ($variants->random(fake()->numberBetween(1, $variants->count())) as $variant) {
                $quantity = fake()->numberBetween(1, 3);
                $price = $variant->price_discount ? $variant->price_discount : $variant->price;

                \App\Models\OrderDetail::factory()->create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'price' => $price,
                ]);
            }

            $order->update([
                'total_amount' => $order->details->sum('subtotal') + $order->shipping_cost_amount,
            ]);

            \App\Models\Payment::factory()->create([
                'order_id' => $order->id,
            ]);
        });
    }
}
