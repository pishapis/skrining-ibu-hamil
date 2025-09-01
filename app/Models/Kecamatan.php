<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kecamatan extends Model
{
    protected $table = 'indonesia_districts';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'code','city_code','name','meta'
    ];

    public function kota()
    {
        return $this->belongsTo(Kota::class, 'city_code', 'code');
    }

    public function puskesmas()
    {
        return $this->hasMany(Puskesmas::class, 'kode_kec', 'code');
    }
}