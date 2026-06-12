<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengamatan', function (Blueprint $table) {
            // Status peringatan resmi hasil smoothing (0-4)
            $table->tinyInteger('status_peringatan')->nullable()->after('status');

            // Validasi: kelas aktual berdasarkan akumulasi hujan 1 jam ke depan
            $table->tinyInteger('kelas_aktual')->nullable()->after('status_peringatan');

            // Total curah hujan aktual 1 jam ke depan (mm)
            $table->float('rainfall_actual_1h')->nullable()->after('kelas_aktual');

            // Penanda apakah baris ini sudah divalidasi
            $table->boolean('sudah_validasi')->default(false)->after('rainfall_actual_1h');
        });
    }

    public function down(): void
    {
        Schema::table('pengamatan', function (Blueprint $table) {
            $table->dropColumn([
                'status_peringatan',
                'kelas_aktual',
                'rainfall_actual_1h',
                'sudah_validasi',
            ]);
        });
    }
};