<?php

namespace App\Http\Controllers\User;

use App\Exceptions\ApiRequestException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle the incoming request.
     */
    public function paid(Request $request)
    {
        try {
            $invoice = $this->paymentService->getInvoice($request->id);

            $order = Order::with('payment')->find($request->external_id);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan dengan ID: '.$request->external_id.' tidak ditemukan.',
                ], 404);
            }

            if (in_array($order->payment->status, ['paid', 'settled', 'expired'])) {
                $message = match ($order->payment->status) {
                    'paid', 'settled' => 'Pesanan dengan nomor: '.$order->order_number.' telah dibayar.',
                    'expired' => 'Pesanan dengan nomor: '.$order->order_number.' telah kadaluarsa.',
                };

                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 400);
            }

            if ($invoice['status'] === 'PAID' || $invoice['status'] === 'SETTLED') {
                $order->update([
                    'status' => 'payment_received',
                ]);

                $referenceNumber = null;

                if ($invoice['payment_method'] === 'BANK_TRANSFER') {
                    $banks = collect($invoice['available_banks']);

                    $selectedBank = $banks->filter(function ($bank) {
                        return isset($bank['bank_account_number']);
                    })->first();

                    $paymentMethod = 'bank_transfer_'.strtolower($selectedBank['bank_code']);
                    $referenceNumber = $selectedBank['bank_account_number'];
                } elseif ($invoice['payment_method'] === 'EWALLET') {
                    $paymentMethod = strtolower($invoice['payment_method'].'_'.$request->ewallet_type);
                } else {
                    $paymentMethod = strtolower($invoice['payment_method']);
                }

                $order->payment->update([
                    'xendit_invoice_id' => $invoice['id'],
                    'status' => strtolower($invoice['status']),
                    'method' => $paymentMethod,
                    'reference_number' => $referenceNumber ?? null,
                    'paid_at' => now(),
                ]);

                $message = 'Pembayaran pesanan dengan nomor: '.$order->order_number.' berhasil.';
            } elseif ($invoice['status'] === 'EXPIRED') {
                $order->update([
                    'status' => 'failed',
                    'cancelation_reason' => 'Dibatalkan oleh sistem: Pesanan kadaluarsa.',
                ]);

                $order->payment->update([
                    'status' => strtolower($invoice['status']),
                ]);

                $message = 'Pesanan dengan nomor: '.$order->order_number.' telah kadaluarsa.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ], 200);
        } catch (ApiRequestException $e) {
            Log::error('Xendit API request exception in Xendit Webhook Controller', [
                'error_type' => 'ApiRequestException',
                'message' => $e->getLogMessage(),
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString(),
                'context' => [
                    'operation' => 'Processing Xendit paid webhook',
                    'component_name' => 'XenditWebhookController.php',
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Xendit exception in Xendit Webhook Controller', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString(),
                'context' => [
                    'operation' => 'Processing Xendit paid webhook',
                    'component_name' => 'XenditWebhookController.php',
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 500);
        }
    }

    public function refunded(Request $request)
    {
        try {
            if (! str_starts_with($request->event, 'refund.')) {
                return response()->json([
                    'success' => false,
                    'message' => 'URL Webhook ini hanya digunakan untuk refund.',
                ], 400);
            }

            $refund = $this->paymentService->getRefund($request->data['id']);

            $order = Order::with('payment.refund')->where('order_number', $refund['reference_id'])->first();

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan dengan nomor pesanan: '.$refund['reference_id'].' tidak ditemukan.',
                ], 404);
            }

            if ($order->payment->refund->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Permintaan refund pada pesanan dengan nomor pesanan: '.$refund['reference_id'].' belum diterima oleh admin.',
                ], 400);
            }

            if ($request->data['status'] === 'SUCCEEDED') {
                $order->payment->refund->update([
                    'xendit_refund_id' => $refund['id'],
                    'status' => 'succeeded',
                    'succeeded_at' => now(),
                ]);

                $responseMessage = 'Permintaan refund pada pesanan dengan nomor pesanan: '.$refund['reference_id'].' berhasil diproses.';
            } elseif ($request->data['status'] !== 'SUCCEEDED') {
                if ($refund['failure_code'] === 'INELIGIBLE_TRANSACTION') {
                    $rejectionReason = 'Kami tidak dapat memproses pengembalian dana untuk transaksi ini. Silakan hubungi kami untuk bantuan lebih lanjut.';
                } elseif ($refund['failure_code'] === 'INSUFFICIENT_BALANCE') {
                    $rejectionReason = 'Pengembalian dana tidak dapat diproses saat ini. Silakan hubungi kami untuk bantuan lebih lanjut.';
                } elseif ($refund['failure_code'] === 'REFUND_TEMPORARILY_UNAVAILABLE') {
                    $rejectionReason = 'Layanan pengembalian dana sedang tidak tersedia untuk sementara. Silakan hubungi kami untuk informasi lebih lanjut.';
                } elseif ($refund['failure_code'] === 'MAXIMUM_USER_BALANCE_EXCEEDED') {
                    $rejectionReason = 'Kami tidak dapat memproses pengembalian dana karena batas saldo telah terlampaui. Silakan hubungi kami untuk bantuan lebih lanjut.';
                } elseif ($refund['failure_code'] === 'INELIGIBLE_PARTIAL_REFUND_TRANSACTION') {
                    $rejectionReason = 'Pengembalian dana sebagian tidak dapat dilakukan untuk transaksi ini. Silakan hubungi kami untuk bantuan selanjutnya.';
                } else {
                    $rejectionReason = 'Pengembalian dana tidak dapat diproses saat ini. Silakan hubungi kami untuk bantuan lebih lanjut.';
                }

                $order->payment->refund->update([
                    'xendit_refund_id' => $refund['id'],
                    'status' => 'failed',
                    'rejection_reason' => $rejectionReason,
                ]);

                $responseMessage = 'Permintaan refund pada pesanan dengan nomor pesanan: '.$refund['reference_id'].' gagal untuk diproses.';
            }

            return response()->json([
                'success' => true,
                'message' => $responseMessage,
            ], 200);
        } catch (ApiRequestException $e) {
            Log::error('Xendit API request exception in Xendit Webhook Controller', [
                'error_type' => 'ApiRequestException',
                'message' => $e->getLogMessage(),
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString(),
                'context' => [
                    'operation' => 'Processing Xendit refund webhook',
                    'component_name' => 'XenditWebhookController.php',
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Xendit exception in Xendit Webhook Controller', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString(),
                'context' => [
                    'operation' => 'Processing Xendit refund webhook',
                    'component_name' => 'XenditWebhookController.php',
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 500);
        }
    }
}
