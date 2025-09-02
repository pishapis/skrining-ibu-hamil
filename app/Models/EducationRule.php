<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationRule extends Model
{
    protected $fillable = [
        'content_id',
        'screening_type',
        'dimension',
        'min_score',
        'max_score',
        'trimester'
    ];
}
