<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DataDiri extends Model
{
    protected $table = 'data_diri';

    protected $fillable = [
        'user_id',
        'nama',
        'nik',
        'tempat_lahir',
        'tanggal_lahir',
        'pendidikan_terakhir',
        'pekerjaan',
        'agama',
        'golongan_darah',
        'alamat_rumah',
        'kode_kec',
        'kode_kab',
        'no_telp',
        'no_jkn',
        'puskesmas_id',
        'faskes_rujukan_id',
        'kode_prov',
        'kode_des'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kec()
    {
        return $this->belongsTo(Kecamatan::class, 'kode_kec', 'code');
    }

    public function kab()
    {
        return $this->belongsTo(Kota::class, 'kode_kab', 'code');
    }

    public function kel()
    {
        return $this->belongsTo(Kelurahan::class, 'kode_des', 'code');
    }

    public function prov()
    {
        return $this->belongsTo(Provinsi::class, 'kode_prov', 'code');
    }

    public function suami(): HasOne
    {
        return $this->hasOne(Suami::class, 'ibu_id');
    }

    public function puskesmas(): BelongsTo
    {
        return $this->belongsTo(Puskesmas::class, 'puskesmas_id');
    }

    public function faskes(): BelongsTo
    {
        return $this->belongsTo(FasilitasKesehatan::class, 'faskes_rujukan_id');
    }

    public function anak(): HasOne
    {
        return $this->hasOne(Anak::class, 'ibu_id');
    }
}
