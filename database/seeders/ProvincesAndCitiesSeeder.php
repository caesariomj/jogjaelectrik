<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class ProvincesAndCitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $provinceUrl = 'https://api.rajaongkir.com/'.env('RAJAONGKIR_PACKAGE').'/province';
        $cityUrl = 'https://api.rajaongkir.com/'.env('RAJAONGKIR_PACKAGE').'/city';

        $headers = [
            'key' => env('RAJAONGKIR_API_KEY'),
        ];

        $provinceResponse = Http::withHeaders($headers)->get($provinceUrl);
        $provinces = $provinceResponse->json()['rajaongkir']['results'];

        foreach ($provinces as $province) {
            Province::updateOrCreate(
                [
                    'id' => $province['province_id'],
                ],
                [
                    'name' => $province['province'],
                ],
            );
        }

        $cityResponse = Http::withHeaders($headers)->get($cityUrl);
        $cities = $cityResponse->json()['rajaongkir']['results'];

        foreach ($cities as $city) {
            City::updateOrCreate(
                [
                    'id' => $city['city_id'],
                ],
                [
                    'province_id' => $city['province_id'],
                    'name' => $city['type'].' '.$city['city_name'],
                ],
            );
        }
    }
}
