<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnswerEpds extends Model
{
    protected $table = 'answers_epds';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'epds_id',
        'jawaban',
        'score',
    ];

    public function epds()
    {
        return $this->belongsTo(SkriningEpds::class, 'epds_id');
    }
}