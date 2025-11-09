<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RescreenToken extends Model
{
    protected $table = 'rescreen_tokens';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'ibu_id',
        'usia_hamil_id',
        'mode',
        'periode',
        'jenis',
        'trimester',
        'issued_by',
        'reason',
        'expires_at',
        'max_uses',
        'used_count',
        'status'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'max_uses' => 'integer',
        'used_count' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($m) {
            if (empty($m->id)) {
                $m->id = (string) Str::uuid();
            }
        });
    }

    // ==================== RELATIONSHIPS ====================
    
    public function ibu(): BelongsTo
    {
        return $this->belongsTo(DataDiri::class, 'ibu_id');
    }

    public function usiaHamil(): BelongsTo
    {
        return $this->belongsTo(UsiaHamil::class, 'usia_hamil_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'issued_by');
    }

    // ==================== SCOPES ====================
    
    /**
     * Token yang masih aktif dan belum expired
     */
    public function scopeActive($q)
    {
        return $q->where('status', 'active')
            ->where(function ($qq) {
                $qq->whereNull('expires_at')
                   ->orWhere('expires_at', '>', now());
            })
            ->whereColumn('used_count', '<', 'max_uses');
    }

    /**
     * Token untuk jenis tertentu (epds/dass)
     */
    public function scopeForType($q, string $type)
    {
        return $q->where('jenis', $type);
    }

    /**
     * Token untuk trimester tertentu
     */
    public function scopeForTrimester($q, string $trimester)
    {
        return $q->where('trimester', $trimester);
    }

    // ==================== ACCESSORS ====================
    
    /**
     * Apakah token masih bisa digunakan?
     */
    public function getIsUsableAttribute(): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->used_count >= $this->max_uses) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        return true;
    }

    /**
     * Sisa kuota
     */
    public function getRemainingUsesAttribute(): int
    {
        return max(0, $this->max_uses - $this->used_count);
    }

    /**
     * Label status human-readable
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Aktif',
            'used' => 'Terpakai',
            'revoked' => 'Dicabut',
            default => 'Tidak Diketahui'
        };
    }

    /**
     * Label trimester human-readable
     */
    public function getTrimesterLabelAttribute(): string
    {
        return match($this->trimester) {
            'trimester_1' => 'Trimester I',
            'trimester_2' => 'Trimester II',
            'trimester_3' => 'Trimester III',
            'pasca_hamil' => 'Pasca Melahirkan',
            default => '-'
        };
    }

    // ==================== METHODS ====================
    
    /**
     * Gunakan token (increment used_count)
     */
    public function use(): bool
    {
        if (!$this->is_usable) {
            return false;
        }

        $this->increment('used_count');
        
        // Auto-update status jika sudah mencapai max_uses
        if ($this->used_count >= $this->max_uses) {
            $this->status = 'used';
            $this->save();
        }

        return true;
    }

    /**
     * Cek apakah token sudah expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Revoke (cabut) token
     */
    public function revoke(string $reason = null): bool
    {
        $this->status = 'revoked';
        if ($reason) {
            $this->reason = ($this->reason ? $this->reason . ' | ' : '') . 
                           "Dicabut: $reason";
        }
        return $this->save();
    }
}