<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatKesehatan extends Model
{
    protected $table = 'riwayat_kesehatan';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'ibu_id',
        'kehamilan_ke',
        'jml_anak_lahir_hidup',
        'riwayat_keguguran',
        'riwayat_penyakit',
    ];

    protected $casts = [
        'riwayat_penyakit' => 'array',
    ];

    public function ibu()
    {
        return $this->belongsTo(DataDiri::class, 'ibu_id');
    }
}
