<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Validasi prediksi tiap 10 menit
Schedule::command('sipedih:validasi')->everyTenMinutes();

// Pengingat status Waspada/Awas — dicek tiap 5 menit
Schedule::command('sipedih:pengingat')->everyFiveMinutes();