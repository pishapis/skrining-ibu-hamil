<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    protected static function booted()
    {
        static::creating(function ($m) {
            if (empty($m->id)) $m->id = (string) Str::uuid();
        });
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active')
            ->where(function ($qq) {
                $qq->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
