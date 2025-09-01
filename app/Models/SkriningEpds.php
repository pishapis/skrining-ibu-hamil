<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkriningEpds extends Model
{
    protected $table = 'skrining_epds';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'pertanyaan',
    ];
}