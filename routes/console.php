<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('invoices:mark-overdue')->dailyAt('06:00')->timezone('Europe/London');
Schedule::command('invoices:send-chases')->dailyAt('09:00')->timezone('Europe/London')->withoutOverlapping();
Schedule::command('bank-feed:pull')->hourly()->withoutOverlapping();
Schedule::command('accounting:check-bank-expiry')->dailyAt('05:30')->timezone('Europe/London');
