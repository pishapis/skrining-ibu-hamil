<?php

namespace App\Http\Controllers\Master;


use App\Http\Controllers\Controller;
use App\Models\EducationContent;
use App\Models\EducationMedia;
use App\Models\EducationTag;
use App\Models\EducationRule;
use App\Models\DataDiri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
            'videos.*'     => 'nullable|mimetypes:video/mp4,video/webm,video/quicktime|max:51200',
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

        // media
        $this->storeMedia($request, $content);

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

        return redirect()->route('pages.edukasi.show', $content->slug)->with('ok', 'Konten disimpan.');
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
            $pid = $user?->puskesmas_id ?? optional(\App\Models\DataDiri::where('user_id', $user?->id)->first())->puskesmas_id;
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
            'videos.*'     => 'nullable|mimetypes:video/mp4,video/webm,video/quicktime|max:51200',
            'video_urls'   => 'nullable|string',

            // reorder & hapus
            'existing_order' => 'nullable|string', // "id1,id2,id3"
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

        return redirect()->route('pages.edukasi.show', $content->slug)->with('ok', 'Konten diperbarui.');
    }

    /** Hapus konten */
    public function destroy(string $slug)
    {
        $this->authorizeManage();
        $content = EducationContent::where('slug', $slug)->firstOrFail();
        $content->delete();
        return redirect()->route('pages.edukasi.index')->with('ok', 'Konten dihapus.');
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

    private function storeMedia(Request $request, EducationContent $content, bool $append = false): void
    {
        $sort = $append ? (int)($content->media()->max('sort_order') ?? -1) + 1 : 0;

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
                ]);
                if (!$content->cover_path) $content->update(['cover_path' => $m->path]);
            }
        }

        // videos
        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $vid) {
                if (!$vid) continue;
                $vpath = $vid->store('edu', 'public');
                EducationMedia::create([
                    'content_id' => $content->id,
                    'media_type' => 'video',
                    'path'       => $vpath,
                    'alt'        => $content->title,
                    'mime'       => $vid->getMimeType(),
                    'sort_order' => $sort++,
                ]);
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
            ]);
        }
    }
}
