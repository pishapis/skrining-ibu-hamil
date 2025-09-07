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
        'kode_prov',
        'kode_kab',
        'kode_kec',
        'kode_des',
        'no_telp',
        'no_jkn',
        'puskesmas_id',
        'faskes_rujukan_id',
    ];

    /* ===========================
       RELATIONSHIPS
       =========================== */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Wilayah (kamu sudah punya versi shortname; biarkan & tambah alias verbose)
    public function kec(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kode_kec', 'code');
    }
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kode_kec', 'code');
    }

    public function kab(): BelongsTo
    {
        return $this->belongsTo(Kota::class, 'kode_kab', 'code');
    }
    public function kota(): BelongsTo
    {
        return $this->belongsTo(Kota::class, 'kode_kab', 'code');
    }

    public function kel(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class, 'kode_des', 'code');
    }
    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class, 'kode_des', 'code');
    }

    public function prov(): BelongsTo
    {
        return $this->belongsTo(Provinsi::class, 'kode_prov', 'code');
    }

    public function puskesmas(): BelongsTo
    {
        return $this->belongsTo(Puskesmas::class, 'puskesmas_id');
    }

    public function faskes(): BelongsTo
    {
        return $this->belongsTo(FasilitasKesehatan::class, 'faskes_rujukan_id');
    }

    public function suami(): HasOne
    {
        return $this->hasOne(Suami::class, 'ibu_id');
    }

    public function anak(): HasOne
    {
        return $this->hasOne(Anak::class, 'ibu_id');
    }

    /* ===========================
       SCOPES: IBU vs PUSKESMAS
       =========================== */

    /**
     * Scope untuk data Ibu (user tanpa jabatan).
     * Contoh pakai: DataDiri::ibu()->paginate(10);
     */
    public function scopeIbu($query)
    {
        return $query->whereDoesntHave('user', function ($q) {
            $q->whereNotNull('jabatan_id');
        });
    }

    /**
     * Scope untuk staf Puskesmas (user punya jabatan).
     * Contoh pakai: DataDiri::puskesmasStaff()->paginate(10);
     */
    public function scopePuskesmasStaff($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->whereNotNull('jabatan_id');
        });
    }

    /* ===========================
       SCOPES: WILAYAH FILTER
       =========================== */

    public function scopeWhereProv($query, $kodeProv = null)
    {
        return $kodeProv ? $query->where('kode_prov', $kodeProv) : $query;
    }

    public function scopeWhereKab($query, $kodeKab = null)
    {
        return $kodeKab ? $query->where('kode_kab', $kodeKab) : $query;
    }

    public function scopeWhereKec($query, $kodeKec = null)
    {
        return $kodeKec ? $query->where('kode_kec', $kodeKec) : $query;
    }

    public function scopeWhereDes($query, $kodeDes = null)
    {
        return $kodeDes ? $query->where('kode_des', $kodeDes) : $query;
    }

    /* ===========================
       ACCESSORS / ATTRIBUTES
       =========================== */

    public function getAlamatLengkapAttribute(): string
    {
        $bagian = array_filter([
            $this->alamat_rumah,
            optional($this->kel)->name,
            optional($this->kec)->name,
            optional($this->kab)->name,
            optional($this->prov)->name,
        ]);

        return implode(', ', $bagian);
    }

    public function getNamaPuskesmasAttribute(): ?string
    {
        return $this->puskesmas?->nama;
    }

    public function getNamaFaskesRujukanAttribute(): ?string
    {
        return $this->faskes?->nama;
    }

    /* ===========================
       HELPER OPSI SELECT WILAYAH
       (Hirarki Prov > Kota > Kec > Kel)
       =========================== */

    /**
     * Ambil opsi provinsi: [code => name]
     */
    public static function optionsProvinsi()
    {
        return Provinsi::query()
            ->orderBy('name')
            ->pluck('name', 'code');
    }

    /**
     * Ambil opsi kota berdasar kode provinsi.
     * @param  string|null $kodeProv
     */
    public static function optionsKota(?string $kodeProv)
    {
        if (!$kodeProv) return collect();
        return Kota::query()
            ->where('prov_code', $kodeProv)
            ->orderBy('name')
            ->pluck('name', 'code');
    }

    /**
     * Ambil opsi kecamatan berdasar kode kota.
     * @param  string|null $kodeKab
     */
    public static function optionsKecamatan(?string $kodeKab)
    {
        if (!$kodeKab) return collect();
        return Kecamatan::query()
            ->where('kab_code', $kodeKab)
            ->orderBy('name')
            ->pluck('name', 'code');
    }

    /**
     * Ambil opsi kelurahan berdasar kode kecamatan.
     * @param  string|null $kodeKec
     */
    public static function optionsKelurahan(?string $kodeKec)
    {
        if (!$kodeKec) return collect();
        return Kelurahan::query()
            ->where('kec_code', $kodeKec)
            ->orderBy('name')
            ->pluck('name', 'code');
    }
}
