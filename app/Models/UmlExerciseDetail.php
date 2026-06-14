<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmlExerciseDetail extends Model
{
    protected $primaryKey = 'exercise_id';

    public $incrementing = false;

    protected $fillable = [
        'diagram_type',
        'scenario',
        'requirements',
        'starter_plantuml',
        'solution_plantuml',
        'expected_elements',
    ];

    protected function casts(): array
    {
        return [
            'requirements' => 'array',
            'expected_elements' => 'array',
        ];
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
