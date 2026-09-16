<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    use HasFactory;

    protected $table = 'exercises';

    // Kolom-kolom yang ada di database MySQL:
    protected $fillable = [
        'name',
        'target_muscle',
        'equipment',
        'description',
    ];
}