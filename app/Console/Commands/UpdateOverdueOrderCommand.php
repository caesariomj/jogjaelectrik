<?php

namespace App\Console\Commands;

use App\Services\PaymentService;
use Illuminate\Console\Command;

class UpdateOverdueOrderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:update-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update overdue orders to failed status';

    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        parent::__construct();

        $this->paymentService = $paymentService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ordersToUpdate = \App\Models\Order::with('payment')->overdue()->get();

        $updatedCount = 0;

        foreach ($ordersToUpdate as $order) {
            $order->status = 'failed';
            $order->cancelation_reason = 'Dibatalkan oleh sistem: Pesanan tidak diproses / dikirim oleh admin';
            $order->save();

            $payment = $order->payment;

            if ($payment) {
                if (in_array($payment->status, ['paid', 'settled'])) {
                    $payment->status = 'refunded';
                    $payment->save();

                    \App\Models\Refund::create([
                        'payment_id' => $payment->id,
                    ]);
                } elseif ($payment->status === 'unpaid') {
                    $payment->status = 'expired';
                    $payment->save();

                    $this->paymentService->expireInvoice($payment->xendit_invoice_id);
                }
            }

            \Illuminate\Support\Facades\Log::info("Overdue order with ID #{$order->id} status updated to failed. Type: ".($order->estimated_shipping_max_days == 0 ? 'Same Day' : 'Reguler'));
            $updatedCount++;
        }

        $this->info("{$updatedCount} orders updated to failed status.");
    }
}
