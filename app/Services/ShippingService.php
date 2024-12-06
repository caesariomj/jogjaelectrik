<?php

namespace App\Services;

use App\Exceptions\ApiRequestException;
use Illuminate\Support\Facades\Http;

class ShippingService
{
    protected $apiKey;

    protected $apiPackage;

    protected object $origin;

    public function __construct()
    {
        $this->apiKey = config('services.rajaongkir.key');
        $this->apiPackage = config('services.rajaongkir.package');
        $this->origin = \App\Models\City::where('name', 'Kabupaten Sleman')->first();
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

            if (! $response->successful()) {
                $statusCode = $response->status();
                $responseBody = $response->json();

                \Illuminate\Support\Facades\Log::error('API request failed', [
                    'status_code' => $statusCode,
                    'response_body' => $responseBody,
                    'url' => $url,
                    'params' => $params,
                ]);

                $errorDescription = $responseBody['rajaongkir']['status']['description'] ?? 'Unknown error';
                $errorCode = $responseBody['rajaongkir']['status']['code'] ?? $statusCode;

                if ($errorCode === 400) {
                    if (str_contains($errorDescription, 'Invalid key')) {
                        $errorMessage = 'Invalid API key. Please check your credentials.';
                    } elseif (str_contains($errorDescription, 'Bad request')) {
                        $errorMessage = 'Invalid parameters. Please check the data you submitted: '.$errorDescription;
                    } else {
                        $errorMessage = 'Bad request: '.$errorDescription;
                    }

                    throw new ApiRequestException($errorMessage, $errorCode);
                }

                if ($errorCode >= 400 && $errorCode < 500) {
                    throw new ApiRequestException('Client error. Please check your request and try again.', $errorCode);
                }

                if ($errorCode >= 500 && $errorCode < 600) {
                    throw new ApiRequestException('Server error. Please try again later.', $errorCode);
                }

                throw new ApiRequestException('An unexpected error occurred. Please try again later.', $errorCode);
            }

            $result = $response->json();

            return $this->transformCourierServicesData($result['rajaongkir']['results']);
        } catch (ApiRequestException $e) {
            \Illuminate\Support\Facades\Log::error('Error calculating shipping cost', [
                'message' => $e->getMessage(),
                'status_code' => $e->getCode(),
                'params' => $params,
                'exception_trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Unexpected error calculating shipping cost', [
                'message' => $e->getMessage(),
                'status_code' => $e->getCode(),
                'params' => $params,
                'exception_trace' => $e->getTraceAsString(),
            ]);

            throw new ApiRequestException('Terjadi kesalahan saat menghitung biaya pengiriman. Silakan coba beberapa saat lagi.', 500);
        }
    }

    private function transformCourierServicesData($data)
    {
        // Ada kemungkinan ekspedisi tidak menyediakan jasa pengiriman dengan contoh data seperti dibawah

        // Test kirim ke Jogja dari Sleman menggunakan POS, array $data['results'][0]['costs'] kosong

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

// Result
// JNE
// array:1 [▼ // app\Services\ShippingService.php:48
//   "rajaongkir" => array:5 [▼
//     "query" => array:4 [▼
//       "origin" => "419"
//       "destination" => "501"
//       "weight" => 9150
//       "courier" => "jne"
//     ]
//     "status" => array:2 [▼
//       "code" => 200
//       "description" => "OK"
//     ]
//     "origin_details" => array:6 [▼
//       "city_id" => "419"
//       "province_id" => "5"
//       "province" => "DI Yogyakarta"
//       "type" => "Kabupaten"
//       "city_name" => "Sleman"
//       "postal_code" => "55513"
//     ]
//     "destination_details" => array:6 [▼
//       "city_id" => "501"
//       "province_id" => "5"
//       "province" => "DI Yogyakarta"
//       "type" => "Kota"
//       "city_name" => "Yogyakarta"
//       "postal_code" => "55111"
//     ]
//     "results" => array:1 [▼
//       0 => array:3 [▼
//         "code" => "jne"
//         "name" => "Jalur Nugraha Ekakurir (JNE)"
//         "costs" => array:3 [▼
//           0 => array:3 [▼
//             "service" => "CTC"
//             "description" => "JNE City Courier"
//             "cost" => array:1 [▼
//               0 => array:3 [▼
//                 "value" => 63000
//                 "etd" => "1-2"
//                 "note" => ""
//               ]
//             ]
//           ]
//           1 => array:3 [▼
//             "service" => "JTR"
//             "description" => "JNE Trucking"
//             "cost" => array:1 [▼
//               0 => array:3 [▼
//                 "value" => 40000
//                 "etd" => "3-4"
//                 "note" => ""
//               ]
//             ]
//           ]
//           2 => array:3 [▼
//             "service" => "CTCYES"
//             "description" => "JNE City Courier"
//             "cost" => array:1 [▼
//               0 => array:3 [▼
//                 "value" => 99000
//                 "etd" => "1-1"
//                 "note" => ""
//               ]
//             ]
//           ]
//         ]
//       ]
//     ]
//   ]
// ]

// POS
// array:1 [▼ // app\Services\ShippingService.php:48
//   "rajaongkir" => array:5 [▼
//     "query" => array:4 [▼
//       "origin" => "419"
//       "destination" => "501"
//       "weight" => 9150
//       "courier" => "pos"
//     ]
//     "status" => array:2 [▼
//       "code" => 200
//       "description" => "OK"
//     ]
//     "origin_details" => array:6 [▼
//       "city_id" => "419"
//       "province_id" => "5"
//       "province" => "DI Yogyakarta"
//       "type" => "Kabupaten"
//       "city_name" => "Sleman"
//       "postal_code" => "55513"
//     ]
//     "destination_details" => array:6 [▼
//       "city_id" => "501"
//       "province_id" => "5"
//       "province" => "DI Yogyakarta"
//       "type" => "Kota"
//       "city_name" => "Yogyakarta"
//       "postal_code" => "55111"
//     ]
//     "results" => array:1 [▼
//       0 => array:3 [▼
//         "code" => "pos"
//         "name" => "POS Indonesia (POS)"
//         "costs" => array:4 [▼
//           0 => array:3 [▼
//             "service" => "Pos Ekonomi"
//             "description" => "Pos Ekonomi"
//             "cost" => array:1 [▼
//               0 => array:3 [▼
//                 "value" => 40500
//                 "etd" => "7-14 HARI"
//                 "note" => ""
//               ]
//             ]
//           ]
//           1 => array:3 [▼
//             "service" => "Pos Reguler"
//             "description" => "Pos Reguler"
//             "cost" => array:1 [▼
//               0 => array:3 [▼
//                 "value" => 63000
//                 "etd" => "3 HARI"
//                 "note" => ""
//               ]
//             ]
//           ]
//           2 => array:3 [▼
//             "service" => "Pos Sameday"
//             "description" => "Pos Sameday"
//             "cost" => array:1 [▼
//               0 => array:3 [▼
//                 "value" => 144000
//                 "etd" => "0 HARI"
//                 "note" => ""
//               ]
//             ]
//           ]
//           3 => array:3 [▼
//             "service" => "Pos Nextday"
//             "description" => "Pos Nextday"
//             "cost" => array:1 [▼
//               0 => array:3 [▼
//                 "value" => 94500
//                 "etd" => "1 HARI"
//                 "note" => ""
//               ]
//             ]
//           ]
//         ]
//       ]
//     ]
//   ]
// ]

// TIKI
// array:1 [▼ // app\Services\ShippingService.php:48
//   "rajaongkir" => array:5 [▼
//     "query" => array:4 [▼
//       "origin" => "419"
//       "destination" => "501"
//       "weight" => 9150
//       "courier" => "tiki"
//     ]
//     "status" => array:2 [▼
//       "code" => 200
//       "description" => "OK"
//     ]
//     "origin_details" => array:6 [▼
//       "city_id" => "419"
//       "province_id" => "5"
//       "province" => "DI Yogyakarta"
//       "type" => "Kabupaten"
//       "city_name" => "Sleman"
//       "postal_code" => "55513"
//     ]
//     "destination_details" => array:6 [▼
//       "city_id" => "501"
//       "province_id" => "5"
//       "province" => "DI Yogyakarta"
//       "type" => "Kota"
//       "city_name" => "Yogyakarta"
//       "postal_code" => "55111"
//     ]
//     "results" => array:1 [▼
//       0 => array:3 [▼
//         "code" => "tiki"
//         "name" => "Citra Van Titipan Kilat (TIKI)"
//         "costs" => array:7 [▼
//           0 => array:3 [▼
//             "service" => "SDS"
//             "description" => "Same Day Service"
//             "cost" => array:1 [▼
//               0 => array:3 [▼
//                 "value" => 126000
//                 "etd" => "0"
//                 "note" => ""
//               ]
//             ]
//           ]
//           1 => array:3 [▼
//             "service" => "ONS"
//             "description" => "Over Night Service"
//             "cost" => array:1 [▼
//               0 => array:3 [▼
//                 "value" => 72000
//                 "etd" => "1"
//                 "note" => ""
//               ]
//             ]
//           ]
//           2 => array:3 [▼
//             "service" => "REG"
//             "description" => "Reguler Service"
//             "cost" => array:1 [▼
//               0 => array:3 [▼
//                 "value" => 45000
//                 "etd" => "2"
//                 "note" => ""
//               ]
//             ]
//           ]
//           3 => array:3 [▼
//             "service" => "T15"
//             "description" => "Motor Di Bawah 150cc"
//             "cost" => array:1 [▼
//               0 => array:3 [▼
//                 "value" => 150000
//                 "etd" => "4"
//                 "note" => ""
//               ]
//             ]
//           ]
//           4 => array:3 [▼
//             "service" => "T25"
//             "description" => "Motor Di Bawah 250cc"
//             "cost" => array:1 [▼
//               0 => array:3 [▼
//                 "value" => 200000
//                 "etd" => "4"
//                 "note" => ""
//               ]
//             ]
//           ]
//           5 => array:3 [▼
//             "service" => "T60"
//             "description" => "Motor Di Bawah 600cc"
//             "cost" => array:1 [▼
//               0 => array:3 [▼
//                 "value" => 280000
//                 "etd" => "4"
//                 "note" => ""
//               ]
//             ]
//           ]
//           6 => array:3 [▼
//             "service" => "TRC"
//             "description" => "Trucking"
//             "cost" => array:1 [▼
//               0 => array:3 [▼
//                 "value" => 25000
//                 "etd" => "4"
//                 "note" => ""
//               ]
//             ]
//           ]
//         ]
//       ]
//     ]
//   ]
// ]
