<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exercise; // <--- PASTIKAN BARIS INI ADA!

class ExerciseSeeder extends Seeder
{
    public function run(): void
    {
        $exercises = [
            ['name' => 'Bench Press', 'target_muscle' => 'Chest', 'equipment' => 'Barbell', 'description' => 'Latihan dada utama.'],
            ['name' => 'Incline Dumbbell Press', 'target_muscle' => 'Chest', 'equipment' => 'Dumbbell', 'description' => 'Fokus dada bagian atas.'],
            ['name' => 'Lat Pulldown', 'target_muscle' => 'Back', 'equipment' => 'Cable', 'description' => 'Latihan melebarkan punggung.'],
            ['name' => 'Barbell Squat', 'target_muscle' => 'Legs', 'equipment' => 'Barbell', 'description' => 'Latihan paha dan bokong.'],
            ['name' => 'Overhead Press', 'target_muscle' => 'Shoulders', 'equipment' => 'Barbell', 'description' => 'Latihan otot bahu.'],
            ['name' => 'Bicep Curl', 'target_muscle' => 'Arms', 'equipment' => 'Dumbbell', 'description' => 'Latihan isolasi bicep.'],
        ];

        foreach ($exercises as $item) {
            Exercise::create($item);
        }
    }
}