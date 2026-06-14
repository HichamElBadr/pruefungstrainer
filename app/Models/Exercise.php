<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Exercise extends Model
{
    protected $fillable = [
        'external_id',
        'category_id',
        'type',
        'topic',
        'difficulty',
        'title',
        'task',
        'explanation',
        'hints',
        'source',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'hints' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sqlDetail(): HasOne
    {
        return $this->hasOne(SqlExerciseDetail::class);
    }

    public function calculationDetail(): HasOne
    {
        return $this->hasOne(CalculationExerciseDetail::class);
    }

    public function umlDetail(): HasOne
    {
        return $this->hasOne(UmlExerciseDetail::class);
    }
}
