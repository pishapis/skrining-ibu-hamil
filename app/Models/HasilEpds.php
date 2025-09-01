<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasilEpds extends Model
{
    protected $table = 'hasil_epds';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'ibu_id','epds_id','answers_epds_id','screening_date','usia_hamil_id',
        'status','session_token','trimester','total_score','started_at','submitted_at'
    ];

    public function ibu()        { return $this->belongsTo(DataDiri::class,'ibu_id'); }
    public function epds()       { return $this->belongsTo(SkriningEpds::class,'epds_id'); }
    public function answersEpds(){ return $this->belongsTo(AnswerEpds::class,'answers_epds_id'); }
    public function usiaHamil()  { return $this->belongsTo(UsiaHamil::class,'usia_hamil_id'); }
}