<?php

use App\Jobs\ExpireExpiredCoupons;
use App\Jobs\GenerateSalesReport;
use App\Jobs\SendLowStockAlerts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ExpireExpiredCoupons)->dailyAt('00:05');
Schedule::job(new SendLowStockAlerts)->hourlyAt(5);
Schedule::job(new GenerateSalesReport)->dailyAt('23:55');
