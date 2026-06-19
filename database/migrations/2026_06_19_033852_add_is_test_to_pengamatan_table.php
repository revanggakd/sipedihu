<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengamatan', function (Blueprint $table) {
            if (!Schema::hasColumn('pengamatan', 'is_test')) {
                $table->boolean('is_test')->default(false)->after('status_peringatan');
                $table->index('is_test');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pengamatan', function (Blueprint $table) {
            if (Schema::hasColumn('pengamatan', 'is_test')) {
                $table->dropIndex(['is_test']);
                $table->dropColumn('is_test');
            }
        });
    }
};

