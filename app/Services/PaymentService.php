<?php

namespace App\Services;

use App\Exceptions\ApiRequestException;
use App\Models\Order;
use Illuminate\Support\Facades\Crypt;
use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\InvoiceApi;
use Xendit\Refund\CreateRefund;
use Xendit\Refund\RefundApi;

class PaymentService
{
    public string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.xendit.secret_key');

        Configuration::setXenditKey($this->secretKey);
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

        $apiInstance = new InvoiceApi;
        $createInvoiceRequest = new CreateInvoiceRequest($params);

        try {
            $result = $apiInstance->createInvoice($createInvoiceRequest);

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

    public function getInvoice(string $invoiceId)
    {
        $apiInstance = new InvoiceApi;

        try {
            $invoice = $apiInstance->getInvoiceById($invoiceId);

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

    public function expireInvoice(string $xenditInvoiceId)
    {
        $apiInstance = new InvoiceApi;

        try {
            $apiInstance->expireInvoice($xenditInvoiceId);
        } catch (\Xendit\XenditSdkException $e) {
            throw new ApiRequestException(
                logMessage: 'Unexpected error during xendit invoice expiration: '.$e->getErrorMessage(),
                userMessage: 'Terjadi kesalahan pada sistem pembayaran, silakan coba beberapa saat lagi.',
                statusCode: $e->getErrorCode()
            );
        } catch (\Exception $e) {
            throw new ApiRequestException(
                logMessage: 'Unexpected error during invoice expiration: '.$e->getMessage(),
                userMessage: 'Terjadi kesalahan tidak terduga pada sistem pembayaran, silakan coba beberapa saat lagi.',
                statusCode: $e->getCode()
            );
        }
    }

    public function createRefund(Order $order)
    {
        $params = [
            'invoice_id' => $order->payment->xendit_invoice_id,
            'reference_id' => $order->order_number,
            'amount' => (float) $order->total_amount,
            'currency' => 'IDR',
            'reason' => 'CANCELLATION',
        ];

        $apiInstance = new RefundApi;
        $createRefund = new CreateRefund($params);

        try {
            return $apiInstance->createRefund(idempotency_key: $order->payment->refund->id, create_refund: $createRefund);
        } catch (\Xendit\XenditSdkException $e) {
            $userMessage = null;

            if ($e->getErrorCode() === 400) {
                $userMessage = 'Gagal memproses permintaan refund karena total belanja melebihi batas maksimal yang diizinkan untuk pengembalian dana.';
            } elseif ($e->getErrorCode() === 403) {
                $userMessage = 'Gagal memproses permintaan refund karena tidak ada API key yang valid.';
            } elseif ($e->getErrorCode() === 404) {
                $userMessage = 'Gagal memproses permintaan refund karena data yang diminta tidak ditemukan.';
            } elseif ($e->getErrorCode() === 409) {
                $userMessage = 'Gagal memproses permintaan refund karena permintaan refund ini telah diproses.';
            } elseif ($e->getErrorCode() === 504) {
                $userMessage = 'Gagal memproses permintaan refund karena saluran pembayaran yang diminta saat ini mengalami masalah, silakan coba beberapa saat lagi.';
            }

            throw new ApiRequestException(
                logMessage: 'Unexpected error during xendit refund creation: '.$e->getErrorMessage(),
                userMessage: $userMessage ?? 'Terjadi kesalahan pada sistem refund, silakan coba beberapa saat lagi.',
                statusCode: $e->getErrorCode()
            );
        } catch (\Exception $e) {
            throw new ApiRequestException(
                logMessage: 'Unexpected error during refund creation: '.$e->getMessage(),
                userMessage: 'Terjadi kesalahan tidak terduga pada sistem refund, silakan coba beberapa saat lagi.',
                statusCode: $e->getCode()
            );
        }
    }
}
