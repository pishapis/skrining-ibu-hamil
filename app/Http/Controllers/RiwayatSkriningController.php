<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DataDiri;
use App\Models\HasilEpds;
use App\Models\HasilDass;
use App\Models\UsiaHamil;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RiwayatSkriningController extends Controller
{
    public function index()
    {
        $title = "Riwayat Skrining";
        $data_diri = DataDiri::where('user_id', Auth::user()->id)->first();
        $usia_hamil = UsiaHamil::where('ibu_id', $data_diri->id)->get();
        $hasil_epds = HasilEpds::with(['epds','answersEpds','ibu'])
                    ->where('ibu_id', $data_diri->id)
                    ->where('status', 'submitted')
                    ->whereNot('epds_id', null)
                    ->orderByDesc('screening_date')
                    ->get();
                    
        $hasil_dass = HasilDass::with(['dass','answersDass','ibu'])
                    ->where('ibu_id', $data_diri->id)
                    ->where('status', 'submitted')
                    ->whereNot('dass_id', null)
                    ->orderByDesc('screening_date')
                    ->get();

        $usia_hamil = null;
         
        if ($usia_hamil) {
            foreach ($usia_hamil as $key => $riwayat) {
                $usiaMinggu  = hitungUsiaKehamilanMinggu($riwayat->hpht);
                $trimester   = tentukanTrimester($usiaMinggu);
                $keterangan  = hitungUsiaKehamilanString($riwayat->hpht);
    
                $usia_hamil = [
                    'id'          => $riwayat->id,
                    'hpht'        => $riwayat->hpht,
                    'hpl'         => $riwayat->hpl,
                    'usia_minggu' => $usiaMinggu,
                    'keterangan'  => $keterangan,
                    'trimester'   => $trimester,
                ];
            }
        }
        return view('pages.riwayat.index', [
            'title'      => $title,
            'usia_hamil' => $usia_hamil,
            'hasil_epds' => $hasil_epds,
            'hasil_dass' => $hasil_dass
        ]);
    }
}