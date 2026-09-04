<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Contoh: "Barbell Bench Press"
            $table->string('muscle_group'); // Contoh: "Chest", "Back", "Legs", "Shoulders", "Arms", "Core"
            $table->string('equipment')->default('Barbell'); // "Barbell", "Dumbbell", "Machine", "Cable", "Bodyweight"
            $table->text('instructions')->nullable(); // Petunjuk gerakan
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};