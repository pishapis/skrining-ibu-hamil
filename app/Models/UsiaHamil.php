<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsiaHamil extends Model
{
    protected $table = 'usia_hamil';

    protected $fillable = [
        'ibu_id',
        'perkiraan_usia_kehamilan',
        'hpht',
        'hpl',
        'tanggal_lahir',
        'keterangan',
        'trimester_1', // Kolom untuk trimester pertama
        'trimester_2', // Kolom untuk trimester kedua
        'trimester_3', // Kolom untuk trimester ketiga
        'pasca_hamil'
    ];

    protected $casts = [
        'hpht' => 'date',
        'hpl' => 'date',
        'created_at' => 'datetime',
    ];

    public function ibu(): BelongsTo
    {
        return $this->belongsTo(DataDiri::class, 'ibu_id');
    }
}
