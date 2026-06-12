<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebController;

Route::get('/',         [WebController::class, 'dashboard'])->name('dashboard');
Route::get('/riwayat',  [WebController::class, 'riwayat'])->name('riwayat');
Route::get('/unduh',    [WebController::class, 'unduh'])->name('unduh');
Route::get('/unduh/export', [WebController::class, 'export'])->name('unduh.export');