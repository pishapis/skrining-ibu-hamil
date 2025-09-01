<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DataDiri;
use App\Models\HasilEpds;
use App\Models\HasilDass;
use App\Models\UsiaHamil;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RiwayatSkriningController extends Controller
{
    public function index()
    {
        $title = "Riwayat Skrining";

        $dataDiri = DataDiri::where('user_id', Auth::id())->firstOrFail();

        // Usia hamil terbaru (opsional ditampilkan)
        $riwayat = UsiaHamil::where('ibu_id', $dataDiri->id)
            ->whereNotNull('hpht')->whereNotNull('hpl')
            ->latest('created_at')->first();

        $usia_hamil = null;
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

        // Ambil hasil EPDS & DASS tersubmit
        $epds_raw = HasilEpds::where('ibu_id', $dataDiri->id)
            ->where('status', 'submitted')
            ->select(
                'session_token',
                DB::raw('MAX(screening_date) AS screening_date'),
                DB::raw('MAX(total_score)    AS total_score'),
                DB::raw('MAX(trimester)      AS trimester')
            )
            ->groupBy('session_token')
            ->orderByDesc('screening_date')
            ->get();

        $hasil_epds = $epds_raw->toBase()->values(); // unique() tidak wajib

        $dass_raw = HasilDass::where('ibu_id', $dataDiri->id)
            ->where('status', 'submitted')
            ->select(
                'session_token',
                DB::raw('MAX(screening_date)    AS screening_date'),
                DB::raw('MAX(total_depression)  AS total_depression'),
                DB::raw('MAX(total_anxiety)     AS total_anxiety'),
                DB::raw('MAX(total_stress)      AS total_stress'),
                DB::raw('MAX(trimester)         AS trimester')
            )
            ->groupBy('session_token')
            ->orderByDesc('screening_date')
            ->get();

        $hasil_dass = $dass_raw->toBase()->values();

        // ---- Map ke 1 array "items" untuk dipakai Alpine di Blade
        $epdsItems = $hasil_epds->map(function ($r) {
            $dt = Carbon::parse($r->screening_date);
            return [
                'id'         => $r->id,
                'type'       => 'EPDS',
                'date_iso'   => $dt->toDateString(),
                'date_human' => $dt->translatedFormat('d M Y'),
                'year'       => $dt->year,
                'trimester'  => $r->trimester,
                'scores'     => ['epds_total' => (int) ($r->total_score ?? 0)],
            ];
        });

        $dassItems = $hasil_dass->map(function ($r) {
            $dt = \Carbon\Carbon::parse($r->screening_date);
            return [
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
            ];
        });

        $items = $epdsItems->merge($dassItems)->sortByDesc('date_iso')->values();

        return view('pages.riwayat.index', compact('title', 'usia_hamil', 'items'));
    }
}
