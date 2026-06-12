<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengamatan', function (Blueprint $table) {
            $table->dropColumn('prob_heavy_rain');
        });
    }

    public function down(): void
    {
        Schema::table('pengamatan', function (Blueprint $table) {
            $table->float('prob_heavy_rain')->nullable()->after('prob_medium_rain');
        });
    }
};