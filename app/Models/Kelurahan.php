<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelurahan extends Model
{
    protected $table = 'indonesia_villages';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'code','district_code','name','meta'
    ];

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class, 'district_code', 'code');
    }
}