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
        'mode',
        'periode',
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

    public function scopeKehamilan($query)
    {
        return $query->whereNotNull('usia_hamil_id')
                    ->where('mode', 'kehamilan');
    }

    public function scopeUmum($query)
    {
        return $query->whereNull('usia_hamil_id')
                    ->where('mode', 'umum');
    }

    public function scopeByTrimester($query, $trimester)
    {
        return $query->kehamilan()->where('trimester', $trimester);
    }

    public function scopeByPeriode($query, $periode)
    {
        return $query->umum()->where('periode', $periode);
    }

    public function getIsKehamilanAttribute(): bool
    {
        return !is_null($this->usia_hamil_id) && 
               $this->mode === 'kehamilan';
    }

    public function getIsUmumAttribute(): bool
    {
        return is_null($this->usia_hamil_id) && 
               $this->mode === 'umum';
    }
    
    /**
     * Get label jenis skrining
     */
    public function getJenisLabelAttribute(): string
    {
        return $this->is_kehamilan ? 'Kehamilan' : 'Umum';
    }
    
    /**
     * Get identifier (trimester atau periode)
     */
    public function getIdentifierAttribute(): string
    {
        if ($this->is_kehamilan) {
            return $this->trimester ?? '-';
        }
        return $this->periode ?? '-';
    }
}
