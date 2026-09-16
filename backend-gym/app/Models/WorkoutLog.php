<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exercise_id',
        'set_number',
        'weight',
        'reps',
        'duration_seconds',
        'workout_date',
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Latihan (agar nama latihan & target otot otomatis terbawa)
    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }
}