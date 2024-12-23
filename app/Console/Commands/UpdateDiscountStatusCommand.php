<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateDiscountStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discounts:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the expired discount status after the start date exceeds or equals the end date, or when the used count exceeds the usage limit.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $discountsToUpdate = \App\Models\Discount::where(function ($query) {
            $query->whereNotNull('start_date')
                ->whereNotNull('end_date')
                ->where('end_date', '<', now()->toDateString());
        })
            ->orWhere(function ($query) {
                $query->whereNotNull('usage_limit')
                    ->whereColumn('used_count', '>=', 'usage_limit');
            })
            ->get();

        foreach ($discountsToUpdate as $discount) {
            $discount->is_active = false;
            $discount->save();

            \Illuminate\Support\Facades\Log::info("Disount with name {$discount->name} status updated to inactive after the start date exceeds or equals the end date, or when the used count exceeds the usage limit.");
        }

        $this->info("{$discountsToUpdate->count()} discounts updated to inactive status.");
    }
}
