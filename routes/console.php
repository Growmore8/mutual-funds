<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pull pool PnL and distribute to clients every day (after market close).
Schedule::command('pool:sync')->dailyAt('23:55')->withoutOverlapping();
