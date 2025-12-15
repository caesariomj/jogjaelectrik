<?php

namespace App\Services;

use App\Exceptions\ApiRequestException;
use App\Models\District;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ShippingService
{
    protected string $apiKey;

    protected string $courierCodes;

    protected object $origin;

    public function __construct()
    {
        $this->apiKey = config('services.rajaongkir.key');
        $this->origin = District::where('name', '=', 'GAMPING')->first();
        $this->courierCodes = config('services.rajaongkir.courier_codes');
    }

    public function calculateShippingCost(int $destination, float $weight): array
    {
        $client = new Client;

        try {
            $response = $client->post(
                'https://rajaongkir.komerce.id/api/v1/calculate/district/domestic-cost',
                [
                    'headers' => [
                        'accept' => 'application/json',
                        'key' => $this->apiKey,
                    ],
                    'form_params' => [
                        'origin' => $this->origin->id,
                        'destination' => $destination,
                        'weight' => $weight,
                        'courier' => $this->courierCodes,
                        'price' => 'lowest',
                    ],
                    'timeout' => 15,
                ]
            );

            $body = json_decode($response->getBody()->getContents(), true);

            if (! isset($body['meta']['status'])) {
                throw new ApiRequestException(
                    'Unexpected error during RajaOngkir shipping cost calculation: Unexpected API format',
                    'Terjadi kesalahan pada layanan pengiriman, silakan coba lagi nanti.',
                    500,
                );
            }

            $status = $body['meta']['status'];
            $code = $body['meta']['code'] ?? 500;
            $message = $body['meta']['message'] ?? 'Unknown error';

            if ($status === 'error' || $status === 'failed') {
                if ($code === 400) {
                    throw new ApiRequestException(
                        'Unexpected error during RajaOngkir shipping cost calculation: '.$message,
                        'Data pengiriman tidak valid, silakan coba lagi.',
                        400
                    );
                }

                if ($code === 422) {
                    throw new ApiRequestException(
                        'Unexpected error during RajaOngkir shipping cost calculation: '.$message,
                        'Kurir tidak didukung oleh layanan pengiriman kami, silakan pilih kurir yang tersedia.',
                        422
                    );
                }

                throw new ApiRequestException(
                    'Unexpected error during RajaOngkir shipping cost calculation: '.$message,
                    'Terjadi kesalahan pada layanan pengiriman, silakan coba lagi nanti.',
                    $code
                );
            }

            $data = ! empty($body['data']) ? $this->transformCourierServicesData($body['data']) : [];

            return $data;
        } catch (RequestException $e) {
            $status = $e->getCode() >= 100 ? $e->getCode() : 503;

            throw new ApiRequestException(
                'HTTP RequestException during RajaOngkir shipping cost calculation: '.$e->getMessage(),
                'Layanan pengiriman sedang tidak tersedia. Silakan coba beberapa saat lagi.',
                $status
            );
        } catch (\Exception $e) {
            throw new ApiRequestException(
                'Unexpected exception during RajaOngkir shipping cost calculation: '.$e->getMessage(),
                'Terjadi kesalahan pada layanan pengiriman.',
                500
            );
        }
    }

    private function transformCourierServicesData($data): array
    {
        $courierServices = [];

        foreach ($data as $courier) {
            if ($courier['code'] === 'tiki' && in_array($courier['service'], ['T15', 'T25', 'T60'])) {
                continue;
            }

            $etd = $this->cleanEtd($courier['etd']);

            $courierServices[] = [
                'name' => $courier['name'],
                'code' => $courier['code'],
                'service' => $courier['service'],
                'description' => $courier['description'],
                'cost' => $courier['cost'],
                'etd' => $etd,
            ];
        }

        return $courierServices;
    }

    private function cleanEtd(?string $value): string
    {
        $value = trim($value ?? '');

        if ($value === '' || $value === '0-0' || $value === '0') {
            return 'N/A';
        }

        if (preg_match('/\d+(-\d+)?/', $value, $matches)) {
            $etd = $matches[0];

            if (strpos($etd, '-') !== false) {
                [$etdMinDays, $etdMaxDays] = explode('-', $etd);

                if ($etdMinDays === $etdMaxDays) {
                    return $etdMinDays;
                }

                return $etd;
            }

            return $etd;
        }

        return 'N/A';
    }
}
