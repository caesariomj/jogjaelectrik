<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateOrderStatusCommand extends Command
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

            if ($payment->status === 'pending') {
                $payment->status = 'expire';
                $payment->save();
            }

            \Illuminate\Support\Facades\Log::info("Unpaid order with ID #{$order->id} status updated to failed after 24 hours.");
        }

        $this->info("{$ordersToUpdate->count()} orders updated to failed status.");
    }
}
