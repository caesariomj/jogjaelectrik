<?php

namespace App\Http\Controllers\User;

use App\Exceptions\ApiRequestException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Carbon\Carbon;
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
    public function __invoke(Request $request)
    {
        try {
            $invoice = $this->paymentService->getInvoice($request->id);

            $order = Order::with('payment')->find($request->external_id);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan tidak ditemukan.',
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

            if ($invoice['status'] === 'PAID') {
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
                    'paid_at' => Carbon::parse($request->paid_at)->format('Y-m-d H:i:s'),
                ]);

                $message = 'Pembayaran pesanan dengan nomor: '.$order->order_number.' berhasil.';
            } elseif ($invoice['status'] === 'SETTLED') {
                $order->payment->update([
                    'status' => strtolower($invoice['status']),
                ]);

                $message = 'Status pembayaran pesanan dengan nomor: '.$order->order_number.' telah settled.';
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
            Log::error('API Request Exception in Xendit Webhook', [
                'error_message' => $e->getLogMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Unexpected Xendit Webhook Error', [
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 500);
        }
    }
}
