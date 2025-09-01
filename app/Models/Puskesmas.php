<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Puskesmas extends Model
{
    protected $table = 'puskesmas';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'nama','alamat','kec','kode_kec','kota','kode_kota','prov','kode_prov','faskes_rujukan_id'
    ];

    public function faskes()
    {
        return $this->belongsTo(FasilitasKesehatan::class, 'faskes_rujukan_id', 'id');
    }

    public function prov()
    {
        return $this->belongsTo(Provinsi::class, 'kode_prov', 'id');
    }

    public function kab()
    {
        return $this->belongsTo(Kota::class, 'kode_kota', 'code');
    }

    public function kec()
    {
        return $this->belongsTo(Kecamatan::class, 'kode_kec', 'code');
    }

}