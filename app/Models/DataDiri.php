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
        'is_luar_wilayah',
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

    // Wilayah relationships
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

    public function usiaHamil(): HasOne
    {
        return $this->hasOne(UsiaHamil::class, 'ibu_id');
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
       SCOPES: IBU vs ADMIN CLINICIAN vs SUPERADMIN
       =========================== */

    /**
     * Scope untuk data Ibu (user role 1 dengan faskes_rujukan_id).
     * Contoh pakai: DataDiri::ibu()->paginate(10);
     */
    public function scopeIbu($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->where('role_id', 1);
        })->whereNotNull('faskes_rujukan_id');
    }

    /**
     * Scope untuk admin clinician (user role 2, data di data_diri sebagai profil staf).
     * Ciri: faskes_rujukan_id = null, tanggal_lahir = null, pendidikan_terakhir = null
     * Contoh pakai: DataDiri::adminClinician()->paginate(10);
     */
    public function scopeAdminClinician($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->where('role_id', 2);
        })
        ->whereNull('faskes_rujukan_id')
        ->whereNull('tanggal_lahir')
        ->whereNull('pendidikan_terakhir');
    }

    /**
     * Scope untuk staf Puskesmas (user punya jabatan) - versi lama, masih bisa dipakai.
     */
    public function scopePuskesmasStaff($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->whereNotNull('jabatan_id');
        });
    }

    /**
     * Scope untuk data berdasarkan role user yang sedang login
     * @param $query
     * @param User $user
     * @return mixed
     */
    public function scopeForUser($query, $user)
    {
        if ($user->isSuperadmin()) {
            // Superadmin: lihat semua data ibu
            return $query->ibu();
        }
        
        if ($user->isAdminClinician()) {
            // Admin clinician: lihat data ibu di puskesmas yang sama
            return $query->ibu()->where('puskesmas_id', $user->puskesmas_id);
        }
        
        if ($user->isIbu()) {
            // Ibu: hanya lihat data diri sendiri
            return $query->where('user_id', $user->id);
        }
        
        // Default: tidak ada data (untuk keamanan)
        return $query->whereRaw('1 = 0');
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

    /**
     * Check apakah data ini adalah profil ibu (bukan admin clinician)
     */
    public function getIsIbuProfileAttribute(): bool
    {
        return !is_null($this->faskes_rujukan_id) && 
               !is_null($this->tanggal_lahir) && 
               $this->user && 
               $this->user->role_id == 1;
    }

    /**
     * Check apakah data ini adalah profil admin clinician
     */
    public function getIsAdminClinicianProfileAttribute(): bool
    {
        return is_null($this->faskes_rujukan_id) && 
               is_null($this->tanggal_lahir) && 
               is_null($this->pendidikan_terakhir) && 
               $this->user && 
               $this->user->role_id == 2;
    }

    /* ===========================
       HELPER OPSI SELECT WILAYAH
       =========================== */

    public static function optionsProvinsi()
    {
        return Provinsi::query()
            ->orderBy('name')
            ->pluck('name', 'code');
    }

    public static function optionsKota(?string $kodeProv)
    {
        if (!$kodeProv) return collect();
        return Kota::query()
            ->where('prov_code', $kodeProv)
            ->orderBy('name')
            ->pluck('name', 'code');
    }

    public static function optionsKecamatan(?string $kodeKab)
    {
        if (!$kodeKab) return collect();
        return Kecamatan::query()
            ->where('kab_code', $kodeKab)
            ->orderBy('name')
            ->pluck('name', 'code');
    }

    public static function optionsKelurahan(?string $kodeKec)
    {
        if (!$kodeKec) return collect();
        return Kelurahan::query()
            ->where('kec_code', $kodeKec)
            ->orderBy('name')
            ->pluck('name', 'code');
    }
}