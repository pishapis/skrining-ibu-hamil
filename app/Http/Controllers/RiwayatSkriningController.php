<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DataDiri;
use App\Models\HasilEpds;
use App\Models\HasilDass;
use App\Models\UsiaHamil;
use App\Models\Puskesmas;
use App\Models\AnswerDass;
use App\Models\AnswerEpds;
use App\Models\SkriningDass;
use App\Models\SkriningEpds;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RiwayatSkriningController extends Controller
{
    public function index(Request $request)
    {
        $title = "Riwayat Skrining";

        $user  = Auth::user();
        $role  = $this->mapRoleFromUser($user);
        $scope = $this->resolveScope($user, $role); // ['type' => self|facility|all, 'puskesmas_id' => ?]

        // ======= Filters dari query string =======
        $monthParam = trim((string) $request->input('month', '')); // 'YYYY-MM' atau ''
        $monthStart = $monthEnd = null;
        if ($monthParam) {
            try {
                [$y, $m] = explode('-', $monthParam);
                $monthStart = Carbon::createFromDate((int)$y, (int)$m, 1)->startOfDay();
                $monthEnd   = $monthStart->copy()->endOfMonth()->endOfDay();
            } catch (\Throwable $e) {
                $monthStart = $monthEnd = null;
            }
        }

        // Superadmin boleh pilih puskesmas; admin facility pakai scope-nya sendiri
        $puskesmasFilterId = null;
        if ($role === 'superadmin') {
            $puskesmasFilterId = $request->integer('puskesmas_id') ?: null;
        }

        // Opsi bulan (12 bulan terakhir) untuk dropdown
        $monthOptions = [];
        for ($i = 0; $i < 12; $i++) {
            $d = Carbon::now()->subMonths($i);
            $monthOptions[] = [
                'value' => $d->format('Y-m'),
                'label' => $d->translatedFormat('F Y'),
            ];
        }

        // List puskesmas untuk superadmin
        $puskesmasList = ($role === 'superadmin')
            ? Puskesmas::orderBy('nama')->get(['id', 'nama'])
            : collect();

        // ======= Build data sesuai scope =======
        $usia_hamil = [];
        $items = collect();

        if ($scope['type'] === 'self') {
            // Data diri & usia hamil terbaru
            $dataDiri = DataDiri::where('user_id', $user->id)->firstOrFail();

            $riwayat = UsiaHamil::where('ibu_id', $dataDiri->id)
                ->whereNotNull('hpht')->whereNotNull('hpl')
                ->latest('created_at')->first();

            if ($riwayat) {
                $usiaMinggu = hitungUsiaKehamilanMinggu($riwayat->hpht);
                $usia_hamil = [
                    'id'          => $riwayat->id,
                    'hpht'        => $riwayat->hpht,
                    'hpl'         => $riwayat->hpl,
                    'usia_minggu' => $usiaMinggu,
                    'keterangan'  => hitungUsiaKehamilanString($riwayat->hpht),
                    'trimester'   => tentukanTrimester($usiaMinggu),
                ];
            }

            // Ambil items untuk user ini (boleh pakai filter bulan kalau diisi)
            $items = collect($this->itemsForSelf($dataDiri->id, $monthStart, $monthEnd, $usia_hamil));
        } else {
            // Admin facility / Superadmin → data sesuai scope + filter server-side
            $items = collect($this->itemsForScope($scope, $puskesmasFilterId, $monthStart, $monthEnd));
        }

        // Urutkan terbaru
        $items = $items->sortByDesc('date_iso')->values();

        $filters = [
            'month'        => $monthParam,
            'puskesmas_id' => $puskesmasFilterId,
        ];

        $answer_epds  = AnswerEpds::with(['epds:id,pertanyaan'])->get();
        $answer_dass  = AnswerDass::all();
        $skrining_dass = SkriningDass::orderBy('id')->get();

        $epds_export = HasilEpds::from('hasil_epds as he')
            ->leftJoin('data_diri as dd', 'dd.id', '=', 'he.ibu_id')
            ->leftJoin('indonesia_villages as kel', 'kel.code', '=', 'dd.kode_des')
            ->leftJoin('indonesia_districts as kec', 'kec.code', '=', 'dd.kode_kec')
            ->when(
                $scope['type'] === 'facility' && !empty($scope['puskesmas_id']),
                fn($q) => $q->where('dd.puskesmas_id', $scope['puskesmas_id'])
            )
            ->when(
                $scope['type'] === 'all' && !empty($puskesmasFilterId),
                fn($q) => $q->where('dd.puskesmas_id', $puskesmasFilterId)
            )
            ->when(
                $monthStart && $monthEnd,
                fn($q) => $q->whereBetween('he.screening_date', [$monthStart, $monthEnd])
            )
            ->where('he.status', 'submitted')
            ->orderBy('he.screening_date')
            ->get([
                'he.id',
                'he.ibu_id',
                'he.epds_id',
                'he.answers_epds_id',
                'he.screening_date',
                'he.trimester',
                'he.total_score',
                'he.session_token',
                'he.submitted_at',
                'he.batch_no',
                'dd.nama as ibu_nama',
                'dd.alamat_rumah',
                'kel.name as kelurahan_nama',
                'kec.name as kecamatan_nama',
                // RT/RW memang tidak ada di DataDiri saat ini → kirim kosong
                DB::raw('NULL as rt_rw'),
            ]);

        // --- DASS rows untuk export: JOIN kelurahan/kecamatan ---
        $dass_export = HasilDass::from('hasil_dass as hd')
            ->leftJoin('data_diri as dd', 'dd.id', '=', 'hd.ibu_id')
            ->leftJoin('indonesia_villages as kel', 'kel.code', '=', 'dd.kode_des')
            ->leftJoin('indonesia_districts as kec', 'kec.code', '=', 'dd.kode_kec')
            ->when(
                $scope['type'] === 'facility' && !empty($scope['puskesmas_id']),
                fn($q) => $q->where('dd.puskesmas_id', $scope['puskesmas_id'])
            )
            ->when(
                $scope['type'] === 'all' && !empty($puskesmasFilterId),
                fn($q) => $q->where('dd.puskesmas_id', $puskesmasFilterId)
            )
            ->when(
                $monthStart && $monthEnd,
                fn($q) => $q->whereBetween('hd.screening_date', [$monthStart, $monthEnd])
            )
            ->where('hd.status', 'submitted')
            ->orderBy('hd.screening_date')
            ->get([
                'hd.id',
                'hd.ibu_id',
                'hd.dass_id',
                'hd.answers_dass_id',
                'hd.screening_date',
                'hd.trimester',
                'hd.total_depression',
                'hd.total_anxiety',
                'hd.total_stress',
                'hd.session_token',
                'hd.submitted_at',
                'hd.batch_no',
                'dd.nama as ibu_nama',
                'dd.alamat_rumah',
                'kel.name as kelurahan_nama',
                'kec.name as kecamatan_nama',
                DB::raw('NULL as rt_rw'),
            ]);

        return view('pages.riwayat.index', compact(
            'title',
            'role',
            'scope',
            'usia_hamil',
            'items',
            'filters',
            'monthOptions',
            'puskesmasList',
            'answer_epds',
            'answer_dass',
            'skrining_dass',
            'epds_export',
            'dass_export'
        ));
    }

    // ===================== Items builder =====================

    /**
     * Untuk user pribadi: ambil 1 baris final per session_token
     * - EPDS: prioritaskan row ringkasan (answers_epds_id IS NULL), fallback MAX(id)
     * - DASS: pakai MAX(id)
     */
    private function itemsForSelf(int $ibuId, ?Carbon $start, ?Carbon $end, array $usia_hamil = []): array
    {
        // EPDS — pick final row per session
        $subE1 = HasilEpds::select('session_token', DB::raw('MAX(screening_date) AS max_date'))
            ->where('ibu_id', $ibuId)
            ->where('status', 'submitted')
            ->whereNotNull('session_token')
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
            ->where('he.ibu_id', $ibuId);

        if ($start && $end) {
            $epds->whereBetween('he.screening_date', [$start, $end]);
        }

        $epds = $epds->orderByDesc('he.screening_date')
            ->get(['he.id', 'he.screening_date', 'he.trimester', 'he.total_score']);

        // DASS — pick MAX(id) per session
        $subD1 = HasilDass::select('session_token', DB::raw('MAX(screening_date) AS max_date'))
            ->where('ibu_id', $ibuId)
            ->where('status', 'submitted')
            ->whereNotNull('session_token')
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
            ->where('hd.ibu_id', $ibuId);

        if ($start && $end) {
            $dass->whereBetween('hd.screening_date', [$start, $end]);
        }

        $dass = $dass->orderByDesc('hd.screening_date')
            ->get(['hd.id', 'hd.screening_date', 'hd.trimester', 'hd.total_depression', 'hd.total_anxiety', 'hd.total_stress']);

        // Map ke items
        $items = [];

        foreach ($epds as $r) {
            $dt = Carbon::parse($r->screening_date);
            $items[] = [
                'id'         => $r->id,
                'type'       => 'EPDS',
                'date_iso'   => $dt->toDateString(),
                'date_human' => $dt->translatedFormat('d M Y'),
                'year'       => $dt->year,
                'trimester'  => $r->trimester,
                'scores'     => ['epds_total' => (int) ($r->total_score ?? 0)],
                'usia_hamil' => $usia_hamil,
            ];
        }

        foreach ($dass as $r) {
            $dt = Carbon::parse($r->screening_date);
            $items[] = [
                'id'         => $r->id,
                'type'       => 'DASS-21',
                'date_iso'   => $dt->toDateString(),
                'date_human' => $dt->translatedFormat('d M Y'),
                'year'       => $dt->year,
                'trimester'  => $r->trimester,
                'scores'     => [
                    'dep'    => (int) ($r->total_depression ?? 0),
                    'anx'    => (int) ($r->total_anxiety   ?? 0),
                    'stress' => (int) ($r->total_stress    ?? 0),
                ],
                'usia_hamil' => $usia_hamil,
            ];
        }

        return $items;
    }

    /**
     * Untuk admin/superadmin: scope fasilitas/all + filter puskesmas (superadmin)
     */
    private function itemsForScope(array $scope, ?int $puskesmasFilterId, ?Carbon $start, ?Carbon $end): array
    {
        // EPDS — final row per session + join data_diri untuk scope
        $subE1 = HasilEpds::select('session_token', DB::raw('MAX(screening_date) AS max_date'))
            ->where('status', 'submitted')
            ->whereNotNull('session_token')
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
            ->join('data_diri as dd', 'dd.id', '=', 'he.ibu_id');

        if ($scope['type'] === 'facility' && !empty($scope['puskesmas_id'])) {
            $epds->where('dd.puskesmas_id', $scope['puskesmas_id']);
        }
        if ($scope['type'] === 'all' && $puskesmasFilterId) {
            $epds->where('dd.puskesmas_id', $puskesmasFilterId);
        }
        if ($start && $end) {
            $epds->whereBetween('he.screening_date', [$start, $end]);
        }

        $epds = $epds->orderByDesc('he.screening_date')
            ->get([
                'he.id',
                'he.screening_date',
                'he.trimester',
                'he.total_score',
                'dd.id as ibu_id',
                'dd.nama as ibu_nama' // <-- tambahkan ini
            ]);

        // DASS — final row per session + join data_diri
        $subD1 = HasilDass::select('session_token', DB::raw('MAX(screening_date) AS max_date'))
            ->where('status', 'submitted')
            ->whereNotNull('session_token')
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
            ->join('data_diri as dd', 'dd.id', '=', 'hd.ibu_id');

        if ($scope['type'] === 'facility' && !empty($scope['puskesmas_id'])) {
            $dass->where('dd.puskesmas_id', $scope['puskesmas_id']);
        }
        if ($scope['type'] === 'all' && $puskesmasFilterId) {
            $dass->where('dd.puskesmas_id', $puskesmasFilterId);
        }
        if ($start && $end) {
            $dass->whereBetween('hd.screening_date', [$start, $end]);
        }

        $dass = $dass->orderByDesc('hd.screening_date')
            ->get([
                'hd.id',
                'hd.screening_date',
                'hd.trimester',
                'hd.total_depression',
                'hd.total_anxiety',
                'hd.total_stress',
                'dd.id as ibu_id',
                'dd.nama as ibu_nama' // <-- tambahkan ini
            ]);

        $ibuIds = $epds->pluck('ibu_id')->merge($dass->pluck('ibu_id'))->unique()->filter()->values();

        $usiaMap = [];
        if ($ibuIds->isNotEmpty()) {
            $subUh = UsiaHamil::select('ibu_id', DB::raw('MAX(created_at) AS max_created'))
                ->whereIn('ibu_id', $ibuIds)
                ->whereNotNull('hpht')->whereNotNull('hpl')
                ->groupBy('ibu_id');

            $uhRows = UsiaHamil::from('usia_hamil as uh')
                ->joinSub($subUh, 't', function ($j) {
                    $j->on('uh.ibu_id', '=', 't.ibu_id')->on('uh.created_at', '=', 't.max_created');
                })
                ->get(['uh.ibu_id', 'uh.hpht', 'uh.hpl']);

            foreach ($uhRows as $u) {
                $minggu = hitungUsiaKehamilanMinggu($u->hpht);
                $usiaMap[$u->ibu_id] = [
                    'hpht'        => $u->hpht,
                    'hpl'         => $u->hpl,
                    'usia_minggu' => $minggu,
                    'keterangan'  => hitungUsiaKehamilanString($u->hpht),
                    'trimester'   => tentukanTrimester($minggu),
                ];
            }
        }

        // Map ke items (usia_hamil tidak disertakan di scope aggregate)
        $items = [];

        foreach ($epds as $r) {
            $dt = Carbon::parse($r->screening_date);
            $items[] = [
                'id'         => $r->id,
                'type'       => 'EPDS',
                'date_iso'   => $dt->toDateString(),
                'date_human' => $dt->translatedFormat('d M Y'),
                'year'       => $dt->year,
                'trimester'  => $r->trimester,
                'scores'     => ['epds_total' => (int) ($r->total_score ?? 0)],
                'ibu'        => $r->ibu_nama,
                'usia_hamil' => $usiaMap[$r->ibu_id] ?? null,
            ];
        }

        foreach ($dass as $r) {
            $dt = Carbon::parse($r->screening_date);
            $items[] = [
                'id'         => $r->id,
                'type'       => 'DASS-21',
                'date_iso'   => $dt->toDateString(),
                'date_human' => $dt->translatedFormat('d M Y'),
                'year'       => $dt->year,
                'trimester'  => $r->trimester,
                'scores'     => [
                    'dep'    => (int) ($r->total_depression ?? 0),
                    'anx'    => (int) ($r->total_anxiety   ?? 0),
                    'stress' => (int) ($r->total_stress    ?? 0),
                ],
                'ibu'        => $r->ibu_nama,
                'usia_hamil' => $usiaMap[$r->ibu_id] ?? null,
            ];
        }

        return $items;
    }

    // ===================== Helpers: Role & Scope (copy dari Dashboard) =====================

    private function mapRoleFromUser($user): string
    {
        $rid = (int)($user->role_id ?? 1);
        if ($rid === 3) return 'superadmin';
        if ($rid === 2) {
            $title = strtolower(trim((string)($user->role_name ?? $user->job_title ?? $user->role ?? '')));
            $hasFacility = !empty($user->puskesmas_id) || preg_match('/puskesmas|klinik|rs|fasyankes/', $title);
            $isClinician = preg_match('/bidan|dokter|psikolog/', $title);
            if ($hasFacility && !$isClinician) return 'admin_facility';
            return 'admin_clinician';
        }
        return 'user';
    }

    private function resolveScope($user, string $role): array
    {
        if ($role === 'superadmin') return ['type' => 'all'];
        if ($role === 'admin_facility' || $role === 'admin_clinician') {
            $pid = $user->puskesmas_id ?? null;
            return ['type' => 'facility', 'puskesmas_id' => $pid];
        }
        return ['type' => 'self'];
    }
}
