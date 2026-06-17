<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Validasi prediksi tiap 10 menit
Schedule::command('sipedih:validasi')->everyTenMinutes();

// Pengingat status tiap 5 menit
Schedule::command('sipedih:pengingat')->everyFiveMinutes();

// Cek koneksi data tiap 5 menit
Schedule::command('sipedih:cek-koneksi')->everyFiveMinutes();

// Bot listener — cek command masuk tiap menit
Schedule::command('sipedih:bot-listen')->everyMinute();