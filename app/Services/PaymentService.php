<?php

namespace App\Services;

use App\Exceptions\ApiRequestException;
use App\Models\Order;

class PaymentService
{
    public function __construct()
    {
        \Midtrans\Config::$serverKey = config('services.midtrans.serverKey');
        \Midtrans\Config::$isProduction = config('services.midtrans.isProduction');
        \Midtrans\Config::$isSanitized = config('services.midtrans.isSanitized');
        \Midtrans\Config::$is3ds = config('services.midtrans.is3ds');
    }

    public function createSnapToken(Order $order, string $paymentMethod)
    {
        $user = $order->user;

        $itemDetails = $order->details->map(function ($item) {
            return [
                'id' => $item->productVariant->product->id,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'name' => $item->productVariant->product->name,
                'category' => $item->productVariant->product->subcategory->name,
                'merchant_name' => 'Toko Jogja Electrik',
                'url' => url('/produk/'.$item->productVariant->product->slug),
            ];
        })->toArray();

        if ($order->discount_amount) {
            $discountAmount = [
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'price' => $order->discount_amount,
                'quantity' => 1,
                'name' => 'Diskon '.ucwords($order->discounts()->first()->discount->name),
            ];

            array_push($itemDetails, $discountAmount);
        }

        $shippingCostAmount = [
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'price' => $order->shipping_cost_amount,
            'quantity' => 1,
            'name' => 'Ongkos Kirim '.strtoupper($order->shipping_courier),
        ];

        array_push($itemDetails, $shippingCostAmount);

        $decryptedUserPhoneNumber = \Illuminate\Support\Facades\Crypt::decryptString($user->phone_number);
        $decryptedUserAddress = \Illuminate\Support\Facades\Crypt::decryptString($user->address);
        $decryptedUserPostalCode = \Illuminate\Support\Facades\Crypt::decryptString($user->postal_code);

        $customerDetails = [
            'first_name' => $user->name,
            'email' => $user->email,
            'phone' => '+62'.str_replace('-', '', $decryptedUserPhoneNumber),
            'billing_address' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => '+62'.str_replace('-', '', $decryptedUserPhoneNumber),
                'address' => $decryptedUserAddress,
                'city' => $user->city->name,
                'postal_code' => $decryptedUserPostalCode,
                'country_code' => 'IDN',
            ],
            'shipping_address' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => '+62'.str_replace('-', '', $decryptedUserPhoneNumber),
                'address' => $decryptedUserAddress,
                'city' => $user->city->name,
                'postal_code' => $decryptedUserPostalCode,
                'country_code' => 'IDN',
            ],
        ];

        $params = [
            'transaction_details' => [
                'order_id' => $order->id,
                'gross_amount' => $order->total_amount,
            ],
            'item_details' => $itemDetails,
            'customer_details' => $customerDetails,
            'enabled_payments' => [$paymentMethod],
        ];

        try {
            return \Midtrans\Snap::getSnapToken($params);
        } catch (ApiRequestException $e) {
            $statusCode = $e->getStatusCode();

            \Illuminate\Support\Facades\Log::error('Error generating Midtrans Snap Token', [
                'message' => $e->getMessage(),
                'status_code' => $statusCode,
                'params' => $params,
                'exception_trace' => $e->getTraceAsString(),
            ]);

            switch ($statusCode) {
                case 401:
                    throw new ApiRequestException('Terjadi masalah dengan otorisasi. Silakan periksa koneksi anda atau coba beberapa saat lagi.', 401);
                    break;

                default:
                    if ($statusCode >= 400 && $statusCode < 500) {
                        throw new ApiRequestException('Gagal memproses pesanan, periksa kembali data pesanan Anda atau coba beberapa saat lagi.', $statusCode);
                    }

                    if ($statusCode >= 500 && $statusCode < 600) {
                        throw new ApiRequestException('Terjadi kesalahan di sistem pembayaran. Silakan coba beberapa saat lagi.', $statusCode);
                    }

                    throw new ApiRequestException('Terjadi kesalahan yang tidak terduga, silakan coba beberapa saat lagi atau hubungi customer support kami.', $statusCode);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Unexpected error generating Midtrans Snap Token', [
                'message' => $e->getMessage(),
                'status_code' => $e->getCode(),
                'params' => $params,
                'exception_trace' => $e->getTraceAsString(),
            ]);

            throw new ApiRequestException('Terjadi kesalahan yang tidak terduga, silakan coba beberapa saat lagi atau hubungi customer support kami.', 500);
        }
    }

    public function checkTransactionStatus(string $orderId)
    {
        try {
            return \Midtrans\Transaction::status($orderId);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Unexpected error checking Midtrans transaction status', [
                'message' => $e->getMessage(),
                'status_code' => $e->getCode(),
                'order_id' => $orderId,
                'exception_trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception('Terjadi kesalahan pada sistem pembayaran, silakan coba beberapa saat lagi.');
        }
    }
}
