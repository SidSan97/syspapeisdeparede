<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Sincroniza preços do Tiny ERP e só reprecifica quando houver mudança.
Schedule::command('tinyerp:sync-prices-and-reprice')->dailyAt('00:30');
