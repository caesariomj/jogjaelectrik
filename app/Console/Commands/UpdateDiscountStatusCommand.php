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
        $discountsToUpdate = \App\Models\Discount::whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('end_date', '>=', \Illuminate\Support\Facades\DB::raw('start_date'))
            ->orWhere(function ($query) {
                return $query->where('used_count', '>=', \Illuminate\Support\Facades\DB::raw('usage_limit'));
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
