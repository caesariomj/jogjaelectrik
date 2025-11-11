<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\District;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $headers = [
            'accept' => 'application/json',
            'key' => config('services.rajaongkir.key'),
        ];

        $baseUrl = 'https://rajaongkir.komerce.id/api/v1/destination';
        $apiLimit = 100;
        $apiCalls = 0;

        $provinces = Province::get()->toArray();
        if (count($provinces) === 0) {
            $provinceResponse = Http::withHeaders($headers)->get("{$baseUrl}/province");
            $provinces = $provinceResponse->json()['data'] ?? [];
        }

        foreach ($provinces as $province) {
            Province::updateOrCreate(['id' => $province['id']], ['name' => $province['name']]);

            $cities = City::where('province_id', $province['id'])->get()->toArray();
            if (count($cities) === 0) {
                $cityResponse = Http::withHeaders($headers)->get("{$baseUrl}/city/{$province['id']}");
                $cities = $cityResponse->json()['data'] ?? [];
            }

            foreach ($cities as $city) {
                City::updateOrCreate(
                    ['id' => $city['id']],
                    ['province_id' => $province['id'], 'name' => $city['name']]
                );

                $districts = District::where('city_id', $city['id'])->get()->toArray();

                if (count($districts) === 0) {
                    if ($apiCalls >= $apiLimit) {
                        $this->command->warn('API limit reached.');

                        return;
                    }

                    $districtResponse = Http::withHeaders($headers)->get("{$baseUrl}/district/{$city['id']}");

                    $districts = $districtResponse->json()['data'] ?? [];
                    $apiCalls++;
                }

                foreach ($districts as $district) {
                    District::updateOrCreate(
                        ['id' => $district['id']],
                        ['city_id' => $city['id'], 'name' => $district['name']]
                    );
                }
            }
        }

        $this->command->info('Location successfully seeded.');
    }
}
