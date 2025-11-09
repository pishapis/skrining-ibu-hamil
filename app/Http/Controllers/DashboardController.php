<?php

namespace App\Http\Controllers;

use App\Models\DataDiri;
use App\Models\UsiaHamil;
use App\Models\HasilEpds;
use App\Models\HasilDass;
use App\Models\User;
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

        // === ROLE MAPPING ===
        $role = $this->mapRoleFromUser($user);
        $scope = $this->resolveScope($user, $role);

        // === PARSE FILTERS ===
        $filters = $this->parseFilters($request, $role);

        // === DATA DIRI & USIA HAMIL (untuk User ibu) ===
        $dataDiri = null;
        $usia = null;
        $hpht = null;
        $hpl = null;

        if ($user->isIbu()) {
            $dataDiri = $user->dataDiri()->where(function ($query) {
            $query->whereNotNull('faskes_rujukan_id')
                ->orWhereNotNull('puskesmas_id');
        })
        ->first();

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
        }

        // === EPDS & DASS untuk User Ibu ===
        $latestEpds = null;
        $latestDass = null;
        $epdsCount = 0;
        $dassCount = 0;
        $epdsSessions = collect();
        $dassSessions = collect();

        if ($user->isIbu() && $dataDiri) {
            // EPDS
            $epdsBaseQuery = HasilEpds::where('ibu_id', $dataDiri->id)
                ->where('status', 'submitted')
                ->whereNotNull('session_token');

            $epdsBaseQuery = $this->applyFiltersToQuery($epdsBaseQuery, $filters, 'epds');

            $subEpdsMaxDate = (clone $epdsBaseQuery)
                ->select('session_token', DB::raw('MAX(screening_date) AS max_date'))
                ->groupBy('session_token');

            $subEpdsPick = HasilEpds::from('hasil_epds as he1')
                ->joinSub($subEpdsMaxDate, 't', function ($j) {
                    $j->on('he1.session_token', '=', 't.session_token')
                        ->on('he1.screening_date', '=', 't.max_date');
                })
                ->selectRaw('he1.session_token, COALESCE(MAX(CASE WHEN he1.answers_epds_id IS NULL THEN he1.id END), MAX(he1.id)) AS pick_id')
                ->groupBy('he1.session_token');

            $epdsSessions = HasilEpds::from('hasil_epds AS he')
                ->joinSub($subEpdsPick, 'p', function ($j) {
                    $j->on('he.session_token', '=', 'p.session_token')
                        ->on('he.id', '=', 'p.pick_id');
                })
                ->where('he.ibu_id', $dataDiri->id)
                ->where(function ($q) use ($filters) {
                    $q = $this->applyFiltersToQuery($q, $filters, 'epds', 'he.');
                })
                ->orderByDesc('he.screening_date')
                ->get();

            // DASS
            $dassBaseQuery = HasilDass::where('ibu_id', $dataDiri->id)
                ->where('status', 'submitted')
                ->whereNotNull('session_token');

            $dassBaseQuery = $this->applyFiltersToQuery($dassBaseQuery, $filters, 'dass');

            $subDassMaxDate = (clone $dassBaseQuery)
                ->select('session_token', DB::raw('MAX(screening_date) AS max_date'))
                ->groupBy('session_token');

            $subDassPick = HasilDass::from('hasil_dass as hd1')
                ->joinSub($subDassMaxDate, 't', function ($j) {
                    $j->on('hd1.session_token', '=', 't.session_token')
                        ->on('hd1.screening_date', '=', 't.max_date');
                })
                ->selectRaw('hd1.session_token, MAX(hd1.id) AS pick_id')
                ->groupBy('hd1.session_token');

            $dassSessions = HasilDass::from('hasil_dass AS hd')
                ->joinSub($subDassPick, 'p', function ($j) {
                    $j->on('hd.session_token', '=', 'p.session_token')
                        ->on('hd.id', '=', 'p.pick_id');
                })
                ->where('hd.ibu_id', $dataDiri->id)
                ->where(function ($q) use ($filters) {
                    $q = $this->applyFiltersToQuery($q, $filters, 'dass', 'hd.');
                })
                ->orderByDesc('hd.screening_date')
                ->get();

            $latestEpds = $epdsSessions->first();
            $latestDass = $dassSessions->first();
            $epdsCount  = $epdsSessions->count();
            $dassCount  = $dassSessions->count();
        }

        // === TRIMESTER YANG SUDAH SUBMIT ===
        $submittedByTrimester = [];
        if ($user->isIbu() && $dataDiri) {
            $query = HasilEpds::where('ibu_id', $dataDiri->id)
                ->where('status', 'submitted')
                ->whereNotNull('trimester');

            $query = $this->applyFiltersToQuery($query, $filters, 'epds');

            $submittedByTrimester = $query->pluck('trimester')->unique()->values()->all();
        }

        // === JADWAL SKRINING BERIKUTNYA ===
        $nextSchedule = null;
        if ($user->isIbu()) {
            $nextScheduleRaw = $this->computeNextScreeningSchedule($hpht, $hpl, $submittedByTrimester);
            $nextSchedule = $nextScheduleRaw ? [
                'phase'      => $nextScheduleRaw['phase'],
                'code'       => $nextScheduleRaw['code'],
                'date'       => $nextScheduleRaw['date']?->toDateString(),
                'date_human' => $nextScheduleRaw['date_human'],
                'is_now'     => $nextScheduleRaw['is_now'],
            ] : null;
        }

        // === KPI ===
        $kpi = $this->buildScopedKpi($scope, $filters);

        // === ALERTS ===
        $alerts = [];
        if ($user->isIbu()) {
            $dassFlags = ['dep' => 15, 'anx' => 12, 'stress' => 20];
            if ($latestEpds && (int)($latestEpds->total_score ?? 0) >= 13) {
                $alerts[] = ['type' => 'warning', 'text' => 'Hasil EPDS terakhir menunjukkan gangguan suasana hati. Disarankan untuk berkonsultasi.'];
            }
            if ($latestDass) {
                if ((int)($latestDass->total_anxiety ?? 0)   >= $dassFlags['anx'])    $alerts[] = ['type' => 'info', 'text' => 'Konsultasi Bidan & Psikolog disarankan.'];
                if ((int)($latestDass->total_depression ?? 0) >= $dassFlags['dep'])    $alerts[] = ['type' => 'info', 'text' => 'Konsultasi Psikolog disarankan.'];
                if ((int)($latestDass->total_stress ?? 0)    >= $dassFlags['stress']) $alerts[] = ['type' => 'info', 'text' => 'Konsultasi Dokter & Psikolog disarankan.'];
            }
        }

        // === TREN DATA ===
        $epdsTrend = ($user->isIbu()) ? $this->buildEpdsTrend($epdsSessions, 30) : [];
        $dassTrend = ($user->isIbu()) ? $this->buildDassTrend($dassSessions, 30) : [];

        // === STATISTIK FASILITAS ===
        $facilityStats = ($scope['type'] !== 'self') ? $this->buildFacilityStats($scope, $filters) : null;
        $latestScreenings = ($scope['type'] !== 'self') ? $this->fetchLatestScreenings($scope, $filters, 12, 5) : [];

        // === REKOMENDASI EDUKASI ===
        $eduRecs = ($user->isIbu())
            ? $this->fetchRecommendedEducation($user, $usia, $latestEpds, $latestDass, 8)
            : [];

        // === FILTER OPTIONS ===
        $filterOptions = $this->buildFilterOptions($scope, $dataDiri, $role);

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
            'eduRecs',
            'filters',
            'filterOptions'
        ));
    }

    // ===================== Filter Management =====================

    private function parseFilters(Request $request, string $role): array
    {
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;

        return [
            'screening_type' => $request->get('screening_type', 'all'),
            'epds_mode' => $request->get('epds_mode', 'all'),
            'dass_mode' => $request->get('dass_mode', 'all'),
            'year' => $request->get('year', $currentYear),
            'month' => $request->get('month', 'all'),
            'trimester' => $request->get('trimester', 'all'),
            'periode' => $request->get('periode', 'all'),
            'date_range' => $request->get('date_range', '30'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'ibu_name' => $request->get('ibu_name'),
        ];
    }

    private function applyFiltersToQuery($query, array $filters, string $type = 'both', string $prefix = '')
    {
        if ($filters['screening_type'] !== 'all') {
            if ($type === 'epds' && $filters['screening_type'] === 'dass') {
                $query->whereRaw('1 = 0');
            }
            if ($type === 'dass' && $filters['screening_type'] === 'epds') {
                $query->whereRaw('1 = 0');
            }
        }

        // Filter mode EPDS
        if ($type === 'epds' && $filters['epds_mode'] !== 'all') {
            if ($filters['epds_mode'] === 'kehamilan') {
                $query->whereNotNull($prefix . 'usia_hamil_id')
                    ->whereNotNull($prefix . 'trimester');
            } elseif ($filters['epds_mode'] === 'umum') {
                $query->whereNull($prefix . 'usia_hamil_id')
                    ->where($prefix . 'mode', 'umum');
            }
        }

        // Filter mode DASS
        if ($type === 'dass' && $filters['dass_mode'] !== 'all') {
            if ($filters['dass_mode'] === 'kehamilan') {
                $query->whereNotNull($prefix . 'usia_hamil_id')
                    ->where($prefix . 'mode', 'kehamilan');
            } elseif ($filters['dass_mode'] === 'umum') {
                $query->whereNull($prefix . 'usia_hamil_id')
                    ->where($prefix . 'mode', 'umum');
            }
        }

        if (!empty($filters['year'])) {
            $query->whereYear($prefix . 'screening_date', $filters['year']);
        }

        if ($filters['month'] !== 'all') {
            $query->whereMonth($prefix . 'screening_date', $filters['month']);
        }

        if ($filters['trimester'] !== 'all') {
            if ($type === 'epds') {
                $query->where($prefix . 'trimester', $filters['trimester']);
            } elseif ($type === 'dass') {
                $query->where(function ($q) use ($filters, $prefix) {
                    $q->where($prefix . 'trimester', $filters['trimester'])
                        ->orWhere(function ($q2) use ($prefix) {
                            $q2->where($prefix . 'mode', 'umum')
                                ->whereNull($prefix . 'trimester');
                        });
                });
            }
        }

        if ($type === 'dass' && $filters['periode'] !== 'all') {
            $query->where($prefix . 'periode', $filters['periode']);
        }

        if ($filters['date_range'] !== 'all') {
            if ($filters['date_range'] === 'custom' && $filters['date_from'] && $filters['date_to']) {
                $query->whereBetween($prefix . 'screening_date', [
                    Carbon::parse($filters['date_from'])->startOfDay(),
                    Carbon::parse($filters['date_to'])->endOfDay()
                ]);
            } else {
                $days = (int)$filters['date_range'];
                $query->where($prefix . 'screening_date', '>=', Carbon::now()->subDays($days));
            }
        }

        if (!empty($filters['ibu_name'])) {
            $query->whereHas('ibu', function ($q) use ($filters) {
                $q->where('nama', 'LIKE', '%' . $filters['ibu_name'] . '%');
            });
        }

        return $query;
    }

    private function buildFilterOptions(array $scope, $dataDiri = null, string $role): array
    {
        $currentYear = Carbon::now()->year;
        $years = [];
        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear - $i;
            $years[$year] = $year;
        }

        $months = [
            'all' => 'Semua Bulan',
            '1' => 'Januari',
            '2' => 'Februari',
            '3' => 'Maret',
            '4' => 'April',
            '5' => 'Mei',
            '6' => 'Juni',
            '7' => 'Juli',
            '8' => 'Agustus',
            '9' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        ];

        $trimesters = [
            'all' => 'Semua Fase',
            'trimester_1' => 'Trimester I',
            'trimester_2' => 'Trimester II',
            'trimester_3' => 'Trimester III',
            'pasca_hamil' => 'Pasca Melahirkan'
        ];

        $periodes = ['all' => 'Semua Periode'];
        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('Y-m');
            $periodes[$key] = $date->translatedFormat('F Y');
        }

        $dateRanges = [
            '7' => '7 Hari Terakhir',
            '30' => '30 Hari Terakhir',
            '90' => '90 Hari Terakhir',
            '365' => '1 Tahun Terakhir',
            'all' => 'Semua Waktu',
            'custom' => 'Rentang Custom'
        ];

        $screeningTypes = [
            'all' => 'Semua Skrining',
            'epds' => 'EPDS',
            'dass' => 'DASS-21'
        ];

        $epdsModes = [
            'all' => 'Semua Mode',
            'kehamilan' => 'Kehamilan',
            'umum' => 'Umum'
        ];

        $dassModes = [
            'all' => 'Semua Mode',
            'kehamilan' => 'Kehamilan',
            'umum' => 'Umum'
        ];

        $ibuNames = [];
        if ($role === 'admin_clinician' || $role === 'superadmin') {
            $query = DataDiri::ibu()
                ->select('id', 'nama')
                ->distinct();

            if ($scope['type'] === 'facility' && !empty($scope['puskesmas_id'])) {
                $query->where('puskesmas_id', $scope['puskesmas_id']);
            }

            $ibuNames = $query->orderBy('nama')
                ->pluck('nama', 'nama')
                ->toArray();
        }

        return [
            'years' => $years,
            'months' => $months,
            'trimesters' => $trimesters,
            'periodes' => $periodes,
            'dateRanges' => $dateRanges,
            'screeningTypes' => $screeningTypes,
            'epdsModes' => $epdsModes,
            'dassModes' => $dassModes,
            'ibuNames' => $ibuNames,
        ];
    }

    // ===================== Role & Scope =====================

    private function mapRoleFromUser($user): string
    {
        $roleId = (int)($user->role_id ?? 1);

        switch ($roleId) {
            case 3:
                return 'superadmin';
            case 2:
                return 'admin_clinician';
            case 1:
            default:
                return 'user';
        }
    }

    private function resolveScope($user, string $role): array
    {
        if ($role === 'superadmin') {
            return ['type' => 'all'];
        }

        if ($role === 'admin_clinician') {
            $puskesmasId = $user->puskesmas_id ?? null;
            if (!$puskesmasId) {
                $profileStaf = $user->profileStaf;
                $puskesmasId = $profileStaf?->puskesmas_id;
            }
            return ['type' => 'facility', 'puskesmas_id' => $puskesmasId];
        }

        return ['type' => 'self'];
    }

    // ===================== KPI =====================

    private function buildScopedKpi(array $scope, array $filters): array
    {
        $epdsQ = HasilEpds::query()
            ->where('status', 'submitted');

        $epdsQ = $this->applyFiltersToQuery($epdsQ, $filters, 'epds');

        $dassKehamilanQ = HasilDass::kehamilan()
            ->where('status', 'submitted');

        $dassKehamilanQ = $this->applyFiltersToQuery($dassKehamilanQ, $filters, 'dass');

        $dassUmumQ = HasilDass::umum()
            ->where('status', 'submitted');

        $dassUmumQ = $this->applyFiltersToQuery($dassUmumQ, $filters, 'dass');

        if ($scope['type'] === 'facility' && !empty($scope['puskesmas_id'])) {
            $epdsQ->join('data_diri as dd', 'dd.id', '=', 'hasil_epds.ibu_id')
                ->where('dd.puskesmas_id', $scope['puskesmas_id'])
                ->whereNotNull('dd.faskes_rujukan_id');

            $dassKehamilanQ->join('data_diri as dd', 'dd.id', '=', 'hasil_dass.ibu_id')
                ->where('dd.puskesmas_id', $scope['puskesmas_id'])
                ->whereNotNull('dd.faskes_rujukan_id');

            $dassUmumQ->join('data_diri as dd', 'dd.id', '=', 'hasil_dass.ibu_id')
                ->where('dd.puskesmas_id', $scope['puskesmas_id'])
                ->whereNotNull('dd.faskes_rujukan_id');
        } elseif ($scope['type'] === 'all') {
            $epdsQ->join('data_diri as dd', 'dd.id', '=', 'hasil_epds.ibu_id')
                ->whereNotNull('dd.faskes_rujukan_id');

            $dassKehamilanQ->join('data_diri as dd', 'dd.id', '=', 'hasil_dass.ibu_id')
                ->whereNotNull('dd.faskes_rujukan_id');

            $dassUmumQ->join('data_diri as dd', 'dd.id', '=', 'hasil_dass.ibu_id')
                ->whereNotNull('dd.faskes_rujukan_id');
        }

        return [
            'epds_count' => $epdsQ->distinct('session_token')->count('session_token'),
            'dass_kehamilan_count' => $dassKehamilanQ->distinct('session_token')->count('session_token'),
            'dass_umum_count' => $dassUmumQ->distinct('session_token')->count('session_token'),
            'dass_total_count' => $dassKehamilanQ->distinct('session_token')->count('session_token') +
                $dassUmumQ->distinct('session_token')->count('session_token'),
        ];
    }

    // ===================== Facility Stats =====================

    private function buildFacilityStats(array $scope, array $filters): array
    {
        $latestUhSub = UsiaHamil::query()
            ->select('ibu_id', DB::raw('MAX(created_at) AS max_created'))
            ->whereNotNull('hpht')->whereNotNull('hpl')
            ->groupBy('ibu_id');

        $uh = UsiaHamil::from('usia_hamil as uh')
            ->joinSub($latestUhSub, 't', function ($j) {
                $j->on('uh.ibu_id', '=', 't.ibu_id')->on('uh.created_at', '=', 't.max_created');
            })
            ->join('data_diri as dd', 'dd.id', '=', 'uh.ibu_id')
            ->whereNotNull('dd.faskes_rujukan_id');

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

        $dd = DataDiri::query()
            ->whereNotNull('faskes_rujukan_id')
            ->whereHas('user', function ($q) {
                $q->where('role_id', 1);
            });

        if ($scope['type'] === 'facility' && !empty($scope['puskesmas_id'])) {
            $dd->where('puskesmas_id', $scope['puskesmas_id']);
        }

        $totalIbu = $dd->count();

        return [
            'total_ibu' => $totalIbu,
            'trimester_counts' => $counts,
        ];
    }

    private function fetchLatestScreenings(array $scope, array $filters, int $limit = 12, int $perDayLimit = 5): array
    {
        // EPDS
        $epdsBaseQ = HasilEpds::where('status', 'submitted')
            ->whereNotNull('session_token');

        $epdsBaseQ = $this->applyFiltersToQuery($epdsBaseQ, $filters, 'epds');

        $subE1 = (clone $epdsBaseQ)
            ->select('session_token', DB::raw('MAX(screening_date) AS max_date'))
            ->groupBy('session_token');

        $subE2 = HasilEpds::from('hasil_epds as he1')
            ->joinSub($subE1, 't', function ($j) {
                $j->on('he1.session_token', '=', 't.session_token')
                    ->on('he1.screening_date', '=', 't.max_date');
            })
            ->selectRaw('he1.session_token, COALESCE(MAX(CASE WHEN he1.answers_epds_id IS NULL THEN he1.id END), MAX(he1.id)) AS pick_id')
            ->groupBy('he1.session_token');

        $epds = HasilEpds::from('hasil_epds as he')
            ->joinSub($subE2, 'p', function ($j) {
                $j->on('he.session_token', '=', 'p.session_token')
                    ->on('he.id', '=', 'p.pick_id');
            })
            ->join('data_diri as dd', 'dd.id', '=', 'he.ibu_id')
            ->leftJoin('usia_hamil as uh', function ($j) {
                $j->on('uh.ibu_id', '=', 'dd.id')
                    ->whereNotNull('uh.hpht')
                    ->whereNotNull('uh.hpl');
            })
            ->whereNotNull('dd.faskes_rujukan_id');

        if ($scope['type'] === 'facility' && !empty($scope['puskesmas_id'])) {
            $epds->where('dd.puskesmas_id', $scope['puskesmas_id']);
        }

        $epds = $epds->orderByDesc('he.screening_date')
            ->get(['he.*', 'dd.nama as ibu_nama', 'uh.hpht', 'uh.hpl']);

        // DASS
        $dassBaseQ = HasilDass::where('status', 'submitted')
            ->whereNotNull('session_token');

        $dassBaseQ = $this->applyFiltersToQuery($dassBaseQ, $filters, 'dass');

        $subD1 = (clone $dassBaseQ)
            ->select('session_token', DB::raw('MAX(screening_date) AS max_date'))
            ->groupBy('session_token');

        $subD2 = HasilDass::from('hasil_dass as hd1')
            ->joinSub($subD1, 't', function ($j) {
                $j->on('hd1.session_token', '=', 't.session_token')
                    ->on('hd1.screening_date', '=', 't.max_date');
            })
            ->selectRaw('hd1.session_token, MAX(hd1.id) AS pick_id')
            ->groupBy('hd1.session_token');

        $dass = HasilDass::from('hasil_dass as hd')
            ->joinSub($subD2, 'p', function ($j) {
                $j->on('hd.session_token', '=', 'p.session_token')
                    ->on('hd.id', '=', 'p.pick_id');
            })
            ->join('data_diri as dd', 'dd.id', '=', 'hd.ibu_id')
            ->leftJoin('usia_hamil as uh', function ($j) {
                $j->on('uh.ibu_id', '=', 'dd.id')
                    ->whereNotNull('uh.hpht')
                    ->whereNotNull('uh.hpl');
            })
            ->whereNotNull('dd.faskes_rujukan_id');

        if ($scope['type'] === 'facility' && !empty($scope['puskesmas_id'])) {
            $dass->where('dd.puskesmas_id', $scope['puskesmas_id']);
        }

        $dassResults = $dass->orderByDesc('hd.screening_date')
            ->select(['hd.*', 'dd.nama as ibu_nama', 'uh.hpht', 'uh.hpl'])
            ->get();

        $items = [];

        foreach ($epds as $r) {
            $dt = Carbon::parse($r->screening_date);

            $usiaInfo = null;
            if ($r->mode === 'kehamilan' && $r->hpht) {
                $usiaMinggu = Kehamilan::hitungUsiaMinggu($r->hpht);
                $usiaInfo = [
                    'keterangan' => Kehamilan::hitungUsiaString($r->hpht),
                    'trimester' => Kehamilan::tentukanTrimester($usiaMinggu),
                ];
            }

            $items[] = [
                'type'  => 'EPDS',
                'mode'  => $r->mode ?? 'kehamilan',
                'ibu'   => $r->ibu_nama,
                'date'  => $dt->toDateString(),
                'label' => $dt->translatedFormat('d M Y'),
                'ts'    => $dt->timestamp,
                'scores' => ['total' => (int)$r->total_score],
                'usia_kehamilan' => $usiaInfo,
            ];
        }

        foreach ($dassResults as $r) {
            $dt = Carbon::parse($r->screening_date);

            $usiaInfo = null;
            if ($r->mode === 'kehamilan' && $r->hpht) {
                $usiaMinggu = Kehamilan::hitungUsiaMinggu($r->hpht);
                $usiaInfo = [
                    'keterangan' => Kehamilan::hitungUsiaString($r->hpht),
                    'trimester' => Kehamilan::tentukanTrimester($usiaMinggu),
                ];
            }

            $items[] = [
                'type'      => 'DASS-21',
                'jenis'     => $r->mode,
                'mode'      => $r->mode,
                'ibu'       => $r->ibu_nama,
                'date'      => $dt->toDateString(),
                'label'     => $dt->translatedFormat('d M Y'),
                'ts'        => $dt->timestamp,
                'scores'    => [
                    'dep'    => (int)$r->total_depression,
                    'anx'    => (int)$r->total_anxiety,
                    'stress' => (int)$r->total_stress,
                ],
                'trimester' => $r->trimester,
                'periode'   => $r->periode,
                'usia_kehamilan' => $usiaInfo,
            ];
        }

        $items = collect($items)
            ->sortByDesc('ts')
            ->values()
            ->groupBy('date')
            ->flatMap(fn($g) => $g->take($perDayLimit))
            ->values();

        if ($limit > 0) {
            $items = $items->take($limit)->values();
        }

        return $items->all();
    }

    // ===================== Tren =====================

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
            ['code' => 'pasca_hamil', 'name' => 'Pasca Melahirkan',   'start' => $pp],
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
                $w->whereDoesntHave('rules');

                $w->orWhereHas('rules', function ($r) use ($scores, $trimester) {
                    $r->where(function ($cond) use ($scores) {
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
            $coverUrl = $this->getContentCoverUrl($c);

            $imgCount = $c->media->where('media_type', 'image')->count();
            $videoCount = $c->media->where('media_type', 'video')->count();
            $embedCount = $c->media->where('media_type', 'embed')->count();

            $hasVideo = ($videoCount + $embedCount) > 0;

            // Tentukan badge berdasarkan media type
            $badge = null;
            if ($hasVideo) {
                $badge = 'Video';
            } elseif ($imgCount > 1) {
                $badge = "{$imgCount} gambar";
            } elseif ($imgCount === 1) {
                $badge = 'Gambar';
            }

            return [
                'id'       => $c->id,
                'title'    => $c->title,
                'summary'  => $c->summary,
                'url'      => route('edukasi.show', $c->slug),
                'cover'    => $coverUrl,
                'badge'    => $badge,
                'tags'     => $c->tags->pluck('name')->take(3)->values()->all(),
            ];
        })->all();
    }

    /**
     * Get cover URL for education content with priority:
     * 1. cover_path dari content
     * 2. thumbnail_path dari media pertama (video upload)
     * 3. poster_path dari media pertama (gambar/video)
     * 4. path dari media image pertama
     * 5. YouTube thumbnail dari embed pertama
     */
    private function getContentCoverUrl($content): ?string
    {
        // Priority 1: cover_path dari content
        if ($content->cover_path) {
            return $content->cover_path;
        }

        // Priority 2-3: cek media pertama untuk thumbnail atau poster
        $firstMedia = $content->media->first();
        if ($firstMedia) {
            // Video upload biasanya punya thumbnail_path
            if ($firstMedia->thumbnail_path) {
                return $firstMedia->thumbnail_path;
            }

            // Poster path (bisa dari video atau gambar)
            if ($firstMedia->poster_path) {
                return $firstMedia->poster_path;
            }
        }

        // Priority 4: cari media type image pertama
        $imageMedia = $content->media->firstWhere('media_type', 'image');
        if ($imageMedia && $imageMedia->path) {
            return $imageMedia->path;
        }

        // Priority 5: YouTube thumbnail dari embed
        $embedMedia = $content->media->firstWhere('media_type', 'embed');
        if ($embedMedia && $embedMedia->external_url) {
            $youtubeId = $this->youtubeId($embedMedia->external_url);
            if ($youtubeId) {
                return "https://i.ytimg.com/vi/{$youtubeId}/hqdefault.jpg";
            }
        }

        return null;
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
