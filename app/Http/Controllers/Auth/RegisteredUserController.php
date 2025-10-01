<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Provinsi;
use App\Models\Puskesmas;
use App\Models\FasilitasKesehatan;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Str;
use App\Models\DataDiri;
use App\Models\Suami;
use App\Models\Anak;
use App\Models\Jabatan;
use App\Models\RiwayatKesehatan;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $puskesmas = Puskesmas::all();
        $faskes = FasilitasKesehatan::all();
        $provinsis     = DataDiri::optionsProvinsi();
        $daftarJabatan = Jabatan::all();
        return view('auth.register', compact('puskesmas', 'faskes', 'provinsis', 'daftarJabatan'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // ===== STEP 1: Akun
            'username'  => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')],
            'email'     => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->letters()->numbers()],
            'password_confirmation' => ['required'],
            'is_luar_wilayah' => ['required', 'boolean'],

            // ===== STEP 2: Data Ibu (biodata)
            'name'              => ['required', 'string', 'max:100'],
            'nik'               => ['required', 'digits:16', Rule::unique('data_diri', 'nik')], // sesuaikan table
            'tempat_lahir'      => ['required', 'string', 'max:100'],
            'tanggal_lahir'     => ['required', 'date', 'before_or_equal:today'],
            'pendidikan'        => ['required', Rule::in(['sd', 'smp', 'sma', 'd3', 's1', 's2', 's3', 'lainnya'])],
            'pekerjaan'         => ['required', Rule::in(['dokter', 'perawat', 'bidan', 'dosen', 'mahasiswa', 'karyawan', 'ibu_rt', 'lainnya'])],
            'agama'             => ['required', Rule::in(['islam', 'protestan', 'katolik', 'hindu', 'buddha', 'konghucu', 'lainnya'])],
            'gol_darah'         => ['required', Rule::in(['a', 'b', 'ab', 'o'])],

            'prov_id'           => ['required', 'string'],
            'kota_id'           => ['required', 'string'],
            'kec_id'            => ['required', 'string'],
            'kelurahan_id'      => ['required', 'string'],

            'alamat_rumah'      => ['required', 'string', 'max:255'],
            'no_telp'           => ['nullable', 'string', 'max:20'],
            'no_jkn'            => ['nullable', 'digits:13'],

            'puskesmas_id'     => ['nullable','integer','exists:puskesmas,id'],
            'faskes_rujukan_id' => ['nullable','integer','exists:fasilitas_kesehatan_rujukan,id'],

            'kehamilan_ke'              => ['required', 'integer', 'min:1'],
            'jumlah_anak_lahir_hidup'   => ['required', 'integer', 'min:0'],
            'riwayat_keguguran'         => ['nullable', 'integer', 'min:0'],

            'penyakit_ids'              => ['nullable', 'array'],
            'penyakit_ids.*'            => ['integer', Rule::in([1, 2, 3, 4, 5])],
            'penyakit_lainnya'          => [
                Rule::requiredIf(fn() => in_array(5, $request->input('penyakit_ids', []))),
                'nullable',
                'string',
                'max:100'
            ],
            'riwayat_penyakit_text'     => ['nullable', 'string', 'max:500'],

            // ===== STEP 4: Suami & Anak
            'suami.nama'            => ['nullable', 'string', 'max:100'],
            'suami.tempat_lahir'    => ['nullable', 'string', 'max:100'],
            'suami.tanggal_lahir'   => ['nullable', 'date', 'before_or_equal:today'],
            'suami.pendidikan'        => ['required', Rule::in(['sd', 'smp', 'sma', 'd3', 's1', 's2', 's3', 'lainnya'])],
            'suami.pekerjaan'         => ['required', Rule::in(['dokter', 'perawat', 'bidan', 'dosen', 'petani', 'karyawan', 'wiraswasta', 'lainnya'])],
            'suami.agama'             => ['required', Rule::in(['islam', 'protestan', 'katolik', 'hindu', 'buddha', 'konghucu', 'lainnya'])],
            'suami.no_telp'         => ['nullable', 'string', 'max:20'],

            'anak'                      => ['nullable', 'array'],
            'anak.*.nama'               => ['nullable', 'string', 'max:100'],
            'anak.*.tanggal_lahir'      => ['nullable', 'date', 'before_or_equal:today'],
            'anak.*.jenis_kelamin'   => ['nullable', 'string', 'in:L,P'],
            'anak.*.no_jkn'             => ['nullable', 'digits:13'],
        ], [
            'no_jkn.digits' => 'No JKN harus 13 digit.',
        ]);

        try {
            $validated['faskes_rujukan_id'] = blank($validated['faskes_rujukan_id'] ?? null) ? null : (int)$validated['faskes_rujukan_id'];
            return DB::transaction(function () use ($request, $validated) {
                // 1) User
                $user = User::create([
                    'role_id'       => 1, //user
                    'puskesmas_id'  => $validated['puskesmas_id'],
                    'name'          => $validated['name'],
                    'username'      => $validated['username'],
                    'email'         => $validated['email'],
                    'password'      => Hash::make($validated['password']),
                ]);

                // 2) Biodata Ibu (DataDiri)
                $dataDiri = DataDiri::create([
                    'user_id'           => $user->id,
                    'nama'              => $validated['name'],
                    'nik'               => $validated['nik'],
                    'tempat_lahir'      => $validated['tempat_lahir'],
                    'tanggal_lahir'     => $validated['tanggal_lahir'],
                    'pendidikan_terakhir'        => $validated['pendidikan'],
                    'pekerjaan'         => $validated['pekerjaan'],
                    'agama'             => $validated['agama'],
                    'golongan_darah'         => $validated['gol_darah'],
                    
                    'is_luar_wilayah'  => $validated['is_luar_wilayah'],
                    'kode_prov'         => $validated['prov_id'],
                    'kode_kab'         => $validated['kota_id'],
                    'kode_kec'          => $validated['kec_id'],
                    'kode_des'    => $validated['kelurahan_id'],

                    'alamat_rumah'      => $validated['alamat_rumah'],
                    'no_telp'           => $validated['no_telp'] ?? null,
                    'no_jkn'            => $validated['no_jkn'] ?? null,

                    'puskesmas_id'     => $validated['puskesmas_id'] ?? null,
                    'faskes_rujukan_id' => $validated['faskes_rujukan_id'] ?? null,
                ]);

                // 5) Simpan Riwayat ke tabel lain
                RiwayatKesehatan::create([
                    'ibu_id'                    => $dataDiri->id,  // FK ke DataDiri
                    'kehamilan_ke'              => $validated['kehamilan_ke'],
                    'jml_anak_lahir_hidup'      => $validated['jumlah_anak_lahir_hidup'],
                    'riwayat_keguguran'         => $validated['riwayat_keguguran'] ?? 0,
                    'riwayat_penyakit' => implode(',', $validated['penyakit_ids'] ?? []) .
                        ($validated['penyakit_lainnya'] ? ' | lainnya: ' . $validated['penyakit_lainnya'] : '') .
                        ($validated['riwayat_penyakit_text'] ? ' | catatan: ' . $validated['riwayat_penyakit_text'] : ''),
                ]);

                // 6) Suami (opsional)
                if (filled(data_get($validated, 'suami.nama'))) {
                    Suami::create([
                        'ibu_id'            => $dataDiri->id,
                        'nama'              => data_get($validated, 'suami.nama'),
                        'tempat_lahir'      => data_get($validated, 'suami.tempat_lahir'),
                        'tanggal_lahir'     => data_get($validated, 'suami.tanggal_lahir'),
                        'pendidikan_terakhir'     => data_get($validated, 'suami.pendidikan'),
                        'pekerjaan'      => data_get($validated, 'suami.pekerjaan'),
                        'agama'          => data_get($validated, 'suami.agama'),
                        'no_telp'       => data_get($validated, 'suami.no_telp'),
                    ]);
                }

                // 7) Anak (dinamis)
                foreach ($validated['anak'] ?? [] as $row) {
                    if (!filled($row['nama'] ?? null) && !filled($row['tanggal_lahir'] ?? null)) {
                        continue;
                    }
                    Anak::create([
                        'ibu_id'            => $dataDiri->id,
                        'nama'              => $row['nama'] ?? null,
                        'tanggal_lahir'     => $row['tanggal_lahir'] ?? null,
                        'jenis_kelamin'     => $row['jenis_kelamin'] ?? null,
                        'no_jkn'            => $row['no_jkn'] ?? null,
                    ]);
                }

                // 8) Login & redirect
                event(new Registered($user));
                Auth::login($user);

                return redirect()->route('dashboard')->with('success', 'Akun berhasil dibuat.');
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->with('danger', 'Terjadi kesalahan di server. Silakan coba lagi.');
        }
    }
}
