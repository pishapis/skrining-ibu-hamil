<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DataDiri;
use App\Models\Anak;
use App\Models\Suami;
use App\Models\RiwayatKesehatan;
use App\Models\HasilEpds;
use App\Models\HasilDass;
use App\Models\UsiaHamil;
use App\Models\Puskesmas;
use App\Models\AnswerDass;
use App\Models\AnswerEpds;
use App\Models\SkriningDass;
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
        $scope = $this->resolveScope($user, $role);

        // ======= Filters dari query string =======
        $monthParam = trim((string) $request->input('month', ''));
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

        $jenisParam = trim((string) $request->input('mode', ''));

        $puskesmasFilterId = null;
        if ($role === 'superadmin') {
            $puskesmasFilterId = $request->integer('puskesmas_id') ?: null;
        }

        $monthOptions = [];
        for ($i = 0; $i < 12; $i++) {
            $d = Carbon::now()->subMonths($i);
            $monthOptions[] = [
                'value' => $d->format('Y-m'),
                'label' => $d->translatedFormat('F Y'),
            ];
        }

        $puskesmasList = ($role === 'superadmin')
            ? Puskesmas::orderBy('nama')->get(['id', 'nama'])
            : collect();

        // ======= Usia hamil untuk user biasa =======
        $usia_hamil = [];
        if ($scope['type'] === 'self') {
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
        }

        $answer_epds  = AnswerEpds::with(['epds:id,pertanyaan'])->get();
        $answer_dass  = AnswerDass::all();
        $skrining_dass = SkriningDass::orderBy('id')->get();

        // ======= EPDS: Ambil SEMUA baris detail dengan relasi =======
        $epds_detail = HasilEpds::from('hasil_epds as he')
            ->leftJoin('data_diri as dd', 'dd.id', '=', 'he.ibu_id')
            ->leftJoin('usia_hamil as uh', function ($join) {
                $join->on('uh.ibu_id', '=', 'dd.id')
                    ->whereNotNull('uh.hpht')
                    ->whereNotNull('uh.hpl');
            })
            ->leftJoin('suami as sm', 'sm.ibu_id', '=', 'dd.id')
            ->leftJoin('anak as an', 'an.ibu_id', '=', 'dd.id')
            ->leftJoin('riwayat_kesehatan as rk', 'rk.ibu_id', '=', 'dd.id')
            ->leftJoin('indonesia_villages as kel', 'kel.code', '=', 'dd.kode_des')
            ->leftJoin('indonesia_districts as kec', 'kec.code', '=', 'dd.kode_kec')
            ->leftJoin('indonesia_cities as kota', 'kota.code', '=', 'dd.kode_kab')
            ->leftJoin('indonesia_provinces as prov', 'prov.code', '=', 'dd.kode_prov')
            ->leftJoin('puskesmas as pusk', 'pusk.id', '=', 'dd.puskesmas_id')
            ->leftJoin('fasilitas_kesehatan_rujukan as faskes', 'faskes.id', '=', 'dd.faskes_rujukan_id')
            ->when(
                $scope['type'] === 'facility' && !empty($scope['puskesmas_id']),
                fn($q) => $q->where('dd.puskesmas_id', $scope['puskesmas_id'])
            )
            ->when(
                $scope['type'] === 'all' && !empty($puskesmasFilterId),
                fn($q) => $q->where('dd.puskesmas_id', $puskesmasFilterId)
            )
            ->when(
                $scope['type'] === 'self',
                fn($q) => $q->where('dd.user_id', $user->id)
            )
            ->when(
                $monthStart && $monthEnd,
                fn($q) => $q->whereBetween('he.screening_date', [$monthStart, $monthEnd])
            )
            ->when(
                $jenisParam === 'kehamilan',
                fn($q) => $q->whereNotNull('he.usia_hamil_id')->whereNotNull('he.trimester')
            )
            ->when(
                $jenisParam === 'umum',
                fn($q) => $q->whereNull('he.usia_hamil_id')->where('he.mode', 'umum')
            )
            ->where('he.status', 'submitted')
            ->select(
                'he.*',
                'he.batch_no',
                // Data Diri Ibu
                'dd.nama as ibu_nama',
                'dd.nik',
                'dd.tempat_lahir',
                'dd.tanggal_lahir',
                'dd.pendidikan_terakhir',
                'dd.pekerjaan',
                'dd.agama',
                'dd.golongan_darah',
                'dd.alamat_rumah',
                'dd.is_luar_wilayah',
                'dd.no_telp',
                'dd.no_jkn',
                // Wilayah
                'kel.name as kelurahan_nama',
                'kec.name as kecamatan_nama',
                'kota.name as kota_nama',
                'prov.name as provinsi_nama',
                // Faskes
                'pusk.nama as puskesmas_nama',
                'faskes.nama as faskes_rujukan_nama',
                // Usia Hamil
                'uh.hpht',
                'uh.hpl',
                // Suami
                'sm.nama as suami_nama',
                'sm.tempat_lahir as suami_tempat_lahir',
                'sm.tanggal_lahir as suami_tanggal_lahir',
                'sm.pendidikan_terakhir as suami_pendidikan',
                'sm.pekerjaan as suami_pekerjaan',
                'sm.agama as suami_agama',
                'sm.no_telp as suami_no_telp',
                // Anak
                'an.nama as anak_nama',
                'an.tanggal_lahir as anak_tanggal_lahir',
                'an.jenis_kelamin as anak_jenis_kelamin',
                'an.no_jkn as anak_no_jkn',
                'an.catatan as anak_catatan',
                // Riwayat Kesehatan
                'rk.kehamilan_ke',
                'rk.jml_anak_lahir_hidup',
                'rk.riwayat_keguguran',
                'rk.riwayat_penyakit'
            )
            ->orderBy('he.screening_date', 'desc')
            ->get();

        // ======= DASS: Ambil SEMUA baris detail dengan relasi =======
        $dass_detail = HasilDass::from('hasil_dass as hd')
            ->leftJoin('data_diri as dd', 'dd.id', '=', 'hd.ibu_id')
            ->leftJoin('usia_hamil as uh', function ($join) {
                $join->on('uh.ibu_id', '=', 'dd.id')
                    ->whereNotNull('uh.hpht')
                    ->whereNotNull('uh.hpl');
            })
            ->leftJoin('suami as sm', 'sm.ibu_id', '=', 'dd.id')
            ->leftJoin('anak as an', 'an.ibu_id', '=', 'dd.id')
            ->leftJoin('riwayat_kesehatan as rk', 'rk.ibu_id', '=', 'dd.id')
            ->leftJoin('indonesia_villages as kel', 'kel.code', '=', 'dd.kode_des')
            ->leftJoin('indonesia_districts as kec', 'kec.code', '=', 'dd.kode_kec')
            ->leftJoin('indonesia_cities as kota', 'kota.code', '=', 'dd.kode_kab')
            ->leftJoin('indonesia_provinces as prov', 'prov.code', '=', 'dd.kode_prov')
            ->leftJoin('puskesmas as pusk', 'pusk.id', '=', 'dd.puskesmas_id')
            ->leftJoin('fasilitas_kesehatan_rujukan as faskes', 'faskes.id', '=', 'dd.faskes_rujukan_id')
            ->when(
                $scope['type'] === 'facility' && !empty($scope['puskesmas_id']),
                fn($q) => $q->where('dd.puskesmas_id', $scope['puskesmas_id'])
            )
            ->when(
                $scope['type'] === 'all' && !empty($puskesmasFilterId),
                fn($q) => $q->where('dd.puskesmas_id', $puskesmasFilterId)
            )
            ->when(
                $scope['type'] === 'self',
                fn($q) => $q->where('dd.user_id', $user->id)
            )
            ->when(
                $monthStart && $monthEnd,
                fn($q) => $q->whereBetween('hd.screening_date', [$monthStart, $monthEnd])
            )
            ->when(
                $jenisParam === 'kehamilan',
                fn($q) => $q->whereNotNull('hd.usia_hamil_id')->whereNotNull('hd.trimester')
            )
            ->when(
                $jenisParam === 'umum',
                fn($q) => $q->whereNull('hd.usia_hamil_id')->where('hd.mode', 'umum')
            )
            ->where('hd.status', 'submitted')
            ->select(
                'hd.*',
                'hd.batch_no',
                // Data Diri Ibu
                'dd.nama as ibu_nama',
                'dd.nik',
                'dd.tempat_lahir',
                'dd.tanggal_lahir',
                'dd.pendidikan_terakhir',
                'dd.pekerjaan',
                'dd.agama',
                'dd.golongan_darah',
                'dd.alamat_rumah',
                'dd.is_luar_wilayah',
                'dd.no_telp',
                'dd.no_jkn',
                // Wilayah
                'kel.name as kelurahan_nama',
                'kec.name as kecamatan_nama',
                'kota.name as kota_nama',
                'prov.name as provinsi_nama',
                // Faskes
                'pusk.nama as puskesmas_nama',
                'faskes.nama as faskes_rujukan_nama',
                // Usia Hamil
                'uh.hpht',
                'uh.hpl',
                // Suami
                'sm.nama as suami_nama',
                'sm.tempat_lahir as suami_tempat_lahir',
                'sm.tanggal_lahir as suami_tanggal_lahir',
                'sm.pendidikan_terakhir as suami_pendidikan',
                'sm.pekerjaan as suami_pekerjaan',
                'sm.agama as suami_agama',
                'sm.no_telp as suami_no_telp',
                // Anak
                'an.nama as anak_nama',
                'an.tanggal_lahir as anak_tanggal_lahir',
                'an.jenis_kelamin as anak_jenis_kelamin',
                'an.no_jkn as anak_no_jkn',
                'an.catatan as anak_catatan',
                // Riwayat Kesehatan
                'rk.kehamilan_ke',
                'rk.jml_anak_lahir_hidup',
                'rk.riwayat_keguguran',
                'rk.riwayat_penyakit'
            )
            ->orderBy('hd.screening_date', 'desc')
            ->get();

        // ======= Group untuk tampilan (1 row per session) =======
        $epds_grouped = $epds_detail->groupBy('session_token')->map(function ($group) use ($usia_hamil) {
            $first = $group->first();
            $ends = $group->sortByDesc('created_at')->first();
            $dt = Carbon::parse($first->screening_date);

            // Hitung usia kehamilan per ibu
            $usia_ibu = [];
            if ($first->hpht) {
                $usiaMinggu = hitungUsiaKehamilanMinggu($first->hpht);
                $usia_ibu = [
                    'hpht' => $first->hpht,
                    'hpl' => $first->hpl,
                    'usia_minggu' => $usiaMinggu,
                    'keterangan' => hitungUsiaKehamilanString($first->hpht),
                    'trimester' => tentukanTrimester($usiaMinggu),
                ];
            }

            return [
                'id'         => $first->session_token,
                'type'       => 'EPDS',
                'date_iso'   => $dt->toDateString(),
                'date_human' => $dt->translatedFormat('d M Y'),
                'year'       => $dt->year,
                'batch_no'   => $ends->batch_no,
                'trimester'  => $first->trimester,
                'scores'     => ['epds_total' => (int) ($first->total_score ?? 0)],
                'usia_hamil' => !empty($usia_ibu) ? $usia_ibu : $usia_hamil,
                'ibu'        => $first->ibu_nama ?? 'Tidak diketahui',
            ];
        });

        $dass_grouped = $dass_detail->groupBy('session_token')->map(function ($group) use ($usia_hamil) {
            $first = $group->first();
            $ends = $group->sortByDesc('created_at')->first();
            $dt = Carbon::parse($first->screening_date);

            $jenis = 'umum';
            if (!empty($first->usia_hamil_id) || !empty($first->trimester)) {
                $jenis = 'kehamilan';
            }

            // Hitung usia kehamilan per ibu
            $usia_ibu = [];
            if ($first->hpht) {
                $usiaMinggu = hitungUsiaKehamilanMinggu($first->hpht);
                $usia_ibu = [
                    'hpht' => $first->hpht,
                    'hpl' => $first->hpl,
                    'usia_minggu' => $usiaMinggu,
                    'keterangan' => hitungUsiaKehamilanString($first->hpht),
                    'trimester' => tentukanTrimester($usiaMinggu),
                ];
            }

            return [
                'id'         => $first->session_token,
                'type'       => 'DASS-21',
                'date_iso'   => $dt->toDateString(),
                'date_human' => $dt->translatedFormat('d M Y'),
                'year'       => $dt->year,
                'batch_no'   => $ends->batch_no,
                'trimester'  => $first->trimester,
                'jenis'      => $jenis,
                'scores'     => [
                    'dep'    => (int) ($first->total_depression ?? 0),
                    'anx'    => (int) ($first->total_anxiety   ?? 0),
                    'stress' => (int) ($first->total_stress    ?? 0),
                ],
                'usia_hamil' => !empty($usia_ibu) ? $usia_ibu : $usia_hamil,
                'ibu'        => $first->ibu_nama ?? 'Tidak diketahui',
            ];
        });

        $items = $epds_grouped->merge($dass_grouped)->sortByDesc('date_iso')->values();

        $filters = [
            'month'        => $monthParam,
            'jenis'        => $jenisParam,
            'puskesmas_id' => $puskesmasFilterId,
        ];

        // dd($epds_detail);

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
            'epds_detail',  // PENTING: Kirim detail lengkap untuk export
            'dass_detail'   // PENTING: Kirim detail lengkap untuk export
        ));
    }

    /**
     * Untuk user pribadi: auto-detect jenis DASS berdasarkan data
     */
    private function itemsForSelf($ibuId, $monthStart, $monthEnd, $jenisParam, $usia_hamil)
    {
        $items = [];

        // EPDS (selalu kehamilan)
        $epdsRows = HasilEpds::where('ibu_id', $ibuId)
            ->where('status', 'submitted')
            ->when($monthStart && $monthEnd, fn($q) => $q->whereBetween('screening_date', [$monthStart, $monthEnd]))
            ->with(['answersEpds'])
            ->orderBy('screening_date', 'desc')
            ->get();

        foreach ($epdsRows as $row) {
            $items[] = [
                'id'          => $row->id,
                'type'        => 'EPDS',
                'jenis'       => 'kehamilan', // EPDS selalu kehamilan
                'date_iso'    => $row->screening_date ?? $row->submitted_at ?? $row->created_at,
                'date_human'  => optional($row->screening_date ?? $row->submitted_at)->translatedFormat('d M Y'),
                'year'        => optional($row->screening_date ?? $row->submitted_at)->year,
                'trimester'   => $row->trimester,
                'usia_hamil'  => $usia_hamil,
                'scores'      => ['epds_total' => $row->total_score],
                'ibu'         => optional($row->ibu)->nama ?? '—',
            ];
        }

        // DASS - Auto detect berdasarkan model
        $dassQuery = HasilDass::where('ibu_id', $ibuId)
            ->where('status', 'submitted')
            ->when($monthStart && $monthEnd, fn($q) => $q->whereBetween('screening_date', [$monthStart, $monthEnd]));

        // Filter berdasarkan jenis yang dipilih
        if ($jenisParam === 'kehamilan') {
            $dassQuery->kehamilan(); // gunakan scope
        } elseif ($jenisParam === 'umum') {
            $dassQuery->umum(); // gunakan scope
        }

        $dassRows = $dassQuery->orderBy('screening_date', 'desc')->get();

        foreach ($dassRows as $row) {
            $items[] = [
                'id'          => $row->id,
                'type'        => 'DASS-21',
                'jenis'       => $row->jenis_label, // menggunakan accessor
                'mode'        => $row->mode, // 'kehamilan' atau 'umum'
                'date_iso'    => $row->screening_date ?? $row->submitted_at ?? $row->created_at,
                'date_human'  => optional($row->screening_date ?? $row->submitted_at)->translatedFormat('d M Y'),
                'year'        => optional($row->screening_date ?? $row->submitted_at)->year,
                'trimester'   => $row->trimester, // untuk kehamilan
                'periode'     => $row->periode, // untuk umum
                'usia_hamil'  => $row->is_kehamilan ? $usia_hamil : null,
                'scores'      => [
                    'dep'    => $row->total_depression,
                    'anx'    => $row->total_anxiety,
                    'stress' => $row->total_stress,
                ],
                'ibu'         => optional($row->ibu)->nama ?? '—',
            ];
        }

        return $items;
    }

    private function itemsForScope($scope, $puskesmasFilterId, $monthStart, $monthEnd, $jenisParam)
    {
        $items = [];

        // EPDS Query
        $epdsQuery = HasilEpds::from('hasil_epds as he')
            ->leftJoin('data_diri as dd', 'dd.id', '=', 'he.ibu_id')
            ->leftJoin('usia_hamil as uh', 'uh.id', '=', 'he.usia_hamil_id')
            ->when($scope['type'] === 'facility', fn($q) => $q->where('dd.puskesmas_id', $scope['puskesmas_id']))
            ->when($scope['type'] === 'all' && $puskesmasFilterId, fn($q) => $q->where('dd.puskesmas_id', $puskesmasFilterId))
            ->when($monthStart && $monthEnd, fn($q) => $q->whereBetween('he.screening_date', [$monthStart, $monthEnd]))
            ->where('he.status', 'submitted')
            ->orderBy('he.screening_date', 'desc')
            ->get([
                'he.id',
                'he.ibu_id',
                'he.screening_date',
                'he.submitted_at',
                'he.created_at',
                'he.trimester',
                'he.total_score',
                'dd.nama as ibu_nama',
                'uh.hpht',
                'uh.hpl'
            ]);

        foreach ($epdsQuery as $row) {
            $usiaMinggu = $row->hpht ? hitungUsiaKehamilanMinggu($row->hpht) : null;
            $items[] = [
                'id'         => $row->id,
                'type'       => 'EPDS',
                'jenis'      => 'kehamilan',
                'date_iso'   => $row->screening_date ?? $row->submitted_at ?? $row->created_at,
                'date_human' => optional($row->screening_date ?? $row->submitted_at)->translatedFormat('d M Y'),
                'year'       => optional($row->screening_date ?? $row->submitted_at)->year,
                'trimester'  => $row->trimester,
                'usia_hamil' => $usiaMinggu ? [
                    'hpht' => $row->hpht,
                    'hpl' => $row->hpl,
                    'usia_minggu' => $usiaMinggu,
                    'keterangan' => hitungUsiaKehamilanString($row->hpht),
                ] : null,
                'scores'     => ['epds_total' => $row->total_score],
                'ibu'        => $row->ibu_nama ?? '—',
            ];
        }

        // DASS Query dengan auto-detect
        $dassQuery = HasilDass::from('hasil_dass as hd')
            ->leftJoin('data_diri as dd', 'dd.id', '=', 'hd.ibu_id')
            ->leftJoin('usia_hamil as uh', 'uh.id', '=', 'hd.usia_hamil_id')
            ->when($scope['type'] === 'facility', fn($q) => $q->where('dd.puskesmas_id', $scope['puskesmas_id']))
            ->when($scope['type'] === 'all' && $puskesmasFilterId, fn($q) => $q->where('dd.puskesmas_id', $puskesmasFilterId))
            ->when($monthStart && $monthEnd, fn($q) => $q->whereBetween('hd.screening_date', [$monthStart, $monthEnd]))
            ->when($jenisParam === 'kehamilan', fn($q) => $q->whereNotNull('hd.usia_hamil_id')->where('hd.mode', 'kehamilan'))
            ->when($jenisParam === 'umum', fn($q) => $q->whereNull('hd.usia_hamil_id')->where('hd.mode', 'umum'))
            ->where('hd.status', 'submitted')
            ->orderBy('hd.screening_date', 'desc')
            ->get([
                'hd.id',
                'hd.ibu_id',
                'hd.screening_date',
                'hd.submitted_at',
                'hd.created_at',
                'hd.trimester',
                'hd.mode',
                'hd.periode',
                'hd.usia_hamil_id',
                'hd.total_depression',
                'hd.total_anxiety',
                'hd.total_stress',
                'dd.nama as ibu_nama',
                'uh.hpht',
                'uh.hpl'
            ]);

        foreach ($dassQuery as $row) {
            $isKehamilan = !is_null($row->usia_hamil_id) && $row->mode === 'kehamilan';
            $usiaMinggu = $isKehamilan && $row->hpht ? hitungUsiaKehamilanMinggu($row->hpht) : null;

            $items[] = [
                'id'         => $row->id,
                'type'       => 'DASS-21',
                'jenis'      => $isKehamilan ? 'kehamilan' : 'umum',
                'mode'       => $row->mode,
                'date_iso'   => $row->screening_date ?? $row->submitted_at ?? $row->created_at,
                'date_human' => optional($row->screening_date ?? $row->submitted_at)->translatedFormat('d M Y'),
                'year'       => optional($row->screening_date ?? $row->submitted_at)->year,
                'trimester'  => $row->trimester,
                'periode'    => $row->periode,
                'usia_hamil' => $isKehamilan && $usiaMinggu ? [
                    'hpht' => $row->hpht,
                    'hpl' => $row->hpl,
                    'usia_minggu' => $usiaMinggu,
                    'keterangan' => hitungUsiaKehamilanString($row->hpht),
                ] : null,
                'scores'     => [
                    'dep'    => $row->total_depression,
                    'anx'    => $row->total_anxiety,
                    'stress' => $row->total_stress,
                ],
                'ibu'        => $row->ibu_nama ?? '—',
            ];
        }

        return $items;
    }

    // Helper methods tetap sama
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
