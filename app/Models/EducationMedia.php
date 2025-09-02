<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationMedia extends Model
{
    protected $fillable = [
        'content_id',
        'media_type',
        'path',
        'poster_path',
        'external_url',
        'mime',
        'duration',
        'alt',
        'caption',
        'sort_order'
    ];

    protected $appends = ['url', 'poster_url', 'is_image', 'is_video', 'is_embed', 'embed_src'];

    public function content()
    {
        return $this->belongsTo(EducationContent::class, 'content_id');
    }

    public function getUrlAttribute(): string
    {
        if ($this->media_type === 'embed') return $this->external_url ?? '';
        return $this->path ? asset('storage/' . $this->path) : '';
    }

    public function getPosterUrlAttribute(): string
    {
        if ($this->poster_path) return asset('storage/' . $this->poster_path);
        if ($this->media_type === 'embed' && $id = $this->parseYoutubeId($this->external_url)) {
            return "https://i.ytimg.com/vi/$id/hqdefault.jpg";
        }
        return '';
    }

    public function getIsImageAttribute(): bool
    {
        return $this->media_type === 'image';
    }
    public function getIsVideoAttribute(): bool
    {
        return $this->media_type === 'video';
    }
    public function getIsEmbedAttribute(): bool
    {
        return $this->media_type === 'embed';
    }

    public function getEmbedSrcAttribute(): string
    {
        if ($id = $this->parseYoutubeId($this->external_url)) return "https://www.youtube.com/embed/$id";
        if ($id = $this->parseVimeoId($this->external_url))   return "https://player.vimeo.com/video/$id";
        return $this->external_url ?? '';
    }

    private function parseYoutubeId(?string $url): ?string
    {
        if (!$url) return null;
        $p = '/(?:youtu\.be\/|youtube\.com\/(?:embed\/|shorts\/|watch\?v=|v\/))([\w\-]{6,})/i';
        return preg_match($p, $url, $m) ? $m[1] : null;
    }
    private function parseVimeoId(?string $url): ?string
    {
        if (!$url) return null;
        return preg_match('/vimeo\.com\/(?:video\/)?(\d+)/i', $url, $m) ? $m[1] : null;
    }
}
