<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Anak extends Model
{
    protected $table = 'anak';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'ibu_id',
        'nama',
        'tanggal_lahir',
        'jenis_kelamin',
        'no_jkn',
        'catatan'
    ];

    public function ibu()
    {
        return $this->belongsTo(DataDiri::class, 'ibu_id');
    }
}