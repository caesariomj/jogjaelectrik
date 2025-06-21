<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Province>
 */
class ProvinceFactory extends Factory
{
    protected static int $indexCounter = 0;

    protected static $provinces = [
        ['id' => 1, 'name' => 'Daerah Istimewa Yogyakarta'],
        ['id' => 2, 'name' => 'Jawa Barat'],
        ['id' => 3, 'name' => 'Jawa Tengah'],
        ['id' => 4, 'name' => 'Jawa Timur'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $index = static::$indexCounter ?? 0;
        $data = static::$provinces[$index % count(static::$provinces)];
        static::$indexCounter = $index + 1;

        return [
            'id' => $data['id'],
            'name' => $data['name'],
        ];
    }

    public static function resetCount(): void
    {
        static::$indexCounter = 0;
    }
}