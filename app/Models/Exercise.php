<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
        protected $fillable = ['category_id', 'prompt', 'generated_task', 'solution'];

        public function category()
        {
                return $this->belongsTo(Category::class);
        }
}
