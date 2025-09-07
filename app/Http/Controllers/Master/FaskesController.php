<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\FasilitasKesehatan;
use App\Models\Kota;
use App\Models\Puskesmas;
use App\Models\DataDiri;
use App\Models\Kecamatan;
use App\Models\Provinsi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FaskesController extends Controller
{
    /**
     * Display the registration view.
     */

    public function index()
    {
        $title          = "Manajemen Faskes";
        $rujukans       = FasilitasKesehatan::paginate(10, ['*'], 'page_rujukan');
        $puskesmas      = Puskesmas::with(['faskes'])->paginate(10, ['*'], 'page_puskesmas');
        $provinsis      = DataDiri::optionsProvinsi();
        $kota           = collect(); // biarkan kosong, akan diisi via AJAX onChange
        $kec            = collect();
        $rs             = FasilitasKesehatan::orderBy('nama')->get();

        return view('pages.master.faskes.index', [
            'title' => $title,
            'rujukans' => $rujukans,
            'puskesmas' => $puskesmas,
            'provinsis' => $provinsis,
            'kota' => $kota,
            'kec' => $kec,
            'rs' => $rs,
        ]);
    }

    public function createRujukan(Request $request)
    {
        $validated = $request->validate([
            'nama_rujukan' => 'required|string',
            'alamat_rujukan' => 'required|string',
            'prov_id_rujukan' => 'required',
            'kota_id_rujukan' => 'required',
            'kec_id_rujukan' => 'required',
            'no_telp_rujukan' => 'nullable',
        ]);

        try {
            $provinsi = Provinsi::where('code',$validated['prov_id_rujukan'])->first();
            $kota = Kota::where('code',$validated['kota_id_rujukan'])->first();
            $kec = Kecamatan::where('code',$validated['kec_id_rujukan'])->first();
            FasilitasKesehatan::create([
                'nama' => $validated['nama_rujukan'],
                'alamat' => $validated['alamat_rujukan'],
                'kode_prov' => $validated['prov_id_rujukan'],
                'kode_kota' => $validated['kota_id_rujukan'],
                'prov'      => $provinsi['name'],
                'kota'      => $kota['name'],
                'kec'      => $kec['name'],
                'kode_kec' => $validated['kec_id_rujukan'],
                'no_telp' => $validated['no_telp_rujukan'],
            ]);

            return redirect()->route('manajemen.faskes')->with('success', 'Data Rujukan Berhasil Dibuat')->with('tab', 'rujukan');

        } catch(\Exception $e){
             Log::error('Terjadi kesalahan di server. Silakan coba lagi', [
                'message'      => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'payload'      => $request->all(),
            ]);
            return redirect()->route('manajemen.faskes')
                ->with('error', ' Terjadi kesalahan, silakan coba lagi.')
                ->withInput()
                ->with('tab', 'rujukan');
        }
    }

    public function createPuskesmas(Request $request)
    {
        $validated = $request->validate([
            'nama_puskesmas' => 'required|string',
            'alamat_puskesmas' => 'required|string',
            'prov_id_puskesmas' => 'required',
            'kota_id_puskesmas' => 'required',
            'kec_id_puskesmas' => 'required',
            'faskes_rujukan_id' => 'nullable',
        ]);

        try {
            $provinsi = Provinsi::where('code',$validated['prov_id_puskesmas'])->first();
            $kota = Kota::where('code',$validated['kota_id_puskesmas'])->first();
            $kec = Kecamatan::where('code',$validated['kec_id_puskesmas'])->first();
            Puskesmas::create([
                'nama' => $validated['nama_puskesmas'],
                'alamat' => $validated['alamat_puskesmas'],
                'prov_id' => $validated['prov_id_puskesmas'],
                'kota_id' => $validated['kota_id_puskesmas'],
                'kec_id' => $validated['kec_id_puskesmas'],
                'faskes_rujukan_id' => $validated['faskes_rujukan_id'],
                'prov'      => $provinsi['name'],
                'kota'      => $kota['name'],
                'kec'      => $kec['name']
            ]);

            return redirect()->route('manajemen.faskes')->with('success', 'Data Rujukan Berhasil Diubah')->with('tab', 'rujukan');

        } catch(\Exception $e){
             Log::error('Terjadi kesalahan di server. Silakan coba lagi', [
                'message'      => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'payload'      => $request->all(),
            ]);
            return redirect()->route('manajemen.faskes')
                ->with('error', ' Terjadi kesalahan, silakan coba lagi.')
                ->withInput()
                ->with('tab', 'rujukan');
        }
    }

    public function updateRujukan(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required',
            'nama' => 'required|string',
            'alamat' => 'required|string',
            'prov_id' => 'required',
            'kota_id' => 'required',
            'kec_id' => 'required',
            'no_telp' => 'nullable',
        ]);

        try {
            $rujukan = FasilitasKesehatan::findOrFail($validated['id']);
            $provinsi = Provinsi::where('code',$validated['prov_id'])->first();
            $kota = Kota::where('code',$validated['kota_id'])->first();
            $kec = Kecamatan::where('code',$validated['kec_id'])->first();
            $rujukan->update([
                'nama' => $validated['nama'],
                'alamat' => $validated['alamat'],
                'kode_prov' => $validated['prov_id'],
                'kode_kota' => $validated['kota_id'],
                'prov'      => $provinsi['name'],
                'kota'      => $kota['name'],
                'kec'      => $kec['name'],
                'kode_kec' => $validated['kec_id'],
                'no_telp' => $validated['no_telp'],
            ]);

            return redirect()->route('manajemen.faskes')->with('success', 'Data Rujukan Berhasil Diubah')->with('tab', 'rujukan');

        } catch(\Exception $e){
             Log::error('Terjadi kesalahan di server. Silakan coba lagi', [
                'message'      => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'payload'      => $request->all(),
            ]);
            return redirect()->route('manajemen.faskes')
                ->with('error', ' Terjadi kesalahan, silakan coba lagi.')
                ->withInput()
                ->with('tab', 'rujukan');
        }
    }

    public function updatePuskesmas(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required',
            'nama' => 'required|string',
            'alamat' => 'required|string',
            'prov_id' => 'required',
            'kota_id_pus' => 'required',
            'kec_id_pus' => 'required',
            'faskes_rujukan_id' => 'nullable',
        ]);

        try {
            $puskesmas = Puskesmas::findOrFail($validated['id']);
            $provinsi = Provinsi::where('code',$validated['prov_id'])->first();
            $kota = Kota::where('code',$validated['kota_id_pus'])->first();
            $kec = Kecamatan::where('code',$validated['kec_id_pus'])->first();
            $puskesmas->update([
                'nama' => $validated['nama'],
                'alamat' => $validated['alamat'],
                'prov_id' => $validated['prov_id'],
                'kota_id' => $validated['kota_id_pus'],
                'kec_id' => $validated['kec_id_pus'],
                'faskes_rujukan_id' => $validated['faskes_rujukan_id'],
                'prov'      => $provinsi['name'],
                'kota'      => $kota['name'],
                'kec'      => $kec['name']
            ]);

            return redirect()->route('manajemen.faskes')->with('success', 'Data Rujukan Berhasil Diubah')->with('tab', 'puskesmas');

        } catch(\Exception $e){
             Log::error('Terjadi kesalahan di server. Silakan coba lagi', [
                'message'      => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'payload'      => $request->all(),
            ]);
            return redirect()->route('manajemen.faskes')
                ->with('error', ' Terjadi kesalahan, silakan coba lagi.')
                ->withInput()
                ->with('tab', 'puskesmas');
        }
    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');
        // $jabatan = Jabatan::findOrFail($id);
        // $jabatan->delete();
        return redirect()->route('manajemen.faskes')->with('success', 'Jabatan Berhasil Dihapus');
    }
}