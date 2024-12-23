<?php

namespace App\Services;

use App\Exceptions\ApiRequestException;
use App\Models\Order;
use Illuminate\Support\Facades\Crypt;
use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\InvoiceApi;

class PaymentService
{
    public function __construct()
    {
        Configuration::setXenditKey(config('services.xendit.secret_key'));
    }

    public function createInvoice(Order $order)
    {
        $user = $order->user;

        $decryptedUserPhoneNumber = Crypt::decryptString($user->phone_number);
        $decryptedUserAddress = Crypt::decryptString($user->address);
        $decryptedUserPostalCode = Crypt::decryptString($user->postal_code);

        $customer = [
            'given_names' => $user->name,
            'email' => $user->email,
            'mobile_number' => '+62'.str_replace('-', '', $decryptedUserPhoneNumber),
            'addresses' => [
                [
                    'city' => $user->city->name,
                    'country' => 'Indonesia',
                    'postal_code' => $decryptedUserPostalCode,
                    'state' => $user->city->province->name,
                    'street_line1' => $decryptedUserAddress,
                ],
            ],
        ];

        $items = $order->details->map(function ($item) {
            $data = [
                'name' => $item->productVariant->product->name,
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->price,
                'url' => config('app.url').'/produk/'.$item->productVariant->product->slug,
            ];

            if ($item->productVariant->product->subcategory) {
                $data['category'] = ucwords($item->productVariant->product->subcategory->name);
            }

            return $data;
        })->toArray();

        $fees = [
            [
                'type' => 'Ongkos Kirim '.strtoupper($order->shipping_courier),
                'value' => $order->shipping_cost_amount,
            ],
        ];

        if ($order->discount_amount) {
            $discountAmount = [
                'type' => 'Diskon '.ucwords($order->discounts()->first()->discount->name),
                'value' => (float) $order->discount_amount,
            ];

            array_push($fees, $discountAmount);
        }

        $params = [
            'external_id' => $order->id,
            'description' => 'Invoice untuk pembayaran pesanan dengan nomor: '.$order->order_number,
            'amount' => $order->total_amount,
            'currency' => 'IDR',
            'locale' => 'id',
            'customer' => $customer,
            'customer_notification_preference' => [
                'invoice_created' => [
                    'whatsapp',
                    'email',
                ],
                'invoice_paid' => [
                    'whatsapp',
                    'email',
                ],
            ],
            'success_redirect_url' => config('app.url'),
            'failure_redirect_url' => config('app.url'),
            'items' => $items,
            'fees' => $fees,
        ];

        $invoiceApi = new InvoiceApi;
        $createInvoiceRequest = new CreateInvoiceRequest($params);

        try {
            $result = $invoiceApi->createInvoice($createInvoiceRequest);

            return [
                'url' => $result['invoice_url'],
            ];
        } catch (\Xendit\XenditSdkException $e) {
            throw new ApiRequestException(
                logMessage: 'Unexpected error during xendit invoice creation: '.$e->getErrorMessage(),
                userMessage: 'Terjadi kesalahan pada sistem pembayaran, silakan coba beberapa saat lagi.',
                statusCode: $e->getErrorCode()
            );
        } catch (\Exception $e) {
            throw new ApiRequestException(
                logMessage: 'Unexpected error during invoice creation: '.$e->getMessage(),
                userMessage: 'Terjadi kesalahan tidak terduga pada sistem pembayaran, silakan coba beberapa saat lagi.',
                statusCode: $e->getCode()
            );
        }
    }

    public function getInvoice($invoiceId)
    {
        $invoiceApi = new InvoiceApi;

        try {
            $invoice = $invoiceApi->getInvoiceById($invoiceId);

            return $invoice;
        } catch (\Xendit\XenditSdkException $e) {
            throw new ApiRequestException(
                logMessage: 'Unexpected error during xendit invoice retrieval: '.$e->getErrorMessage(),
                userMessage: 'Terjadi kesalahan pada sistem pembayaran, silakan coba beberapa saat lagi.',
                statusCode: $e->getErrorCode()
            );
        } catch (\Exception $e) {
            throw new ApiRequestException(
                logMessage: 'Unexpected error during invoice retrieval: '.$e->getMessage(),
                userMessage: 'Terjadi kesalahan tidak terduga pada sistem pembayaran, silakan coba beberapa saat lagi.',
                statusCode: $e->getCode()
            );
        }
    }
}
