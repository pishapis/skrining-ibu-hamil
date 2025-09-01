<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkriningDass extends Model
{
    protected $table = 'skrining_dass';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'pertanyaan','dimensi'
    ];
}