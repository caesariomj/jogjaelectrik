<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\City>
 */
class CityFactory extends Factory
{
    protected static int $indexCounter = 0;

    protected static $cities = [
        ['id' => 1, 'province_id' => 1, 'name' => 'Kabupaten Sleman'],
        ['id' => 2, 'province_id' => 2, 'name' => 'Bandung'],
        ['id' => 3, 'province_id' => 2, 'name' => 'Bekasi'],
        ['id' => 4, 'province_id' => 3, 'name' => 'Semarang'],
        ['id' => 5, 'province_id' => 3, 'name' => 'Solo'],
        ['id' => 6, 'province_id' => 4, 'name' => 'Surabaya'],
        ['id' => 7, 'province_id' => 4, 'name' => 'Malang'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $index = static::$indexCounter ?? 0;
        $data = static::$cities[$index % count(static::$cities)];
        static::$indexCounter = $index + 1;

        return [
            'id' => $data['id'],
            'province_id' => $data['province_id'],
            'name' => $data['name'],
        ];
    }

    public static function resetCount(): void
    {
        static::$indexCounter = 0;
    }
}