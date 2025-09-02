<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, BelongsToMany};

class EducationContent extends Model
{
    protected $fillable = [
        'author_id',
        'title',
        'slug',
        'summary',
        'body',
        'cover_path',
        'visibility',
        'puskesmas_id',
        'status',
        'published_at',
        'reading_time',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
    public function media(): HasMany
    {
        return $this->hasMany(EducationMedia::class, 'content_id')->orderBy('sort_order');
    }
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(EducationTag::class, 'education_content_tag', 'content_id', 'tag_id');
    }
    public function rules(): HasMany
    {
        return $this->hasMany(EducationRule::class, 'content_id');
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published')->whereNotNull('published_at');
    }
}
