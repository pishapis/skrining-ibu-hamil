<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kota extends Model
{
    protected $table = 'indonesia_cities';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'code','province_code','name','meta'
    ];

    public function provinsi()
    {
        return $this->belongsTo(Provinsi::class, 'province_code', 'code');
    }

    public function puskesmas()
    {
        return $this->hasMany(Puskesmas::class, 'kode_kota', 'code');
    }
}