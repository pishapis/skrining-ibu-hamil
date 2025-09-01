<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnswerDass extends Model
{
    protected $table = 'answers_dass';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'jawaban',
        'score',
    ];
}