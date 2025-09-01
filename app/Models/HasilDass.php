<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasilDass extends Model
{
    protected $table = 'hasil_dass';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'ibu_id',
        'usia_hamil_id',
        'status',
        'session_token',
        'trimester',
        'dass_id',
        'answers_dass_id',
        'screening_date',
        'started_at',
        'submitted_at',
        'total_depression',
        'total_anxiety',
        'total_stress',
        'rescreen_token_id',
        'batch_no'
    ];

    public function rescreenToken()
    {
        return $this->belongsTo(RescreenToken::class, 'rescreen_token_id');
    }
    
    public function ibu()
    {
        return $this->belongsTo(DataDiri::class, 'ibu_id');
    }
    public function dass()
    {
        return $this->belongsTo(SkriningDass::class, 'dass_id');
    }
    public function answersDass()
    {
        return $this->belongsTo(AnswerDass::class, 'answers_dass_id');
    }
    public function usiaHamil()
    {
        return $this->belongsTo(UsiaHamil::class, 'usia_hamil_id');
    }
}
