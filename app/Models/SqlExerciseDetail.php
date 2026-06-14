<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SqlExerciseDetail extends Model
{
    protected $primaryKey = 'exercise_id';

    public $incrementing = false;

    protected $fillable = [
        'setup_sql',
        'starter_sql',
        'solution_sql',
    ];

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
