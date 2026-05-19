<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'difficulty',
        'source',
        'prompt',
        'generated_task',
        'solution',
        'expected_unit',
        'sample_solution',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
