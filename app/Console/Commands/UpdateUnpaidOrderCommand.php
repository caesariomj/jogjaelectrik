<?php

namespace App\Console\Commands;

use App\Services\PaymentService;
use Illuminate\Console\Command;

class UpdateUnpaidOrderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update unpaid order status after 24 hours of creation';

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
        $ordersToUpdate = \App\Models\Order::with('payment')->expired()->get();

        foreach ($ordersToUpdate as $order) {
            $order->status = 'failed';
            $order->save();

            $payment = $order->payment;

            if ($payment) {
                if ($payment->status === 'unpaid') {
                    $payment->status = 'expired';
                    $payment->save();

                    $this->paymentService->expireInvoice($payment->xendit_invoice_id);
                }
            }

            \Illuminate\Support\Facades\Log::info("Unpaid order with ID #{$order->id} status updated to failed after 24 hours.");
        }

        $this->info("{$ordersToUpdate->count()} orders updated to failed status.");
    }
}
