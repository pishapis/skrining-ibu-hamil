<?php

namespace App\Http\Controllers;

use App\Models\DataDiri;
use App\Models\UsiaHamil;
use App\Models\HasilEpds;
use App\Models\HasilDass;
use App\Support\Kehamilan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\EducationContent;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // === ROLE dari role_id ===
        $role = $this->mapRoleFromUser($user); // <-- perbaiki: kirim $user (bukan $user->role)
        $scope = $this->resolveScope($user, $role); // ['type' => self|facility|all, 'puskesmas_id' => ?]

        // === DATA DIRI & USIA HAMIL TERBARU (hanya untuk User pribadi) ===
        $dataDiri = ($scope['type'] === 'self')
            ? DataDiri::where('user_id', $user->id)->first()
            : null;

        $usia = null;
        $hpht = null;
        $hpl = null;

        if ($dataDiri) {
            $riwayat = UsiaHamil::where('ibu_id', $dataDiri->id)
                ->whereNotNull('hpht')->whereNotNull('hpl')
                ->latest('created_at')->first();

            if ($riwayat) {
                $hpht = Carbon::parse($riwayat->hpht);
                $hpl  = Carbon::parse($riwayat->hpl);

                $usiaMinggu = Kehamilan::hitungUsiaMinggu($riwayat->hpht);
                $usia = [
                    'hpht'        => $riwayat->hpht,
                    'hpl'         => $riwayat->hpl,
                    'usia_minggu' => $usiaMinggu,
                    'keterangan'  => Kehamilan::hitungUsiaString($riwayat->hpht),
                    'trimester'   => Kehamilan::tentukanTrimester($usiaMinggu),
                ];
            }
        }

        // === EPDS & DASS (per user pribadi) ===
        $latestEpds = null;
        $latestDass = null;
        $epdsCount = 0;
        $dassCount = 0;
        $epdsSessions = collect();
        $dassSessions = collect();

        if ($scope['type'] === 'self' && $dataDiri) {
            // Subquery: record terakhir per session_token (USER INI)
            $subEpds = HasilEpds::select('session_token', DB::raw('MAX(screening_date) AS max_date'))
                ->where('ibu_id', $dataDiri->id)
                ->where('status', 'submitted')
                ->whereNotNull('session_token')
                ->groupBy('session_token');

            $epdsSessions = HasilEpds::from('hasil_epds AS he')
                ->joinSub($subEpds, 't', function ($j) {
                    $j->on('he.session_token', '=', 't.session_token')
                        ->on('he.screening_date', '=', 't.max_date');
                })
                ->where('he.ibu_id', $dataDiri->id)
                ->orderByDesc('he.screening_date')
                ->get();

            $subDass = HasilDass::select('session_token', DB::raw('MAX(screening_date) AS max_date'))
                ->where('ibu_id', $dataDiri->id)
                ->where('status', 'submitted')
                ->whereNotNull('session_token')
                ->groupBy('session_token');

            $dassSessions = HasilDass::from('hasil_dass AS hd')
                ->joinSub($subDass, 't', function ($j) {
                    $j->on('hd.session_token', '=', 't.session_token')
                        ->on('hd.screening_date', '=', 't.max_date');
                })
                ->where('hd.ibu_id', $dataDiri->id)
                ->orderByDesc('hd.screening_date')
                ->get();

            $latestEpds = $epdsSessions->first();
            $latestDass = $dassSessions->first();
            $epdsCount  = $epdsSessions->count();
            $dassCount  = $dassSessions->count();
        }

        // === TRIMESTER YANG SUDAH SUBMIT (untuk user pribadi) ===
        $submittedByTrimester = [];
        if ($scope['type'] === 'self' && $dataDiri) {
            $submittedByTrimester = HasilEpds::where('ibu_id', $dataDiri->id)
                ->where('status', 'submitted')
                ->whereNotNull('trimester')
                ->pluck('trimester')->unique()->values()->all();
        }

        // === JADWAL SKRINING BERIKUTNYA (user pribadi) ===
        $nextSchedule = null;
        if ($scope['type'] === 'self') {
            $nextScheduleRaw = $this->computeNextScreeningSchedule($hpht, $hpl, $submittedByTrimester);
            $nextSchedule = $nextScheduleRaw ? [
                'phase'      => $nextScheduleRaw['phase'],
                'code'       => $nextScheduleRaw['code'],
                'date'       => $nextScheduleRaw['date']?->toDateString(),
                'date_human' => $nextScheduleRaw['date_human'],
                'is_now'     => $nextScheduleRaw['is_now'],
            ] : null;
        }

        // === KPI 30 HARI (scoped: ALL untuk superadmin, FACILITY untuk admin, GLOBAL user opsional) ===
        $since = Carbon::now()->subDays(30);
        $kpi = $this->buildScopedKpi($since, $scope);

        // === ALERTS (untuk user pribadi saja) ===
        $alerts = [];
        if ($scope['type'] === 'self') {
            $dassFlags = ['dep' => 15, 'anx' => 12, 'stress' => 20];
            if ($latestEpds && (int)($latestEpds->total_score ?? 0) >= 13) {
                $alerts[] = ['type' => 'warning', 'text' => 'Hasil EPDS terakhir mengindikasikan depresi. Pertimbangkan konsultasi.'];
            }
            if ($latestDass) {
                if ((int)($latestDass->total_anxiety ?? 0)   >= $dassFlags['anx'])    $alerts[] = ['type' => 'info', 'text' => 'Kecemasan tinggi. Konsultasi Bidan & Psikolog disarankan.'];
                if ((int)($latestDass->total_depression ?? 0) >= $dassFlags['dep'])    $alerts[] = ['type' => 'info', 'text' => 'Depresi tinggi. Konsultasi Psikolog disarankan.'];
                if ((int)($latestDass->total_stress ?? 0)    >= $dassFlags['stress']) $alerts[] = ['type' => 'info', 'text' => 'Stres tinggi. Konsultasi Dokter & Psikolog disarankan.'];
            }
        }

        // === TREN (untuk user pribadi; admin/superadmin biasanya lihat agregat) ===
        $maxPoints = 30;
        $epdsTrend = ($scope['type'] === 'self') ? $this->buildEpdsTrend($epdsSessions, $maxPoints) : [];
        $dassTrend = ($scope['type'] === 'self') ? $this->buildDassTrend($dassSessions, $maxPoints) : [];

        // (Opsional) Statistik fasilitas: distribusi trimester, total ibu, dsb (untuk admin/superadmin)
        $facilityStats = ($scope['type'] !== 'self') ? $this->buildFacilityStats($scope) : null;
        $latestScreenings = ($scope['type'] !== 'self') ? $this->fetchLatestScreenings($scope, 12) : [];

        $eduRecs = ($role === 'user')
            ? $this->fetchRecommendedEducation($user, $usia, $latestEpds, $latestDass, 8)
            : [];

        return view('dashboard', compact(
            'role',
            'usia',
            'latestEpds',
            'latestDass',
            'epdsCount',
            'dassCount',
            'kpi',
            'alerts',
            'nextSchedule',
            'epdsTrend',
            'dassTrend',
            'facilityStats',
            'latestScreenings',
            'eduRecs'
        ));
    }

    // ===================== Helpers: Role & Scope =====================

    private function mapRoleFromUser($user): string
    {
        $rid = (int)($user->role_id ?? 1);
        if ($rid === 3) return 'superadmin';
        if ($rid === 2) {
            // Bedakan klinisi vs fasilitas (opsional; boleh kamu sederhanakan jadi 'admin' saja)
            $title = strtolower(trim((string)($user->role_name ?? $user->job_title ?? $user->role ?? '')));
            $hasFacility = !empty($user->puskesmas_id) || preg_match('/puskesmas|klinik|rs|fasyankes/', $title);
            $isClinician = preg_match('/bidan|dokter|psikolog/', $title);
            if ($hasFacility && !$isClinician) return 'admin_facility';
            return 'admin_clinician';
        }
        return 'user';
    }

    /**
     * Resolve scope:
     * - self: hanya data pribadi
     * - facility: semua ibu di puskesmas_id user
     * - all: seluruh data
     */
    private function resolveScope($user, string $role): array
    {
        if ($role === 'superadmin') return ['type' => 'all'];
        if ($role === 'admin_facility' || $role === 'admin_clinician') {
            $pid = $user->puskesmas_id ?? null;
            return ['type' => 'facility', 'puskesmas_id' => $pid];
        }
        return ['type' => 'self'];
    }

    // ===================== Helpers: KPI scoped =====================

    private function buildScopedKpi(Carbon $since, array $scope): array
    {
        // EPDS
        $epdsQ = HasilEpds::query()
            ->where('status', 'submitted')
            ->where('screening_date', '>=', $since);

        // DASS
        $dassQ = HasilDass::query()
            ->where('status', 'submitted')
            ->where('screening_date', '>=', $since);

        if ($scope['type'] === 'facility' && !empty($scope['puskesmas_id'])) {
            // Gabungkan dengan data_diri untuk filter puskesmas_id
            $epdsQ->join('data_diri as dd', 'dd.id', '=', 'hasil_epds.ibu_id')
                ->where('dd.puskesmas_id', $scope['puskesmas_id']);
            $dassQ->join('data_diri as dd', 'dd.id', '=', 'hasil_dass.ibu_id')
                ->where('dd.puskesmas_id', $scope['puskesmas_id']);
        }
        // superadmin => tanpa filter; user 'self' bisa dibiarkan global atau kamu set ke 0, sesuai kebutuhan UI

        return [
            'epds_30d' => $epdsQ->distinct('session_token')->count('session_token'),
            'dass_30d' => $dassQ->distinct('session_token')->count('session_token'),
        ];
    }

    // ===================== Helpers: Facility stats (opsional) =====================

    /**
     * Statistik ringkas fasilitas/all:
     * - total_ibu
     * - distribusi trimester (berdasarkan riwayat terbaru yang punya HPHT/HPL)
     */
    private function buildFacilityStats(array $scope): array
    {
        // Ambil riwayat HPHT/HPL TERBARU per ibu (di-scope fasilitas bila perlu)
        $latestUhSub = UsiaHamil::query()
            ->select('ibu_id', DB::raw('MAX(created_at) AS max_created'))
            ->whereNotNull('hpht')->whereNotNull('hpl')
            ->groupBy('ibu_id');

        $uh = UsiaHamil::from('usia_hamil as uh')
            ->joinSub($latestUhSub, 't', function ($j) {
                $j->on('uh.ibu_id', '=', 't.ibu_id')->on('uh.created_at', '=', 't.max_created');
            })
            ->join('data_diri as dd', 'dd.id', '=', 'uh.ibu_id');

        if ($scope['type'] === 'facility' && !empty($scope['puskesmas_id'])) {
            $uh->where('dd.puskesmas_id', $scope['puskesmas_id']);
        }

        $rows = $uh->select('uh.hpht', 'uh.hpl')->get();

        $counts = ['trimester_1' => 0, 'trimester_2' => 0, 'trimester_3' => 0, 'pasca_hamil' => 0];
        foreach ($rows as $r) {
            $usiaM = Kehamilan::hitungUsiaMinggu($r->hpht);
            $code  = Kehamilan::tentukanTrimester($usiaM, $r->hpl);
            $counts[$code] = ($counts[$code] ?? 0) + 1;
        }

        // total ibu (berdasarkan data_diri)
        $dd = DataDiri::query();
        if ($scope['type'] === 'facility' && !empty($scope['puskesmas_id'])) {
            $dd->where('puskesmas_id', $scope['puskesmas_id']);
        }
        $totalIbu = $dd->count();

        return [
            'total_ibu' => $totalIbu,
            'trimester_counts' => $counts,
        ];
    }

    /**
     * Ambil daftar skrining terbaru (EPDS/DASS) di scope (fasilitas/all), limit N.
     * Menghasilkan array unified: type, ibu_nama(optional), date, skor ringkas.
     * **Sesuaikan kolom nama ibu di data_diri** (di sini diasumsikan `nama`).
     */
    private function fetchLatestScreenings(array $scope, int $limit = 12): array
    {
        // EPDS: latest per session_token
        $subE = HasilEpds::select('session_token', DB::raw('MAX(screening_date) AS max_date'))
            ->where('status', 'submitted')->whereNotNull('session_token')->groupBy('session_token');

        $epds = HasilEpds::from('hasil_epds as he')
            ->joinSub($subE, 't', fn($j) => $j->on('he.session_token', '=', 't.session_token')->on('he.screening_date', '=', 't.max_date'))
            ->join('data_diri as dd', 'dd.id', '=', 'he.ibu_id');

        if ($scope['type'] === 'facility' && !empty($scope['puskesmas_id'])) {
            $epds->where('dd.puskesmas_id', $scope['puskesmas_id']);
        }

        $epds = $epds->orderByDesc('he.screening_date')
            ->limit($limit)->get(['he.total_score', 'he.screening_date', 'dd.nama as ibu_nama']);

        // DASS: latest per session_token
        $subD = HasilDass::select('session_token', DB::raw('MAX(screening_date) AS max_date'))
            ->where('status', 'submitted')->whereNotNull('session_token')->groupBy('session_token');

        $dass = HasilDass::from('hasil_dass as hd')
            ->joinSub($subD, 't', fn($j) => $j->on('hd.session_token', '=', 't.session_token')->on('hd.screening_date', '=', 't.max_date'))
            ->join('data_diri as dd', 'dd.id', '=', 'hd.ibu_id');

        if ($scope['type'] === 'facility' && !empty($scope['puskesmas_id'])) {
            $dass->where('dd.puskesmas_id', $scope['puskesmas_id']);
        }

        $dass = $dass->orderByDesc('hd.screening_date')
            ->limit($limit)->get(['hd.total_depression', 'hd.total_anxiety', 'hd.total_stress', 'hd.screening_date', 'dd.nama as ibu_nama']);

        // Satukan & urutkan
        $items = [];
        foreach ($epds as $r) {
            $dt = Carbon::parse($r->screening_date);
            $items[] = [
                'type' => 'EPDS',
                'ibu'  => $r->ibu_nama,
                'date' => $dt->toDateString(),
                'label' => $dt->translatedFormat('d M Y'),
                'scores' => ['total' => (int)$r->total_score],
            ];
        }
        foreach ($dass as $r) {
            $dt = Carbon::parse($r->screening_date);
            $items[] = [
                'type' => 'DASS-21',
                'ibu'  => $r->ibu_nama,
                'date' => $dt->toDateString(),
                'label' => $dt->translatedFormat('d M Y'),
                'scores' => [
                    'dep' => (int)$r->total_depression,
                    'anx' => (int)$r->total_anxiety,
                    'stress' => (int)$r->total_stress,
                ],
            ];
        }

        usort($items, fn($a, $b) => strcmp($b['date'], $a['date']));
        return array_slice($items, 0, $limit);
    }

    // ===================== Helpers: Tren (user pribadi) =====================

    private function buildEpdsTrend(Collection $sessions, int $limit): array
    {
        return $sessions->sortBy('screening_date')
            ->take($limit)->values()
            ->map(function ($r) {
                $dt = Carbon::parse($r->screening_date);
                return [
                    'date'  => $dt->toDateString(),
                    'label' => $dt->translatedFormat('d M Y'),
                    'total' => (int)($r->total_score ?? 0),
                ];
            })->all();
    }

    private function buildDassTrend(Collection $sessions, int $limit): array
    {
        return $sessions->sortBy('screening_date')
            ->take($limit)->values()
            ->map(function ($r) {
                $dt = Carbon::parse($r->screening_date);
                return [
                    'date'   => $dt->toDateString(),
                    'label'  => $dt->translatedFormat('d M Y'),
                    'dep'    => (int)($r->total_depression ?? 0),
                    'anx'    => (int)($r->total_anxiety ?? 0),
                    'stress' => (int)($r->total_stress ?? 0),
                ];
            })->all();
    }

    private function computeNextScreeningSchedule(?Carbon $hpht, ?Carbon $hpl, array $submittedByTrimester): ?array
    {
        if (!$hpht) return null;
        $now = Carbon::now();

        $t1 = $hpht->copy();
        $t2 = $hpht->copy()->addWeeks(14);
        $t3 = $hpht->copy()->addWeeks(28);
        $pp = $hpl?->copy() ?? $hpht->copy()->addWeeks(40);

        $phases = [
            ['code' => 'trimester_1', 'name' => 'Trimester I',   'start' => $t1],
            ['code' => 'trimester_2', 'name' => 'Trimester II',  'start' => $t2],
            ['code' => 'trimester_3', 'name' => 'Trimester III', 'start' => $t3],
            ['code' => 'pasca_hamil', 'name' => 'Pasca Hamil',   'start' => $pp],
        ];

        $currentCode = null;
        try {
            $usiaMingguNow = Kehamilan::hitungUsiaMinggu($hpht->toDateString());
            $currentCode = Kehamilan::tentukanTrimester($usiaMingguNow, $hpl?->toDateString());
        } catch (\Throwable $e) {
            $currentCode = null;
        }

        $current = $currentCode ? collect($phases)->firstWhere('code', $currentCode) : null;

        if ($current && !in_array($current['code'], $submittedByTrimester, true)) {
            return [
                'phase'      => $current['name'],
                'code'       => $current['code'],
                'date'       => $now,
                'date_human' => $now->translatedFormat('d M Y'),
                'is_now'     => true,
            ];
        }

        $ordered = collect($phases)->sortBy(fn($p) => $p['start']->timestamp)->values();
        $startIndex = 0;
        if ($current) {
            $startIndex = max(0, $ordered->search(fn($p) => $p['code'] === $current['code']) + 1);
        }

        for ($i = $startIndex; $i < $ordered->count(); $i++) {
            $p = $ordered[$i];
            if (!in_array($p['code'], $submittedByTrimester, true)) {
                return [
                    'phase'      => $p['name'],
                    'code'       => $p['code'],
                    'date'       => $p['start'],
                    'date_human' => $p['start']->translatedFormat('d M Y'),
                    'is_now'     => $p['start']->isSameDay($now) || $p['start']->lessThan($now),
                ];
            }
        }

        return null;
    }

    private function fetchRecommendedEducation($user, ?array $usia, $latestEpds, $latestDass, int $limit = 8): array
    {
        $scores = [
            'epds_total' => (int)($latestEpds->total_score ?? 0),
            'dass_dep'   => (int)($latestDass->total_depression ?? 0),
            'dass_anx'   => (int)($latestDass->total_anxiety ?? 0),
            'dass_str'   => (int)($latestDass->total_stress ?? 0),
        ];
        $trimester = $usia['trimester'] ?? null;
        $pid = $user->puskesmas_id ?? null;

        $q = EducationContent::query()
            ->with([
                'media' => fn($m) => $m->orderBy('sort_order'),
                'tags',
                'rules',
            ])
            ->where('status', 'published')
            ->where(function ($w) use ($pid) {
                $w->where('visibility', 'public')
                    ->orWhere(function ($x) use ($pid) {
                        if ($pid) {
                            $x->where('visibility', 'facility')->where('puskesmas_id', $pid);
                        }
                    });
            })
            ->where(function ($w) use ($scores, $trimester) {
                // konten tanpa rules (umum)
                $w->whereDoesntHave('rules');

                // atau konten dengan rules yang match
                $w->orWhereHas('rules', function ($r) use ($scores, $trimester) {
                    $r->where(function ($cond) use ($scores) {
                        // EPDS total
                        $cond->orWhere(function ($ep) use ($scores) {
                            $ep->where('screening_type', 'epds')
                                ->where('dimension', 'epds_total')
                                ->where(function ($range) use ($scores) {
                                    $range->whereNull('min_score')->orWhere('min_score', '<=', $scores['epds_total']);
                                })
                                ->where(function ($range) use ($scores) {
                                    $range->whereNull('max_score')->orWhere('max_score', '>=', $scores['epds_total']);
                                });
                        });
                        // DASS dep
                        $cond->orWhere(function ($d) use ($scores) {
                            $d->where('screening_type', 'dass')
                                ->where('dimension', 'dass_dep')
                                ->where(function ($range) use ($scores) {
                                    $range->whereNull('min_score')->orWhere('min_score', '<=', $scores['dass_dep']);
                                })
                                ->where(function ($range) use ($scores) {
                                    $range->whereNull('max_score')->orWhere('max_score', '>=', $scores['dass_dep']);
                                });
                        });
                        // DASS anx
                        $cond->orWhere(function ($d) use ($scores) {
                            $d->where('screening_type', 'dass')
                                ->where('dimension', 'dass_anx')
                                ->where(function ($range) use ($scores) {
                                    $range->whereNull('min_score')->orWhere('min_score', '<=', $scores['dass_anx']);
                                })
                                ->where(function ($range) use ($scores) {
                                    $range->whereNull('max_score')->orWhere('max_score', '>=', $scores['dass_anx']);
                                });
                        });
                        // DASS str
                        $cond->orWhere(function ($d) use ($scores) {
                            $d->where('screening_type', 'dass')
                                ->where('dimension', 'dass_str')
                                ->where(function ($range) use ($scores) {
                                    $range->whereNull('min_score')->orWhere('min_score', '<=', $scores['dass_str']);
                                })
                                ->where(function ($range) use ($scores) {
                                    $range->whereNull('max_score')->orWhere('max_score', '>=', $scores['dass_str']);
                                });
                        });
                    });

                    if ($trimester) {
                        $r->where(function ($t) use ($trimester) {
                            $t->whereNull('trimester')->orWhere('trimester', $trimester);
                        });
                    }
                });
            })
            ->where(function ($w) {
                $w->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('published_at')
            ->limit($limit);

        $rows = $q->get();

        return $rows->map(function ($c) {
            // cover: cover_path > first image > youtube thumb > null
            $cover = $c->cover_path
                ?: optional($c->media->firstWhere('media_type', 'image'))->path;

            $coverUrl = $cover ? Storage::disk('public')->url($cover) : null;

            if (!$coverUrl) {
                $embed = $c->media->firstWhere('media_type', 'embed');
                if ($embed && $this->youtubeId($embed->external_url)) {
                    $coverUrl = 'https://i.ytimg.com/vi/' . $this->youtubeId($embed->external_url) . '/hqdefault.jpg';
                }
            }

            $imgCount = $c->media->where('media_type', 'image')->count();
            $hasVideo = $c->media->contains(fn($m) => $m->media_type === 'embed');
            $badge = $hasVideo ? 'Video' : ($imgCount > 1 ? "{$imgCount} gambar" : ($imgCount === 1 ? 'Gambar' : null));

            return [
                'id'       => $c->id,
                'title'    => $c->title,
                'summary'  => $c->summary,
                'url'      => route('edukasi.show', $c->slug), // pastikan route resource sudah ada
                'cover'    => $coverUrl,
                'badge'    => $badge,
                'tags'     => $c->tags->pluck('name')->take(3)->values()->all(),
            ];
        })->all();
    }

    private function youtubeId(?string $url): ?string
    {
        if (!$url) return null;
        $u = parse_url($url);
        if (!$u || empty($u['host'])) return null;

        // youtu.be/<id>
        if (str_contains($u['host'], 'youtu.be')) {
            return ltrim($u['path'] ?? '', '/');
        }

        // youtube.com/watch?v=<id>
        if (str_contains($u['host'], 'youtube.com')) {
            if (!empty($u['query'])) {
                parse_str($u['query'], $q);
                return $q['v'] ?? null;
            }
            // youtube.com/embed/<id>
            if (!empty($u['path']) && str_contains($u['path'], '/embed/')) {
                return trim(str_replace('/embed/', '', $u['path']), '/');
            }
        }

        return null;
    }
}
