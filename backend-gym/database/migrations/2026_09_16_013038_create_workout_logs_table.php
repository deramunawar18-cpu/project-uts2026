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
    Schema::create('workout_logs', function (Blueprint $table) {
        $table->id();
        // Relasi ke user yang login
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        // Relasi ke latihan yang dipilih
        $table->foreignId('exercise_id')->constrained('exercises')->onDelete('cascade');
        
        $table->integer('set_number')->default(1); // Set ke 1, 2, 3...
        $table->decimal('weight', 8, 2);           // Beban dalam kg (misal: 60.5)
        $table->integer('reps');                   // Jumlah repetisi (misal: 10)
        $table->date('workout_date');              // Tanggal latihan
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_logs');
    }
};
