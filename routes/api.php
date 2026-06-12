<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\Api\PengamatanController;

Route::post('/data', [PengamatanController::class, 'store']);
Route::get('/data/latest', [PengamatanController::class, 'latest']);