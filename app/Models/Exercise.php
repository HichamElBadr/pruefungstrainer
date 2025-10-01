<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
        protected $fillable = ['category','prompt','generated_task','solution'];
}
