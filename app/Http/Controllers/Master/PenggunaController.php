<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Provinsi;
use App\Models\Kota;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\FasilitasKesehatan;
use App\Models\Puskesmas;
use App\Models\DataDiri;
use App\Models\Anak;
use App\Models\Suami;
use Illuminate\Http\Request;

class PenggunaController extends Controller
{
    /**
     * Display the registration view.
     */

    public function index()
    {
        $title = "Manajemen Pengguna";
        $data = DataDiri::with(['user','kec','kel','prov','faskes','puskesmas','anak','suami'])->get();
        return view('pages.master.pengguna.index', compact('title','data'));
    }
}
