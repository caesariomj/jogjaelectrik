<?php

use App\Console\Commands\UpdateDiscountStatusCommand;
use App\Console\Commands\UpdateOverdueOrderCommand;
use App\Console\Commands\UpdateUnpaidOrderCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command(UpdateUnpaidOrderCommand::class)->daily();
Schedule::command(UpdateDiscountStatusCommand::class)->daily();
Schedule::command(UpdateOverdueOrderCommand::class)->daily();
