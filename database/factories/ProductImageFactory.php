<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductImage>
 */
class ProductImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'file_name' => \Illuminate\Support\Str::slug($name).'-'.$this->faker->unique()->numberBetween(1000, 9999).'.jpg',
            'is_thumbnail' => false,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function ($image) {
            if (app()->runningUnitTests()) {
                \Illuminate\Support\Facades\Storage::disk('public_uploads')->put('product-images/'.$image->file_name, 'fake image content');
            }
        });
    }
}
