<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EducationTag extends Model
{
    protected $fillable = ['name', 'slug'];

}
