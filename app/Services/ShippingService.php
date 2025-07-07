<?php

namespace App\Services;

use App\Exceptions\ApiRequestException;
use App\Models\City;

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

        $params = [
            'origin' => (string) $this->origin->id,
            'destination' => (string) $destination,
            'weight' => (int) $weight,
            'courier' => (string) $courier,
        ];

        $postFields = http_build_query($params);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                'content-type: application/x-www-form-urlencoded',
                'key: '.$this->apiKey,
            ],
            CURLOPT_HEADER => true,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        curl_close($curl);

        try {
            if ($err) {
                if (strpos($err, 'timeout') !== false) {
                    throw new ApiRequestException(
                        logMessage: 'RajaOngkir cURL timeout: '.$err,
                        userMessage: 'Koneksi ke layanan pengiriman terputus, silakan coba lagi nanti.',
                        statusCode: 504
                    );
                }
                throw new ApiRequestException(
                    logMessage: 'RajaOngkir cURL error: '.$err,
                    userMessage: 'Terjadi kesalahan tidak terduga pada saat menghitung ongkos kirim, silakan coba beberapa saat lagi.',
                    statusCode: 500
                );
            }

            error_log('RajaOngkir raw response: '.$body);
            error_log('RajaOngkir response headers: '.$headers);
            error_log('RajaOngkir HTTP status code: '.$httpCode);

            if (empty($body)) {
                throw new ApiRequestException(
                    logMessage: 'RajaOngkir empty response received',
                    userMessage: 'Tidak ada data dari layanan pengiriman, silakan coba lagi nanti.',
                    statusCode: 502
                );
            }

            if ($httpCode === 500) {
                throw new ApiRequestException(
                    logMessage: 'RajaOngkir server error: HTTP 500 Internal Server Error | Raw response: '.substr($body, 0, 500),
                    userMessage: 'Layanan pengiriman sedang bermasalah, silakan coba lagi nanti.',
                    statusCode: 500
                );
            }

            $result = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ApiRequestException(
                    logMessage: 'RajaOngkir invalid JSON response: '.json_last_error_msg().' | Raw response: '.substr($body, 0, 500),
                    userMessage: 'Terjadi kesalahan dalam memproses data pengiriman, silakan coba lagi nanti.',
                    statusCode: 500
                );
            }

            if ($httpCode >= 400) {
                $errorDescription = $result['rajaongkir']['status']['description'] ?? 'Unknown error';
                $errorCode = $result['rajaongkir']['status']['code'] ?? $httpCode;

                if ($errorCode >= 400 && $errorCode <= 499) {
                    if (stripos($errorDescription, 'Invalid key') !== false) {
                        throw new ApiRequestException(
                            logMessage: 'RajaOngkir invalid API key: '.$errorDescription,
                            userMessage: 'Terjadi kesalahan pada layanan pengiriman, silakan coba beberapa saat lagi.',
                            statusCode: $errorCode
                        );
                    }

                    if (stripos($errorDescription, 'Bad request') !== false) {
                        throw new ApiRequestException(
                            logMessage: 'RajaOngkir invalid parameters: '.$errorDescription,
                            userMessage: 'Terjadi kesalahan dalam menghitung ongkos kirim, silakan periksa alamat Anda dan coba lagi.',
                            statusCode: $errorCode
                        );
                    }

                    throw new ApiRequestException(
                        logMessage: 'RajaOngkir unexpected 4xx error: '.$errorDescription,
                        userMessage: 'Terjadi kesalahan yang tidak terduga, silakan coba beberapa saat lagi.',
                        statusCode: $errorCode
                    );
                }

                throw new ApiRequestException(
                    logMessage: 'RajaOngkir API unexpected error: '.$errorDescription,
                    userMessage: 'Terjadi kesalahan yang tidak terduga, silakan coba beberapa saat lagi.',
                    statusCode: $errorCode
                );
            }

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
