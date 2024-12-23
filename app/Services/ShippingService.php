<?php

namespace App\Services;

use App\Exceptions\ApiRequestException;
use App\Models\City;
use Illuminate\Support\Facades\Http;

class ShippingService
{
    protected string $apiKey;

    protected string $apiPackage;

    protected object $origin;

    public function __construct()
    {
        $this->apiKey = config('services.rajaongkir.key');
        $this->apiPackage = config('services.rajaongkir.package');
        $this->origin = City::where('name', 'Kabupaten Sleman')->first();
    }

    public function calculateShippingCost($destination, $weight, $courier)
    {
        $url = 'https://api.rajaongkir.com/'.$this->apiPackage.'/cost';

        $headers = [
            'key' => $this->apiKey,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $params = [
            'origin' => (string) $this->origin->id,
            'destination' => (string) $destination,
            'weight' => (int) $weight,
            'courier' => (string) $courier,
        ];

        try {
            $response = Http::withHeaders($headers)->asForm()->post($url, $params);

            if ($response->failed()) {
                $responseBody = $response->json();

                $errorDescription = $responseBody['rajaongkir']['status']['description'] ?? 'Unknown error';
                $errorCode = $responseBody['rajaongkir']['status']['code'] ?? $response->status();

                if ($errorCode >= 400 && $errorCode <= 499) {
                    if (str_contains($errorDescription, 'Invalid key')) {
                        throw new ApiRequestException(
                            logMessage: 'RajaOngkir invalid API key: '.$errorDescription,
                            userMessage: 'Terjadi kesalahan pada layanan pengiriman, silakan coba beberapa saat lagi.',
                            statusCode: $errorCode,
                        );
                    }

                    if (str_contains($errorDescription, 'Bad request')) {
                        throw new ApiRequestException(
                            logMessage: 'RajaOngkir invalid parameters: '.$errorDescription,
                            userMessage: 'Terjadi kesalahan dalam menghitung ongkos kirim, silakan periksa alamat Anda dan coba lagi.',
                            statusCode: $errorCode,
                        );
                    }

                    throw new ApiRequestException(
                        logMessage: 'RajaOngkir unexpected 4xx error: '.$errorDescription,
                        userMessage: 'Terjadi kesalahan yang tidak terduga, silakan coba beberapa saat lagi.',
                        statusCode: $errorCode,
                    );
                }

                throw new ApiRequestException(
                    logMessage: 'RajaOngkir API unexpected error: '.$errorDescription,
                    userMessage: 'Terjadi kesalahan yang tidak terduga, silakan coba beberapa saat lagi.',
                    statusCode: $errorCode
                );
            }

            $result = $response->json();

            return $this->transformCourierServicesData($result['rajaongkir']['results']);
        } catch (ApiRequestException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ApiRequestException(
                logMessage: 'Unexpected error during shipping cost calculation: '.$e->getMessage(),
                userMessage: 'Terjadi kesalahan tidak terduga pada saat menghitung ongkos kirim, silakan coba beberapa saat lagi.',
                statusCode: $e->getCode()
            );
        }
    }

    private function transformCourierServicesData($data)
    {
        $courierServices = [];

        foreach ($data as $courier) {
            foreach ($courier['costs'] as $service) {
                if ($courier['code'] === 'tiki' && in_array($service['service'], ['T15', 'T25', 'T60'])) {
                    continue;
                }

                $etd = preg_match('/\d+-?\d*/', $service['cost'][0]['etd'], $matches) ? $matches[0] : null;

                $courierServices[] = [
                    'courier_code' => $courier['code'],
                    'courier_name' => $courier['name'],
                    'service' => $service['service'],
                    'description' => $service['description'],
                    'cost_value' => $service['cost'][0]['value'],
                    'etd' => $this->convertEstimatedTime($etd),
                    'note' => $service['cost'][0]['note'],
                ];
            }
        }

        return $courierServices;
    }

    private function convertEstimatedTime(string $etd)
    {
        $etd = trim(strtoupper($etd));

        if (preg_match('/^\d+-\d+$/', $etd)) {
            [$first, $second] = explode('-', $etd);

            if ($first === $second) {
                return $first;
            }

            return $etd;
        }

        if (preg_match('/^(\d+)(?:\sHARI)?$/', $etd, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^\d+$/', $etd)) {
            return $etd;
        }

        return $etd;
    }
}
