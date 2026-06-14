<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalculationExerciseDetail extends Model
{
    protected $primaryKey = 'exercise_id';

    public $incrementing = false;

    protected $fillable = [
        'expected_value',
        'tolerance',
        'unit',
        'solution_steps',
    ];

    protected function casts(): array
    {
        return [
            'expected_value' => 'decimal:6',
            'tolerance' => 'decimal:6',
        ];
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
