<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FasilitasKesehatan extends Model
{
    protected $table = 'fasilitas_kesehatan_rujukan';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'nama',
        'alamat',
        'kec',
        'kode_kec',
        'kota',
        'kode_kota',
        'prov',
        'kode_prov',
        'no_telp',
    ];

    public function kec()
    {
        return $this->belongsTo(Kecamatan::class, 'kode_kec', 'code');
    }

    public function kab()
    {
        return $this->belongsTo(Kota::class, 'kode_kota', 'code');
    }

    public function prov()
    {
        return $this->belongsTo(Provinsi::class, 'kode_prov', 'id');
    }
}
