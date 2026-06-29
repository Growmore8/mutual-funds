<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto: pull pool PnL from CubeX every minute and distribute newly-realized
// (closed) profit to clients automatically — no manual sync needed.
Schedule::command('pool:sync')->everyMinute()->withoutOverlapping();

// Spot Trading: rebuild the house order book around the live price every minute.
Schedule::command('spot:seed')->everyMinute()->withoutOverlapping();

// Spot Trading: stream live prices into the DB every ~5s (runs ~55s, scheduled each minute = continuous).
Schedule::command('spot:stream')->everyMinute()->withoutOverlapping();

// Auto statements: every Monday (previous week) and the 1st of each month (previous month).
Schedule::command('statements:send weekly')->weeklyOn(1, '06:00');
Schedule::command('statements:send monthly')->monthlyOn(1, '06:00');
