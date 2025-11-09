<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GeneratedLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'puskesmas_id',
        'original_url',
        'short_url',
        'short_code',
        'token',
        'expires_at',
        'created_by',
        'is_active',
        'access_count',
        'last_accessed_at',
        'last_access_ip',
        'last_user_agent',
        'qr_code'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'is_active' => 'boolean',
        'access_count' => 'integer'
    ];

    protected $hidden = [
        'token' // Hide token dari JSON output untuk keamanan
    ];

    /**
     * Relasi ke model Puskesmas
     */
    public function puskesmas()
    {
        return $this->belongsTo(Puskesmas::class);
    }

    /**
     * Relasi ke model User (creator)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check apakah link sudah expired
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at < now();
    }

    /**
     * Check apakah link masih aktif dan belum expired
     */
    public function isValid()
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Scope untuk link yang aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk link yang belum expired
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope untuk link yang valid (aktif dan belum expired)
     */
    public function scopeValid($query)
    {
        return $query->active()->notExpired();
    }

    /**
     * Scope untuk link berdasarkan puskesmas
     */
    public function scopeByPuskesmas($query, $puskesmasId)
    {
        return $query->where('puskesmas_id', $puskesmasId);
    }

    /**
     * Get short URL attribute - generate jika belum ada
     */
    public function getShortUrlAttribute($value)
    {
        if (empty($value) && !empty($this->short_code)) {
            return route('skrining.redirect', $this->short_code);
        }

        return $value;
    }

    /**
     * Get formatted access count
     */
    public function getFormattedAccessCountAttribute()
    {
        if ($this->access_count >= 1000000) {
            return number_format($this->access_count / 1000000, 1) . 'M';
        } elseif ($this->access_count >= 1000) {
            return number_format($this->access_count / 1000, 1) . 'K';
        }

        return number_format($this->access_count);
    }

    /**
     * Get time since last access
     */
    public function getTimeSinceLastAccessAttribute()
    {
        if (!$this->last_accessed_at) {
            return 'Belum pernah diakses';
        }

        return $this->last_accessed_at->diffForHumans();
    }

    /**
     * Get status badge
     */
    public function getStatusBadgeAttribute()
    {
        if (!$this->is_active) {
            return '<span class="badge badge-danger">Nonaktif</span>';
        }

        if ($this->isExpired()) {
            return '<span class="badge badge-warning">Kedaluwarsa</span>';
        }

        return '<span class="badge badge-success">Aktif</span>';
    }

    /**
     * Boot method untuk events
     */
    protected static function boot()
    {
        parent::boot();

        // Event ketika link dibuat
        static::creating(function ($link) {
            // Auto-generate short_url jika belum ada
            if (empty($link->short_url) && !empty($link->short_code)) {
                $link->short_url = route('skrining.redirect', $link->short_code);
            }
        });

        // Event ketika link diupdate
        static::updating(function ($link) {
            // Log perubahan status
            if ($link->isDirty('is_active')) {
                Log::info('Link status changed', [
                    'short_code' => $link->short_code,
                    'old_status' => $link->getOriginal('is_active'),
                    'new_status' => $link->is_active,
                    'changed_by' => Auth::user()->id
                ]);
            }
        });
    }

    /**
     * Get analytics data untuk link ini
     */
    public function getAnalytics()
    {
        return [
            'basic_info' => [
                'short_code' => $this->short_code,
                'created_at' => $this->created_at,
                'puskesmas' => $this->puskesmas->name,
                'creator' => $this->creator->name ?? 'System'
            ],
            'access_stats' => [
                'total_access' => $this->access_count,
                'last_accessed' => $this->last_accessed_at,
                'time_since_last_access' => $this->time_since_last_access,
                'never_accessed' => $this->access_count === 0
            ],
            'status' => [
                'is_active' => $this->is_active,
                'is_expired' => $this->isExpired(),
                'is_valid' => $this->isValid(),
                'expires_at' => $this->expires_at
            ]
        ];
    }

    /**
     * Deactivate link
     */
    public function deactivate($reason = null)
    {
        $this->update(['is_active' => false]);

        Log::info('Link deactivated', [
            'short_code' => $this->short_code,
            'reason' => $reason,
            'deactivated_by' => Auth::user()->id
        ]);

        return $this;
    }

    /**
     * Activate link
     */
    public function activate()
    {
        $this->update(['is_active' => true]);

        Log::info('Link activated', [
            'short_code' => $this->short_code,
            'activated_by' => Auth::user()->id
        ]);

        return $this;
    }

    /**
     * Record access
     */
    public function recordAccess($ip = null, $userAgent = null)
    {
        $this->increment('access_count');

        $this->update([
            'last_accessed_at' => now(),
            'last_access_ip' => $ip ?? request()->ip(),
            'last_user_agent' => $userAgent ?? request()->userAgent()
        ]);

        return $this;
    }
}
