<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengamatan', function (Blueprint $table) {
            $table->id();                                        // BIGINT PK Auto Increment

            // Waktu
            $table->dateTime('recorded_at')->index();           // Waktu aktual sensor (dari RTC ESP32)
            
            // Data sensor
            $table->float('temp');                              // Suhu udara (°C)
            $table->float('humidity');                          // Kelembapan relatif (%)
            $table->float('pressure');                          // Tekanan udara (hPa)
            $table->float('rainfall');                          // Curah hujan (mm)

            // Raw confidence model ML (0.00 - 1.00)
            $table->float('prob_no_rain');                      // Kelas 0: Tidak hujan
            $table->float('prob_light_rain');                   // Kelas 1: Hujan ringan
            $table->float('prob_medium_rain');                  // Kelas 2: Hujan sedang
            $table->float('prob_heavy_rain');                   // Kelas 3: Hujan lebat

            // Hasil prediksi
            $table->tinyInteger('pred_class');                  // Kelas final (0/1/2/3)
            $table->tinyInteger('status');                      // Status peringatan dini

            // Baterai VRLA
            $table->float('battery_voltage')->nullable();       // Tegangan baterai (Volt)
            $table->float('battery_percent')->nullable();       // Persentase baterai (%)

            $table->timestamp('created_at')->useCurrent();      // Waktu data diterima server
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengamatan');
    }
};