<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'role_id',
        'jabatan_id',
        'puskesmas_id',
        'name',
        'email',
        'username',
        'password',
        'is_temp'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }

    public function puskesmas()
    {
        return $this->belongsTo(Puskesmas::class, 'puskesmas_id');
    }

    public function dataDiri()
    {
        return $this->hasOne(DataDiri::class, 'user_id');
    }

    public function profileStaf()
    {
        return $this->hasOne(DataDiri::class, 'user_id')
            ->whereNull('faskes_rujukan_id')
            ->whereNull('tanggal_lahir')
            ->whereNull('pendidikan_terakhir');
    }

    // ========== HELPER METHODS ==========
    
    public function isIbu(): bool
    {
        return $this->role_id == 1;
    }

    public function isAdminClinician(): bool
    {
        return $this->role_id == 2;
    }

    public function isSuperadmin(): bool
    {
        return $this->role_id == 3;
    }

    /**
     * Check if user is admin or superadmin
     */
    public function isAdmin(): bool
    {
        return in_array($this->role_id, [2, 3]);
    }

    /**
     * Get role name
     */
    public function getRoleNameAttribute(): string
    {
        return match($this->role_id) {
            1 => 'Ibu',
            2 => 'Admin Clinician',
            3 => 'Superadmin',
            default => 'Unknown'
        };
    }

    /**
     * Get NIK from DataDiri (for ibu only)
     */
    public function getNikAttribute(): ?string
    {
        return $this->dataDiri?->nik;
    }

    /**
     * Static method: Find user by NIK
     */
    public static function findByNik(string $nik): ?self
    {
        $dataDiri = DataDiri::where('nik', $nik)
            ->whereNotNull('faskes_rujukan_id')
            ->first();

        return $dataDiri?->user;
    }

    public static function optionsJabatans()
    {
        return Jabatan::query()
            ->orderBy('nama')
            ->pluck('nama', 'id');
    }
}