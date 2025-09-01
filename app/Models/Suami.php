<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suami extends Model
{
    protected $table = 'suami';

    protected $fillable = [
        'ibu_id',
        'nama',
        'tempat_lahir',
        'tanggal_lahir',
        'pendidikan_terakhir',
        'pekerjaan',
        'agama',
        'no_telp',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function ibu(): BelongsTo
    {
        return $this->belongsTo(DataDiri::class, 'ibu_id');
    }
}
