<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\EducationContent;
use App\Models\EducationMedia;
use App\Models\EducationTag;
use App\Models\EducationRule;
use App\Models\DataDiri;
use App\Jobs\CompressVideoJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EducationContentController extends Controller
{
    /** List konten (scoped by role/visibility) */
    public function index(Request $request)
    {
        $user = Auth::user();
        $roleId = (int)($user->role_id ?? 1);

        $q = EducationContent::with(['tags', 'media'])
            ->when($request->filled('search'), function ($qq) use ($request) {
                $s = '%' . $request->search . '%';
                $qq->where(function ($x) use ($s) {
                    $x->where('title', 'like', $s)->orWhere('summary', 'like', $s)->orWhere('body', 'like', $s);
                });
            })
            ->published()
            ->orderByDesc('published_at');

        // scope facility
        if ($roleId === 3) {
            // superadmin: lihat semua
        } elseif ($roleId === 2) {
            $pid = $user->puskesmas_id ?? null;
            $q->where(function ($w) use ($pid) {
                $w->where('visibility', 'public')
                    ->orWhere(function ($z) use ($pid) {
                        $z->where('visibility', 'facility')->where('puskesmas_id', $pid);
                    });
            });
        } else { // user
            $dd  = DataDiri::where('user_id', $user->id)->first();
            $pid = $dd->puskesmas_id ?? null;
            $q->where(function ($w) use ($pid) {
                $w->where('visibility', 'public')
                    ->orWhere(function ($z) use ($pid) {
                        $z->where('visibility', 'facility')->where('puskesmas_id', $pid);
                    });
            });
        }

        $contents = $q->paginate(12)->withQueryString();
        $contents->getCollection()->transform(function ($content) {
            $coverUrl = null;
            $embed = $content->media->firstWhere('media_type', 'embed');

            if ($embed) {
                $youtubeId = $this->youtubeId($embed->external_url);
                if ($youtubeId) {
                    $coverUrl = 'https://i.ytimg.com/vi/' . $youtubeId . '/hqdefault.jpg';
                }
            }

            $content->coverUrl = $coverUrl;
            return $content;
        });

        // dd($contents);

        return view('pages.edukasi.index', compact('contents'));
    }

    /** Form create (admin/superadmin) */
    public function create()
    {
        $this->authorizeManage();
        return view('pages.edukasi.create');
    }

    /** Simpan konten + media + rules + tags */
    public function store(Request $request)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'title'        => 'required|string|max:200',
            'summary'      => 'nullable|string|max:500',
            'body'         => 'nullable|string',
            'visibility'   => 'required|in:public,facility,private',
            'puskesmas_id' => 'nullable|integer',
            'status'       => 'required|in:draft,published',
            'published_at' => 'nullable|date',
            'tags'         => 'nullable|string',

            // media
            'images.*'     => 'nullable|image|max:4096',
            'videos.*'     => 'nullable|mimetypes:video/mp4,video/webm,video/quicktime|max:716800',
            'video_urls'   => 'nullable|string',

            // rules
            'rules'                       => 'nullable|array',
            'rules.*.screening_type'      => 'required_with:rules|in:epds,dass',
            'rules.*.dimension'           => 'required_with:rules|in:epds_total,dass_dep,dass_anx,dass_str',
            'rules.*.min_score'           => 'nullable|integer',
            'rules.*.max_score'           => 'nullable|integer',
            'rules.*.trimester'           => 'nullable|in:trimester_1,trimester_2,trimester_3,pasca_hamil',
        ]);

        $user = Auth::user();
        $slug = Str::slug($data['title']) . '-' . Str::random(5);

        $content = EducationContent::create([
            'author_id'    => $user->id,
            'title'        => $data['title'],
            'slug'         => $slug,
            'summary'      => $data['summary'] ?? null,
            'body'         => $data['body'] ?? null,
            'visibility'   => $data['visibility'],
            'puskesmas_id' => $data['visibility'] === 'facility' ? ($data['puskesmas_id'] ?? $user->puskesmas_id) : null,
            'status'       => $data['status'],
            'published_at' => $data['status'] === 'published' ? ($data['published_at'] ?? now()) : null,
            'reading_time' => $this->estimateReadingTime($data['body'] ?? ''),
        ]);

        // tags
        if (!empty($data['tags'])) {
            $tagIds = collect(explode(',', $data['tags']))
                ->map(fn($t) => trim($t))->filter()->unique()
                ->map(function ($name) {
                    $slug = Str::slug($name);
                    return EducationTag::firstOrCreate(['slug' => $slug], ['name' => $name]);
                })->pluck('id')->all();
            $content->tags()->sync($tagIds);
        }

        // media - collect video media IDs
        $videoMediaIds = $this->storeMedia($request, $content);

        // rules
        if (!empty($data['rules'])) {
            foreach ($data['rules'] as $r) {
                EducationRule::create([
                    'content_id'     => $content->id,
                    'screening_type' => $r['screening_type'],
                    'dimension'      => $r['dimension'],
                    'min_score'      => $r['min_score'] ?? null,
                    'max_score'      => $r['max_score'] ?? null,
                    'trimester'      => $r['trimester'] ?? null,
                ]);
            }
        }

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Konten disimpan. Video sedang diproses.',
                'media_ids' => $videoMediaIds,
                'redirect' => route('edukasi.show', $content->slug)
            ]);
        }

        return redirect()->route('edukasi.show', $content->slug)
            ->with('ok', 'Konten disimpan. Video sedang diproses.');
    }

    /** Get video processing status */
    public function videoStatus($mediaId)
    {
        $media = EducationMedia::find($mediaId);

        if (!$media) {
            return response()->json(['error' => 'Media not found'], 404);
        }

        return response()->json([
            'status' => $media->processing_status,
            'progress' => $media->processing_progress,
            'error' => $media->processing_error,
            'thumbnail_url' => $media->thumbnail_url
        ]);
    }

    /** Tampil detail */
    public function show(string $slug)
    {
        $content = EducationContent::with(['tags', 'media', 'rules', 'author'])->where('slug', $slug)->firstOrFail();

        // auth by visibility
        $user = Auth::user();
        $roleId = (int)($user?->role_id ?? 1);
        if ($content->visibility === 'private' && $roleId !== 3 && $user?->id !== $content->author_id) abort(403);
        if ($content->visibility === 'facility') {
            $pid = $user?->puskesmas_id ?? optional(DataDiri::where('user_id', $user?->id)->first())->puskesmas_id;
            if (!$pid || $pid != $content->puskesmas_id) abort(403);
        }

        return view('pages.edukasi.show', compact('content'));
    }

    /** Form edit */
    public function edit(string $slug)
    {
        $this->authorizeManage();
        $content = EducationContent::with(['tags', 'media', 'rules'])->where('slug', $slug)->firstOrFail();
        $tags = $content->tags->pluck('name')->implode(', ');
        return view('pages.edukasi.edit', compact('content', 'tags'));
    }

    /** Update konten + media */
    public function update(Request $request, string $slug)
    {
        $this->authorizeManage();

        $content = EducationContent::where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'title'        => 'required|string|max:200',
            'summary'      => 'nullable|string|max:500',
            'body'         => 'nullable|string',
            'visibility'   => 'required|in:public,facility,private',
            'puskesmas_id' => 'nullable|integer',
            'status'       => 'required|in:draft,published',
            'published_at' => 'nullable|date',
            'tags'         => 'nullable|string',

            // media baru
            'images.*'     => 'nullable|image|max:4096',
            'videos.*'     => 'nullable|mimetypes:video/mp4,video/webm,video/quicktime|max:716800',
            'video_urls'   => 'nullable|string',

            // reorder & hapus
            'existing_order' => 'nullable|string',
            'remove_media'   => 'nullable|array',
            'remove_media.*' => 'integer',

            // rules
            'rules'                       => 'nullable|array',
            'rules.*.screening_type'      => 'required_with:rules|in:epds,dass',
            'rules.*.dimension'           => 'required_with:rules|in:epds_total,dass_dep,dass_anx,dass_str',
            'rules.*.min_score'           => 'nullable|integer',
            'rules.*.max_score'           => 'nullable|integer',
            'rules.*.trimester'           => 'nullable|in:trimester_1,trimester_2,trimester_3,pasca_hamil',
        ]);

        $slugChanged = Str::slug($data['title']) !== Str::before($content->slug, '-');

        $content->update([
            'title'        => $data['title'],
            'slug'         => $slugChanged ? (Str::slug($data['title']) . '-' . Str::random(5)) : $content->slug,
            'summary'      => $data['summary'] ?? null,
            'body'         => $data['body'] ?? null,
            'visibility'   => $data['visibility'],
            'puskesmas_id' => $data['visibility'] === 'facility' ? ($data['puskesmas_id'] ?? $content->puskesmas_id) : null,
            'status'       => $data['status'],
            'published_at' => $data['status'] === 'published' ? ($data['published_at'] ?? now()) : null,
            'reading_time' => $this->estimateReadingTime($data['body'] ?? ''),
        ]);

        // tags
        $content->tags()->detach();
        if (!empty($data['tags'])) {
            $tagIds = collect(explode(',', $data['tags']))
                ->map(fn($t) => trim($t))->filter()->unique()
                ->map(function ($name) {
                    $slug = Str::slug($name);
                    return EducationTag::firstOrCreate(['slug' => $slug], ['name' => $name]);
                })->pluck('id')->all();
            $content->tags()->sync($tagIds);
        }

        // hapus media
        if (!empty($data['remove_media'])) {
            EducationMedia::whereIn('id', $data['remove_media'])->where('content_id', $content->id)->delete();
        }

        // reorder existing
        if (!empty($data['existing_order'])) {
            $ids = collect(explode(',', $data['existing_order']))->map('intval')->values();
            foreach ($ids as $i => $mid) {
                EducationMedia::where('id', $mid)->where('content_id', $content->id)->update(['sort_order' => $i]);
            }
        }

        // tambahkan media baru
        $this->storeMedia($request, $content, true);

        // rules (replace)
        $content->rules()->delete();
        if (!empty($data['rules'])) {
            foreach ($data['rules'] as $r) {
                EducationRule::create([
                    'content_id'     => $content->id,
                    'screening_type' => $r['screening_type'],
                    'dimension'      => $r['dimension'],
                    'min_score'      => $r['min_score'] ?? null,
                    'max_score'      => $r['max_score'] ?? null,
                    'trimester'      => $r['trimester'] ?? null,
                ]);
            }
        }

        return redirect()->route('edukasi.show', $content->slug)->with('ok', 'Konten diperbarui.');
    }

    /** Hapus konten */
    public function destroy(string $slug)
    {
        $this->authorizeManage();
        $content = EducationContent::where('slug', $slug)->firstOrFail();
        $content->delete();
        return redirect()->route('edukasi.index')->with('ok', 'Konten dihapus.');
    }

    // ===== Helpers =====

    private function authorizeManage(): void
    {
        $rid = (int)(Auth::user()->role_id ?? 1);
        abort_unless(in_array($rid, [2, 3]), 403);
    }

    private function estimateReadingTime(string $text): int
    {
        $words = str_word_count(strip_tags($text));
        return max(1, (int)ceil($words / 200));
    }

    private function storeMedia(Request $request, EducationContent $content, bool $append = false): array
    {
        $sort = $append ? (int)($content->media()->max('sort_order') ?? -1) + 1 : 0;
        $videoMediaIds = [];

        // Ukuran minimum untuk kompresi (30MB dalam bytes)
        $minCompressionSize = 30 * 1024 * 1024; // 30MB

        // images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                if (!$img) continue;
                $path = $img->store('edu', 'public');
                $m = EducationMedia::create([
                    'content_id' => $content->id,
                    'media_type' => 'image',
                    'path'       => $path,
                    'alt'        => $content->title,
                    'mime'       => $img->getMimeType(),
                    'sort_order' => $sort++,
                    'processing_status' => 'completed',
                    'processing_progress' => 100,
                ]);
                if (!$content->cover_path) $content->update(['cover_path' => $m->path]);
            }
        }

        // videos - CHECK SIZE BEFORE COMPRESSION
        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $vid) {
                if (!$vid) continue;

                $fileSize = $vid->getSize(); // Get file size in bytes
                $vpath = $vid->store('edu', 'public');

                // Create media record
                $media = EducationMedia::create([
                    'content_id' => $content->id,
                    'media_type' => 'video',
                    'path'       => $vpath,
                    'alt'        => $content->title,
                    'mime'       => $vid->getMimeType(),
                    'sort_order' => $sort++,
                    'processing_status' => $fileSize >= $minCompressionSize ? 'pending' : 'completed',
                    'processing_progress' => $fileSize >= $minCompressionSize ? 0 : 100,
                ]);

                $videoMediaIds[] = $media->id;

                // Only dispatch compression job if file is >= 30MB
                if ($fileSize >= $minCompressionSize) {
                    CompressVideoJob::dispatch($media->id, $vpath);
                    Log::info("Video queued for compression", [
                        'media_id' => $media->id,
                        'size' => round($fileSize / (1024 * 1024), 2) . 'MB'
                    ]);
                } else {
                    // Generate thumbnail only for small videos
                    $this->generateThumbnailForSmallVideo($media, $vpath);
                    Log::info("Video skipped compression (< 30MB)", [
                        'media_id' => $media->id,
                        'size' => round($fileSize / (1024 * 1024), 2) . 'MB'
                    ]);
                }
            }
        }

        // embeds
        $urls = collect(preg_split('/\r\n|\r|\n/', (string)$request->input('video_urls')))
            ->map(fn($s) => trim($s))->filter();
        foreach ($urls as $u) {
            EducationMedia::create([
                'content_id'  => $content->id,
                'media_type'  => 'embed',
                'path'        => null,
                'external_url' => $u,
                'alt'         => $content->title,
                'sort_order'  => $sort++,
                'processing_status' => 'completed',
                'processing_progress' => 100,
            ]);
        }

        return $videoMediaIds;
    }

    private function generateThumbnailForSmallVideo(EducationMedia $media, string $videoPath): void
    {
        try {
            $fullPath = Storage::disk('public')->path($videoPath);

            if (!file_exists($fullPath)) {
                Log::warning("Video file not found for thumbnail generation: {$fullPath}");
                return;
            }

            $isProduction = config('app.env') === 'production';

            $ffmpegBinary = $isProduction
                ? config('services.ffmpeg.binaries_prod', env('FFMPEG_BINARIES_PROD', '/usr/bin/ffmpeg'))
                : config('services.ffmpeg.binaries_dev', env('FFMPEG_BINARIES_DEV', 'C:/ffmpeg/bin/ffmpeg.exe'));

            $ffprobeBinary = $isProduction
                ? config('services.ffmpeg.probe_prod', env('FFPROBE_BINARIES_PROD', '/usr/bin/ffprobe'))
                : config('services.ffmpeg.probe_dev', env('FFPROBE_BINARIES_DEV', 'C:/ffmpeg/bin/ffprobe.exe'));

            if (!file_exists($ffmpegBinary) || !file_exists($ffprobeBinary)) {
                Log::warning("FFmpeg not found, skipping thumbnail generation");
                return;
            }

            $ffmpeg = \FFMpeg\FFMpeg::create([
                'ffmpeg.binaries'  => $ffmpegBinary,
                'ffprobe.binaries' => $ffprobeBinary,
                'timeout'          => 300,
            ]);

            $video = $ffmpeg->open($fullPath);

            $fileName = pathinfo($fullPath, PATHINFO_FILENAME);
            $thumbnailName = 'thumbnails/' . $fileName . '_thumb.jpg';
            $thumbnailFullPath = Storage::disk('public')->path($thumbnailName);

            $thumbnailDir = dirname($thumbnailFullPath);
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            $frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(2));
            $frame->save($thumbnailFullPath);

            $media->update(['thumbnail_path' => $thumbnailName]);

            Log::info("Thumbnail generated for small video", [
                'media_id' => $media->id,
                'thumbnail' => $thumbnailName
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to generate thumbnail for small video", [
                'media_id' => $media->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function youtubeId(?string $url): ?string
    {
        if (!$url) return null;
        $u = parse_url($url);
        if (!$u || empty($u['host'])) return null;

        if (str_contains($u['host'], 'youtu.be')) {
            return ltrim($u['path'] ?? '', '/');
        }

        if (str_contains($u['host'], 'youtube.com')) {
            if (!empty($u['query'])) {
                parse_str($u['query'], $q);
                return $q['v'] ?? null;
            }
            if (!empty($u['path']) && str_contains($u['path'], '/embed/')) {
                return trim(str_replace('/embed/', '', $u['path']), '/');
            }
        }

        return null;
    }
}
