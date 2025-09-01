<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DataDiri;
use App\Models\UsiaHamil;
use App\Models\HasilEpds;
use App\Models\HasilDass;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // === ROLE SEDERHANA (tanpa getRoleNames) ===
        $roleRaw = strtolower((string)($user->role ?? 'user'));
        switch ($roleRaw) {
            case 'bidan':
            case 'psikolog':
            case 'psikolog klinis':
                $role = 'admin_clinician';
                break;
            case 'puskesmas':
                $role = 'admin_facility';
                break;
            case 'superadmin':
                $role = 'superadmin';
                break;
            case 'ibu':
            case 'user':
            default:
                $role = 'user';
        }

        // === DATA DIRI & USIA HAMIL TERBARU ===
        $dataDiri = DataDiri::where('user_id', $user->id)->first();
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

                $usiaMinggu = hitungUsiaKehamilanMinggu($riwayat->hpht);
                $usia = [
                    'hpht'        => $riwayat->hpht,
                    'hpl'         => $riwayat->hpl,
                    'usia_minggu' => $usiaMinggu,
                    'keterangan'  => hitungUsiaKehamilanString($riwayat->hpht),
                    'trimester'   => tentukanTrimester($usiaMinggu),
                ];
            }
        }

        // === EPDS & DASS (dedupe per session_token) ===
        $latestEpds = null;
        $latestDass = null;
        $epdsCount = 0;
        $dassCount = 0;

        $epdsSessions = collect();
        $dassSessions = collect();

        if ($dataDiri) {
            $epdsSessions = HasilEpds::where('ibu_id', $dataDiri->id)
                ->where('status', 'submitted')
                ->whereNotNull('session_token')
                ->orderByDesc('screening_date')
                ->get()
                ->unique('session_token')
                ->values();

            $dassSessions = HasilDass::where('ibu_id', $dataDiri->id)
                ->where('status', 'submitted')
                ->whereNotNull('session_token')
                ->orderByDesc('screening_date')
                ->get()
                ->unique('session_token')
                ->values();

            $latestEpds = $epdsSessions->first();
            $latestDass = $dassSessions->first();
            $epdsCount  = $epdsSessions->count();
            $dassCount  = $dassSessions->count();
        }

        // === TRIMESTER YANG SUDAH SUBMIT (untuk jadwal berikutnya) ===
        $submittedByTrimester = [];
        if ($dataDiri) {
            $submittedByTrimester = HasilEpds::where('ibu_id', $dataDiri->id)
                ->where('status', 'submitted')
                ->whereNotNull('trimester')
                ->get()
                ->groupBy('trimester')
                ->keys()
                ->toArray();
        }

        // === HITUNG JADWAL SKRINING BERIKUTNYA ===
        $nextSchedule = $this->computeNextScreeningSchedule($hpht, $hpl, $submittedByTrimester);

        // === KPI 30 HARI (untuk admin) ===
        $since = Carbon::now()->subDays(30);
        $kpi = [
            'epds_30d' => HasilEpds::where('status', 'submitted')->where('screening_date', '>=', $since)->distinct('session_token')->count('session_token'),
            'dass_30d' => HasilDass::where('status', 'submitted')->where('screening_date', '>=', $since)->distinct('session_token')->count('session_token'),
        ];

        // === ALERTS SEDERHANA ===
        $dassFlags = ['dep' => 15, 'anx' => 12, 'stress' => 20];
        $alerts = [];
        if ($latestEpds && (int)($latestEpds->total_score ?? 0) >= 13) {
            $alerts[] = ['type' => 'warning', 'text' => 'Hasil EPDS terakhir mengindikasikan depresi. Pertimbangkan konsultasi.'];
        }
        if ($latestDass) {
            if ((int)($latestDass->total_anxiety ?? 0)   >= $dassFlags['anx'])    $alerts[] = ['type' => 'info', 'text' => 'Kecemasan tinggi. Konsultasi Bidan & Psikolog disarankan.'];
            if ((int)($latestDass->total_depression ?? 0) >= $dassFlags['dep'])    $alerts[] = ['type' => 'info', 'text' => 'Depresi tinggi. Konsultasi Psikolog disarankan.'];
            if ((int)($latestDass->total_stress ?? 0)    >= $dassFlags['stress']) $alerts[] = ['type' => 'info', 'text' => 'Stres tinggi. Konsultasi Dokter & Psikolog disarankan.'];
        }

        // === DATA UNTUK GRAFIK TREN (user ini) ===
        $epdsTrend = $epdsSessions->sortBy('screening_date')->values()->map(function ($r) {
            $dt = Carbon::parse($r->screening_date);
            return [
                'date'  => $dt->toDateString(),
                'label' => $dt->translatedFormat('d M Y'),
                'total' => (int)($r->total_score ?? 0),
            ];
        })->values();

        $dassTrend = $dassSessions->sortBy('screening_date')->values()->map(function ($r) {
            $dt = Carbon::parse($r->screening_date);
            return [
                'date'    => $dt->toDateString(),
                'label'   => $dt->translatedFormat('d M Y'),
                'dep'     => (int)($r->total_depression ?? 0),
                'anx'     => (int)($r->total_anxiety ?? 0),
                'stress'  => (int)($r->total_stress ?? 0),
            ];
        })->values();

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
            'dassTrend'
        ));
    }

    private function computeNextScreeningSchedule(?Carbon $hpht, ?Carbon $hpl, array $submittedByTrimester): ?array
    {
        if (!$hpht) return null;
        $now = Carbon::now();

        $t1 = $hpht->copy();                   // T1 start
        $t2 = $hpht->copy()->addWeeks(14);     // T2 start
        $t3 = $hpht->copy()->addWeeks(28);     // T3 start
        $pp = $hpl?->copy() ?? $hpht->copy()->addWeeks(40); // Pasca

        $phases = [
            ['code' => 'trimester_1', 'name' => 'Trimester I',  'start' => $t1],
            ['code' => 'trimester_2', 'name' => 'Trimester II', 'start' => $t2],
            ['code' => 'trimester_3', 'name' => 'Trimester III', 'start' => $t3],
            ['code' => 'pasca_hamil', 'name' => 'Pasca Hamil',  'start' => $pp],
        ];

        $currentCode = null;
        try {
            $usiaMingguNow = hitungUsiaKehamilanMinggu($hpht->toDateString());
            $currentCode = tentukanTrimester($usiaMingguNow);
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

        return null; // semua fase sudah submit
    }
}
