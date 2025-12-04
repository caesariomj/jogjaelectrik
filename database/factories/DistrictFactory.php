<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\District>
 */
class DistrictFactory extends Factory
{
    protected static int $indexCounter = 0;

    protected static $districts = [
        ['id' => 1, 'city_id' => 1, 'name' => 'GAMPING'],
        ['id' => 2, 'city_id' => 2, 'name' => 'Bandung'],
        ['id' => 3, 'city_id' => 2, 'name' => 'Bekasi'],
        ['id' => 4, 'city_id' => 3, 'name' => 'Semarang'],
        ['id' => 5, 'city_id' => 3, 'name' => 'Solo'],
        ['id' => 6, 'city_id' => 4, 'name' => 'Surabaya'],
        ['id' => 7, 'city_id' => 4, 'name' => 'Malang'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $index = static::$indexCounter ?? 0;
        $data = static::$districts[$index % count(static::$districts)];
        static::$indexCounter = $index + 1;

        return [
            'id' => $data['id'],
            'city_id' => $data['city_id'],
            'name' => $data['name'],
        ];
    }

    public static function resetCount(): void
    {
        static::$indexCounter = 0;
    }
}
