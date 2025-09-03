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
use App\Models\Jabatan;
use App\Models\Anak;
use App\Models\Suami;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class PenggunaController extends Controller
{
    /**
     * Display the registration view.
     */

    public function index()
    {
        $title = "Manajemen Pengguna";
        $dataIbu = DataDiri::with(['user', 'kec', 'kel', 'prov', 'faskes', 'puskesmas', 'anak', 'suami'])
            ->paginate(10, ['*'], 'page_ibu');

        $data_puskesmas = DataDiri::with(['user', 'kec', 'kel', 'prov', 'puskesmas'])
            ->whereHas('user', fn($q) => $q->where('role_id', 2))
            ->paginate(10, ['*'], 'page_puskesmas');

        $provinsis = DataDiri::optionsProvinsi();
        $kota           = collect(); // biarkan kosong, akan diisi via AJAX onChange
        $kec            = collect();
        $desa           = collect();

        $puskesmas      = Puskesmas::orderBy('nama')->get();
        $rujukan        = FasilitasKesehatan::orderBy('nama')->get();
        $daftarJabatan  = Jabatan::orderBy('nama')->get();

        return view('pages.master.pengguna.index', [
            'title' => $title,
            'dataIbu' => $dataIbu,
            'data_puskesmas' => $data_puskesmas,
            'provinsis' => $provinsis,
            'kota' => $kota,
            'kec' => $kec,
            'desa' => $desa,
            'puskesmas' => $puskesmas,
            'rujukan' => $rujukan,
            'daftarJabatan' => $daftarJabatan,
        ]);
    }

    public function updateIbu(Request $request, DataDiri $dataDiri)
    {
        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'nik'               => ['required', 'string', 'max:32', Rule::unique('data_diri', 'nik')->ignore($dataDiri->id)],
            'tempat_lahir'      => ['nullable', 'string', 'max:255'],
            'tanggal_lahir'     => ['nullable', 'date'],
            'pendidikan'        => ['nullable', 'in:sd,smp,sma,d3,s1,s2,s3,lainnya'],
            'pekerjaan'         => ['nullable', 'string', 'max:64'],
            'agama'             => ['nullable', 'in:islam,protestan,katolik,hindu,buddha,konghucu,lainnya'],
            'gol_darah'         => ['nullable', 'in:a,b,ab,o'],
            'prov_id'           => ['nullable', 'string', 'max:16', Rule::exists('provinsi', 'code')],
            'kota_id'           => ['nullable', 'string', 'max:16', Rule::exists('kota', 'code')],
            'kec_id'            => ['nullable', 'string', 'max:16', Rule::exists('kecamatan', 'code')],
            'kelurahan_id'      => ['nullable', 'string', 'max:16', Rule::exists('kelurahan', 'code')],
            'alamat_rumah'      => ['nullable', 'string', 'max:1000'],
            'no_telp'           => ['nullable', 'string', 'max:32'],
            'puskesmas_id'      => ['nullable', Rule::exists('puskesmas', 'id')],
            'faskes_rujukan_id' => ['nullable', Rule::exists('fasilitas_kesehatan', 'id')],
            'no_jkn'            => ['nullable', 'string', 'max:64'],
        ]);

        // Map field form -> kolom DataDiri
        $dataDiri->fill([
            'nama'                => $validated['name'],
            'nik'                 => $validated['nik'],
            'tempat_lahir'        => $validated['tempat_lahir'] ?? null,
            'tanggal_lahir'       => $validated['tanggal_lahir'] ?? null,
            'pendidikan_terakhir' => $validated['pendidikan'] ?? null,
            'pekerjaan'           => $validated['pekerjaan'] ?? null,
            'agama'               => $validated['agama'] ?? null,
            'golongan_darah'      => $validated['gol_darah'] ?? null,
            'kode_prov'           => $validated['prov_id'] ?? null,
            'kode_kab'            => $validated['kota_id'] ?? null,
            'kode_kec'            => $validated['kec_id'] ?? null,
            'kode_des'            => $validated['kelurahan_id'] ?? null,
            'alamat_rumah'        => $validated['alamat_rumah'] ?? null,
            'no_telp'             => $validated['no_telp'] ?? null,
            'puskesmas_id'        => $validated['puskesmas_id'] ?? null,
            'faskes_rujukan_id'   => $validated['faskes_rujukan_id'] ?? null,
            'no_jkn'              => $validated['no_jkn'] ?? null,
        ])->save();

        // Opsional: sinkronkan nama ke tabel users
        if ($dataDiri->user) {
            $dataDiri->user->name = $validated['name'];
            $dataDiri->user->save();
        }

        return back()->with('success', 'Data ibu berhasil diperbarui.')
            ->with('tab', 'ibu');
    }

    // ================== UPDATE PUSKESMAS (STAF) ==================
    public function updatePuskesmas(Request $request, DataDiri $dataDiri)
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'nik'          => ['required', 'string', 'max:32', Rule::unique('data_diri', 'nik')->ignore($dataDiri->id)],
            // tidak ada field: tempat/tgl lahir, pendidikan, pekerjaan, agama, gol_darah, no_jkn, faskes_rujukan_id
            'prov_id'      => ['nullable', 'string', 'max:16', Rule::exists('provinsi', 'code')],
            'kota_id'      => ['nullable', 'string', 'max:16', Rule::exists('kota', 'code')],
            'kec_id'       => ['nullable', 'string', 'max:16', Rule::exists('kecamatan', 'code')],
            'kelurahan_id' => ['nullable', 'string', 'max:16', Rule::exists('kelurahan', 'code')],
            'alamat_rumah' => ['nullable', 'string', 'max:1000'],
            'no_telp'      => ['nullable', 'string', 'max:32'],
            'puskesmas_id' => ['nullable', Rule::exists('puskesmas', 'id')],
            'jabatan_id'   => ['required', Rule::exists('jabatans', 'id')],
        ]);

        $dataDiri->fill([
            'nama'         => $validated['name'],
            'nik'          => $validated['nik'],
            'kode_prov'    => $validated['prov_id'] ?? null,
            'kode_kab'     => $validated['kota_id'] ?? null,
            'kode_kec'     => $validated['kec_id'] ?? null,
            'kode_des'     => $validated['kelurahan_id'] ?? null,
            'alamat_rumah' => $validated['alamat_rumah'] ?? null,
            'no_telp'      => $validated['no_telp'] ?? null,
            'puskesmas_id' => $validated['puskesmas_id'] ?? null,
        ])->save();

        // Set jabatan ke user terkait
        if ($dataDiri->user) {
            $dataDiri->user->jabatan_id = $validated['jabatan_id'];
            $dataDiri->user->name       = $validated['name']; // sinkron nama
            $dataDiri->user->save();
        }

        return back()->with('success', 'Data staf puskesmas berhasil diperbarui.')
            ->with('tab', 'puskesmas');
    }
}
