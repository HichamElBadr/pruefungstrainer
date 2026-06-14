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
        'starter_plantuml',
        'solution_plantuml',
    ];

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
