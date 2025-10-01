<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Models\RiwayatKesehatan;
use App\Models\FasilitasKesehatan;
use App\Models\Puskesmas;
use App\Models\DataDiri;
use App\Models\Jabatan;
use App\Models\Anak;
use App\Models\Suami;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Log;

class PenggunaController extends Controller
{
    /**
     * Display the registration view.
     */

    public function index()
    {
        $title = "Manajemen Pengguna";
        $user = Auth::user();
        $role = $user->role_id;

        // Jika Superadmin, tampilkan semua data
        // Jika Admin Clinician, filter berdasarkan puskesmas_id
        $dataIbuQuery = DataDiri::whereNotNull('faskes_rujukan_id')
            ->with(['user', 'kec', 'kel', 'prov', 'faskes', 'puskesmas', 'anak', 'suami']);

        // Jika Admin Clinician, filter berdasarkan puskesmas_id
        if ($role == 2) {
            $dataIbuQuery->where('puskesmas_id', $user->puskesmas_id);
        }

        // Query untuk data ibu
        $dataIbu = $dataIbuQuery->paginate(10, ['*'], 'page_ibu');

        // Query untuk data puskesmas
        $data_puskesmasQuery = DataDiri::with(['user', 'kec', 'kel', 'prov', 'puskesmas'])
            ->whereHas('user', fn($q) => $q->where('role_id', 2));

        // Jika Admin Clinician, filter berdasarkan puskesmas_id
        if ($role == 2) {
            $data_puskesmasQuery->where('puskesmas_id', $user->puskesmas_id);
        }

        // Query untuk data puskesmas
        $data_puskesmas = $data_puskesmasQuery->paginate(10, ['*'], 'page_puskesmas');

        // Data lainnya
        $provinsis = DataDiri::optionsProvinsi();
        $kota = collect(); // biarkan kosong, akan diisi via AJAX onChange
        $kec = collect();
        $desa = collect();
        
        $puskesmas = Puskesmas::orderBy('nama')->get();
        $rujukan = FasilitasKesehatan::orderBy('nama')->get();
        $daftarJabatan = Jabatan::orderBy('nama')->get();
        $faskes = FasilitasKesehatan::orderBy('nama')->get();

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
            'faskes' => $faskes,
        ]);
    }


    public function createIbu(Request $request)
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

            'puskesmas_id'     => ['nullable', 'integer', 'exists:puskesmas,id'],
            'faskes_rujukan_id' => ['nullable', 'integer', 'exists:fasilitas_kesehatan_rujukan,id'],

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
                    'role_id'   => 1, //user
                    'puskesmas_id'  => $validated['puskesmas_id'],
                    'name'      => $validated['name'],
                    'username'  => $validated['username'],
                    'email'     => $validated['email'],
                    'password'  => Hash::make($validated['password']),
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

                return back()->with('success', 'Data ibu berhasil dibuat.')
                    ->with('tab', 'ibu');
            });
        } catch (\Exception $e) {
            Log::error('Terjadi kesalahan di server. Silakan coba lagi', [
                'message'      => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'payload'      => $request->all(),
            ]);
            return back()
                ->with('error', ' Terjadi kesalahan, silakan coba lagi.')
                ->withInput()
                ->with('tab', 'ibu');
        }
    }

    public function updateIbu(Request $request)
    {
        try {
            // 1) Validasi (bila gagal, langsung ke catch ValidationException)
            $validated = $request->validate([
                'name_edit'              => ['required', 'string', 'max:255'],
                'tempat_lahir_edit'      => ['nullable', 'string', 'max:255'],
                'tanggal_lahir_edit'     => ['nullable', 'date'],
                'is_luar_wilayah_edit'   => ['nullable', 'boolean'],
                'pendidikan_edit'        => ['nullable', 'in:sd,smp,sma,d3,s1,s2,s3,lainnya'],
                'pekerjaan_edit'         => ['nullable', 'string', 'max:64'],
                'agama_edit'             => ['nullable', 'in:islam,protestan,katolik,hindu,buddha,konghucu,lainnya'],
                'gol_darah_edit'         => ['nullable', 'in:a,b,ab,o'],
                'prov_id_edit'           => ['nullable', 'string', 'max:16', Rule::exists('indonesia_provinces', 'code')],
                'kota_id_edit'           => ['nullable', 'string', 'max:16', Rule::exists('indonesia_cities', 'code')],
                'kec_id_edit'            => ['nullable', 'string', 'max:16', Rule::exists('indonesia_districts', 'code')],
                'kelurahan_id_edit'      => ['nullable', 'string', 'max:16', Rule::exists('indonesia_villages', 'code')],
                'alamat_rumah_edit'      => ['nullable', 'string', 'max:1000'],
                'no_telp_edit'           => ['nullable', 'string', 'max:32'],
                'puskesmas_id_edit'      => ['nullable', Rule::exists('puskesmas', 'id')],
                'faskes_rujukan_id_edit' => ['nullable', Rule::exists('fasilitas_kesehatan_rujukan', 'id')],
                'no_jkn_edit'            => ['nullable', 'string', 'max:64'],
            ]);
            $id = $request->input('id');
            $dataDiri = DataDiri::findOrFail($id);

            // 2) Simpan ke tabel data_diri
            $dataDiri->update([
                'nama'                => $validated['name_edit'],
                'tempat_lahir'        => $validated['tempat_lahir_edit'] ?? null,
                'tanggal_lahir'       => $validated['tanggal_lahir_edit'] ?? null,
                'pendidikan_terakhir' => $validated['pendidikan_edit'] ?? null,
                'pekerjaan'           => $validated['pekerjaan_edit'] ?? null,
                'agama'               => $validated['agama_edit'] ?? null,
                'golongan_darah'      => $validated['gol_darah_edit'] ?? null,
                'is_luar_wilayah'     => $validated['is_luar_wilayah'] ?? false,
                'kode_prov'           => $validated['prov_id_edit'] ?? null,
                'kode_kab'            => $validated['kota_id_edit'] ?? null,
                'kode_kec'            => $validated['kec_id_edit'] ?? null,
                'kode_des'            => $validated['kelurahan_id_edit'] ?? null,
                'alamat_rumah'        => $validated['alamat_rumah_edit'] ?? null,
                'no_telp'             => $validated['no_telp_edit'] ?? null,
                'puskesmas_id'        => $validated['puskesmas_id_edit'] ?? null,
                'faskes_rujukan_id'   => $validated['faskes_rujukan_id_edit'] ?? null,
                'no_jkn'              => $validated['no_jkn_edit'] ?? null,
            ]);

            $user = User::findOrFail($request->user_id);
            $user->update([
                'name' => $validated['name_edit']
            ]);

            // 4) Respons sukses (JSON vs Redirect)
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Data ibu berhasil diperbarui.',
                    'data'    => $dataDiri->fresh(['user']),
                ], 200);
            }

            return back()
                ->with('success', 'Data ibu berhasil diperbarui.')
                ->with('tab', 'ibu');
        } catch (ValidationException $e) {

            Log::error('Update ibu gagal', [
                'message'      => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'payload'      => $request->all(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Validasi gagal.',
                    'errors'  => $e->errors(),
                ], 422);
            }

            return back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('tab', 'ibu');
        } catch (\Exception $e) {
            Log::error('Terjadi kesalahan di server. Silakan coba lagi', [
                'message'      => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'payload'      => $request->all(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Terjadi kesalahan di server. Silakan coba lagi.',
                ], 500);
            }

            return back()
                ->with('error', ' Terjadi kesalahan, silakan coba lagi.')
                ->withInput()
                ->with('tab', 'ibu');
        }
    }

    public function createPuskesmas(Request $request)
    {

        $validated = $request->validate([
            // ===== STEP 1: Akun
            'username_pus_create'  => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')],
            'email_pus_create'     => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'password_pus_create' => ['nullable', Password::min(8)->mixedCase()->letters()->numbers()],
            'password_confirmation_pus_create' => ['nullable'],

            // ===== STEP 2: Data Puskesmas (biodata)
            'name_pus_create'              => ['required', 'string', 'max:100'],
            'nik_pus_create'               => ['required', 'digits:16', Rule::unique('data_diri', 'nik')], // sesuaikan table
            'prov_id_pus_create'           => ['required', 'string'],
            'kota_id_pus_create'           => ['required', 'string'],
            'kec_id_pus_create'            => ['required', 'string'],
            'kelurahan_id_pus_create'      => ['required', 'string'],

            'alamat_rumah_pus_create'      => ['required', 'string', 'max:255'],
            'no_telp_pus_create'           => ['nullable', 'string', 'max:20'],
            'puskesmas_id_pus_create'     => ['nullable', 'integer', 'exists:puskesmas,id'],
            'jabatan_id_pus_create'     => ['nullable', 'integer', 'exists:puskesmas,id'],
        ]);
        $validated['password_pus_create'] = trim($validated['password_pus_create']);
        $validated['password_confirmation_pus_create'] = trim($validated['password_confirmation_pus_create']);

        if ($request->input('password_pus_create') !== $request->input('password_confirmation_pus_create')) {
            return back()->withErrors([
                'password_confirmation_pus_create' => 'Password dan konfirmasi password tidak cocok.'
            ]);
        }

        try {
            $validated['jabatan_id_pus_create'] = blank($validated['jabatan_id_pus_create'] ?? null) ? null : (int)$validated['jabatan_id_pus_create'];
            return DB::transaction(function () use ($request, $validated) {
                // 1) User
                $user = User::create([
                    'role_id'    => 2, //puskesmas
                    'puskesmas_id'  => $validated['puskesmas_id_pus_create'],
                    'name'       => $validated['name_pus_create'],
                    'jabatan_id' => $validated['jabatan_id_pus_create'],
                    'username'   => $validated['username_pus_create'],
                    'email'      => $validated['email_pus_create'],
                    'password'   => Hash::make($validated['password_pus_create']),
                ]);

                // 2) Biodata Puskesmas (DataDiri)
                DataDiri::create([
                    'user_id'           => $user->id,
                    'nama'              => $validated['name_pus_create'],
                    'nik'               => $validated['nik_pus_create'],

                    'kode_prov'         => $validated['prov_id_pus_create'],
                    'kode_kab'         => $validated['kota_id_pus_create'],
                    'kode_kec'          => $validated['kec_id_pus_create'],
                    'kode_des'    => $validated['kelurahan_id_pus_create'],

                    'alamat_rumah'      => $validated['alamat_rumah_pus_create'],
                    'no_telp'           => $validated['no_telp_pus_create'] ?? null,
                    'no_jkn'            => $validated['no_jkn_pus_create'] ?? null,

                    'puskesmas_id'     => $validated['puskesmas_id_pus_create'] ?? null,
                ]);

                return back()->with('success', 'Data puskesmas berhasil dibuat.')
                    ->with('tab', 'puskesmas');
            });
        } catch (\Exception $e) {
            Log::error('Terjadi kesalahan di server. Silakan coba lagi', [
                'message'      => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'payload'      => $request->all(),
            ]);
            return back()
                ->with('error', ' Terjadi kesalahan, silakan coba lagi.')
                ->withInput()
                ->with('tab', 'puskesmas');
        }
    }
    // ================== UPDATE PUSKESMAS (STAF) ==================
    public function updatePuskesmas(Request $request, DataDiri $dataDiri)
    {
        $validated = $request->validate([
            'name_edit'         => ['required', 'string', 'max:255'],
            'prov_id_edit_pus'           => ['nullable', 'string', 'max:16', Rule::exists('indonesia_provinces', 'code')],
            'kota_id_edit_pus'           => ['nullable', 'string', 'max:16', Rule::exists('indonesia_cities', 'code')],
            'kec_id_edit_pus'            => ['nullable', 'string', 'max:16', Rule::exists('indonesia_districts', 'code')],
            'kelurahan_id_edit_pus'      => ['nullable', 'string', 'max:16', Rule::exists('indonesia_villages', 'code')],
            'alamat_rumah_edit' => ['nullable', 'string', 'max:1000'],
            'no_telp_edit'      => ['nullable', 'string', 'max:32'],
            'puskesmas_id_edit' => ['nullable', Rule::exists('puskesmas', 'id')],
            'jabatan_id_edit'   => ['required', Rule::exists('jabatan', 'id')],
        ]);

        try {
            $id = $request->input('id');
            $dataDiri = DataDiri::findOrFail($id);
            $dataDiri->update([
                'nama'         => $validated['name_edit'],
                'kode_prov'    => $validated['prov_id_edit_pus'] ?? null,
                'kode_kab'     => $validated['kota_id_edit_pus'] ?? null,
                'kode_kec'     => $validated['kec_id_edit_pus'] ?? null,
                'kode_des'     => $validated['kelurahan_id_edit_pus'] ?? null,
                'alamat_rumah' => $validated['alamat_rumah_edit'] ?? null,
                'no_telp'      => $validated['no_telp_edit'] ?? null,
                'puskesmas_id' => $validated['puskesmas_id_edit'] ?? null,
            ]);

            $user = User::findOrFail($request->user_id);
            $user->update([
                'name' => $validated['name_edit']
            ]);

            return back()->with('success', 'Data staf puskesmas berhasil diperbarui.')
                ->with('tab', 'puskesmas');
        } catch (\Exception $e) {
            Log::error('Terjadi kesalahan di server. Silakan coba lagi', [
                'message'      => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'payload'      => $request->all(),
            ]);

            return back()
                ->with('error', ' Terjadi kesalahan, silakan coba lagi.')
                ->withInput()
                ->with('tab', 'puskesmas');
        }
    }
}
