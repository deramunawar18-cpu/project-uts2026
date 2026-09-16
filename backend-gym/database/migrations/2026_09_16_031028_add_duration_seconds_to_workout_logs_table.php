<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('workout_logs', function (Blueprint $table) {
        // Kolom durasi waktu latihan (dalam detik, misal 60 untuk 1 menit)
        $table->integer('duration_seconds')->nullable()->default(0)->after('reps');
    });
}

public function down(): void
{
    Schema::table('workout_logs', function (Blueprint $table) {
        $table->dropColumn('duration_seconds');
    });
}
};
