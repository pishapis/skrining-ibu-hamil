<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\DataDiri;
use App\Models\FasilitasKesehatan;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Kota;
use App\Models\Puskesmas;
use App\Models\Provinsi;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */

    public function edit(Request $request): View
    {
        $id = Auth::user()->id;
        $role = Auth::user()->role_id;

        $data_diri = null;
        $puskesmas = null;
        $provinsis = null;
        $kota = null;
        $kec = null;
        $desa = null;
        $rujukan = null;

        if ($role != 3) {
            $data_diri = DataDiri::where('user_id', $id)->first();
            if ($data_diri) {
                $puskesmas = Puskesmas::where('kode_kec', $data_diri->kode_kec)->get();
                $kota = Kota::where('province_code', $data_diri->kode_prov)->get();
                $kec = Kecamatan::where('city_code', $data_diri->kode_kab)->get();
                $desa = Kelurahan::where('district_code', $data_diri->kode_kec)->get();
                $rujukan = FasilitasKesehatan::where('kode_kota', $data_diri->kode_kab)->get();
            }
        }

        $provinsis = Provinsi::all();

        return view('profile.edit', [
            'user' => $request->user(),
            'data_diri' => $data_diri,
            'puskesmas' => $puskesmas,
            'provinsis' => $provinsis,
            'kota' => $kota,
            'kec' => $kec,
            'desa' => $desa,
            'rujukan' => $rujukan,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            // ===== STEP 1: Akun
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($user->id),
            ],

            // ===== STEP 2: Data Ibu (biodata)
            'name'          => ['required', 'string', 'max:100'],
            'tempat_lahir'  => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],

            'pendidikan' => ['required', Rule::in(['sd', 'smp', 'sma', 'd3', 's1', 's2', 's3', 'lainnya'])],
            'pekerjaan'  => ['required', Rule::in(['dokter', 'perawat', 'bidan', 'dosen', 'mahasiswa', 'karyawan', 'ibu_rt', 'lainnya'])],
            'agama'      => ['required', Rule::in(['islam', 'protestan', 'katolik', 'hindu', 'buddha', 'konghucu', 'lainnya'])],
            'gol_darah'  => ['required', Rule::in(['a', 'b', 'ab', 'o'])],

            // kode wilayah (pakai string kalau pakai code)
            'prov_id'      => ['required', 'string'],
            'kota_id'      => ['required', 'string'],
            'kec_id'       => ['required', 'string'],
            'kelurahan_id' => ['required', 'string'],

            'alamat_rumah' => ['required', 'string', 'max:255'],
            'no_telp'      => ['nullable', 'string', 'max:20'],

            // field yang dipakai di bawah tapi belum divalidasi
            'nik'   => ['nullable', 'string', 'max:32'],   // ganti ke 'digits:16' kalau wajib KTP
            'no_jkn' => ['nullable', 'string', 'max:32'],

            'puskesmas_id'      => ['nullable', 'integer', 'exists:puskesmas,id'],
            'faskes_rujukan_id' => ['nullable', 'integer', 'exists:fasilitas_kesehatan_rujukan,id'],
        ]);

        try {
            // normalisasi nullable int
            $validated['faskes_rujukan_id'] = blank($validated['faskes_rujukan_id'] ?? null)
                ? null : (int) $validated['faskes_rujukan_id'];

            DB::transaction(function () use ($user, $validated) {
                // 1) Update user akun
                $user->update([
                    'name'  => $validated['name'],
                    'email' => Str::lower($validated['email']),
                ]);

                // 2) Update / create biodata ibu (DataDiri) milik user
                $dataDiri = DataDiri::firstOrCreate(['user_id' => $user->id]);

                $dataDiri->update([
                    'nama'                 => $validated['name'],
                    'tempat_lahir'         => $validated['tempat_lahir'],
                    'tanggal_lahir'        => $validated['tanggal_lahir'],
                    'pendidikan_terakhir'  => $validated['pendidikan'],
                    'pekerjaan'            => $validated['pekerjaan'],
                    'agama'                => $validated['agama'],
                    'golongan_darah'       => $validated['gol_darah'],

                    'kode_prov'            => (string) $validated['prov_id'],
                    'kode_kab'             => (string) $validated['kota_id'],
                    'kode_kec'             => (string) $validated['kec_id'],
                    'kode_des'             => (string) $validated['kelurahan_id'], // pastikan nama kolom benar (desa/des)

                    'alamat_rumah'         => $validated['alamat_rumah'],
                    'no_telp'              => $validated['no_telp'] ?? null,

                    'puskesmas_id'         => $validated['puskesmas_id'] ?? null,
                    'faskes_rujukan_id'    => $validated['faskes_rujukan_id'] ?? null,
                ]);
            });

            return Redirect::route('profile.edit')->with('status', 'profile-updated');
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->with('danger', 'Terjadi kesalahan di server. Silakan coba lagi.');
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
