<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengamatan extends Model
{
    protected $table = 'pengamatan';

    public $timestamps = false;

    protected $fillable = [
        'recorded_at',
        'temp',
        'humidity',
        'pressure',
        'rainfall',
        'prob_no_rain',
        'prob_light_rain',
        'prob_medium_rain',
        'pred_class',
        'status',
        'status_peringatan',
        'kelas_aktual',
        'rainfall_actual_1h',
        'sudah_validasi',
        'battery_voltage',
        'battery_percent',
        'is_test',
        'is_warmup',
    ];

    protected $casts = [
        'recorded_at'        => 'datetime',
        'temp'               => 'float',
        'humidity'           => 'float',
        'pressure'           => 'float',
        'rainfall'           => 'float',
        'prob_no_rain'       => 'float',
        'prob_light_rain'    => 'float',
        'prob_medium_rain'   => 'float',
        'pred_class'         => 'integer',
        'status'             => 'integer',
        'status_peringatan'  => 'integer',
        'kelas_aktual'       => 'integer',
        'rainfall_actual_1h' => 'float',
        'sudah_validasi'     => 'boolean',
        'battery_voltage'    => 'float',
        'battery_percent'    => 'float',
        'is_test'            => 'boolean',
        'is_warmup'          => 'boolean',
    ];
}