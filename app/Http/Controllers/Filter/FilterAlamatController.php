<?php

namespace App\Http\Controllers\Filter;

use App\Http\Controllers\Controller;
use App\Models\Provinsi;
use App\Models\Kota;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\FasilitasKesehatan;
use Illuminate\Support\Facades\Auth;
use App\Models\Puskesmas;
use Illuminate\Http\Request;

class FilterAlamatController extends Controller
{
    /**
     * Display the registration view.
     */

    public function filter_kota(Request $request)
    {
        try {
            $data = $request->validate([
                'provId' => ['required'],
            ]);

            $kota = Kota::where('province_code', $data['provId'])->get();
            return response()->json([
                'ack' => 'ok',
                'message' => 'Data kota berhasil ditemukan',
                'data' => $kota,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ack' => 'bad',
                'message' => 'Terjadi kesalahan saat mengambil data kota: ' . $e->getMessage(),
            ], 200);
        }
    }

    public function filter_kecamatan(Request $request)
    {
        try {

            $data = $request->validate([
                'kotaId' => ['required'],
            ]);

            $kecamatan = Kecamatan::where('city_code', $data['kotaId'])->get();

            return response()->json([
                'ack' => 'ok',
                'message' => 'Data kecamatan berhasil ditemukan',
                'data' => $kecamatan,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ack' => 'bad',
                'message' => 'Terjadi kesalahan saat mengambil data kecamatan: ' . $e->getMessage(),
            ], 200);
        }
    }

    public function filter_kel(Request $request)
    {
        try {
            $data = $request->validate([
                'kecId' => ['required'],
            ]);

            $role = null;
            if (Auth::check() == true){
                $user = Auth::user();
                $role = $user->role_id;
            }

            $kelurahan = Kelurahan::where('district_code', $data['kecId'])->get();
            $puskesmasQuery = Puskesmas::where('kode_kec', $data['kecId']);
            if ($role == 2){
                $puskesmasQuery->where('id', $user->puskesmas_id);
            }
            $puskesmas = $puskesmasQuery->orderBy('nama')->get();

            return response()->json([
                'ack' => 'ok',
                'message' => 'Data kelurahan berhasil ditemukan',
                'data' => [
                    'kelurahan' => $kelurahan,
                    'puskesmas' => $puskesmas,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ack' => 'bad',
                'message' => 'Terjadi kesalahan saat mengambil data kelurahan: ' . $e->getMessage(),
            ], 200);
        }
    }

    public function filter_faskes(Request $request)
    {
        try {
            $data = $request->validate([
                'kota_id' => ['required'],
            ]);

            $faskes = FasilitasKesehatan::where('kode_kota', $data['kota_id'])
            ->get();

            return response()->json([
                'ack' => 'ok',
                'message' => 'Data fasilitas kesehatan berhasil ditemukan',
                'data' => $faskes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ack' => 'bad',
                'message' => 'Terjadi kesalahan saat mengambil data fasilitas kesehatan: ' . $e->getMessage(),
            ], 200);
        }
    }
}
