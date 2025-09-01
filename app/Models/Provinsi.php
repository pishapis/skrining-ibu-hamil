<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provinsi extends Model
{
    protected $table = 'indonesia_provinces';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'code','name','meta'
    ];

    public function puskesmas()
    {
        return $this->hasMany(Puskesmas::class, 'kode_prov', 'id');
    }
}