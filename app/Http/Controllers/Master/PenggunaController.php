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
        $dataIbu = DataDiri::with(['user', 'kec', 'kel', 'prov', 'faskes', 'puskesmas', 'anak', 'suami'])
            ->paginate(10, ['*'], 'page_ibu');

        $data_puskesmas = DataDiri::with(['user', 'kec', 'kel', 'prov', 'puskesmas'])
            ->whereHas('user', fn($q) => $q->where('role_id', 2))
            ->paginate(10, ['*'], 'page_puskesmas');

        $provinsis      = DataDiri::optionsProvinsi();
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

    public function createIbu(Request $request)
    {
        $validated = $request->validate([
            // ===== STEP 1: Akun
            'username'  => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')],
            'email'     => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->letters()->numbers()],
            'password_confirmation' => ['required'],

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
        } catch (\Throwable $e) {
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
                'name'              => ['required', 'string', 'max:255'],
                'tempat_lahir'      => ['nullable', 'string', 'max:255'],
                'tanggal_lahir'     => ['nullable', 'date'],
                'pendidikan'        => ['nullable', 'in:sd,smp,sma,d3,s1,s2,s3,lainnya'],
                'pekerjaan'         => ['nullable', 'string', 'max:64'],
                'agama'             => ['nullable', 'in:islam,protestan,katolik,hindu,buddha,konghucu,lainnya'],
                'gol_darah'         => ['nullable', 'in:a,b,ab,o'],
                'prov_id'           => ['nullable', 'string', 'max:16', Rule::exists('indonesia_provinces', 'code')],
                'kota_id'           => ['nullable', 'string', 'max:16', Rule::exists('indonesia_cities', 'code')],
                'kec_id'            => ['nullable', 'string', 'max:16', Rule::exists('indonesia_districts', 'code')],
                'kelurahan_id'      => ['nullable', 'string', 'max:16', Rule::exists('indonesia_villages', 'code')],
                'alamat_rumah'      => ['nullable', 'string', 'max:1000'],
                'no_telp'           => ['nullable', 'string', 'max:32'],
                'puskesmas_id'      => ['nullable', Rule::exists('puskesmas', 'id')],
                'faskes_rujukan_id' => ['nullable', Rule::exists('fasilitas_kesehatan_rujukan', 'id')],
                'no_jkn'            => ['nullable', 'string', 'max:64'],
            ]);
            $id = $request->input('id');
            $dataDiri = DataDiri::findOrFail($id);

            // 2) Simpan ke tabel data_diri
            $dataDiri->update([
                'nama'                => $validated['name'],
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
            ]);

            $user = User::findOrFail($request->user_id);
            $user->update([
                'name' => $validated['name']
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
            'username'  => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')],
            'email'     => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->letters()->numbers()],
            'password_confirmation' => ['required'],

            // ===== STEP 2: Data Puskesmas (biodata)
            'name'              => ['required', 'string', 'max:100'],
            'nik'               => ['required', 'digits:16', Rule::unique('data_diri', 'nik')], // sesuaikan table
            'prov_id'           => ['required', 'string'],
            'kota_id'           => ['required', 'string'],
            'kec_id'            => ['required', 'string'],
            'kelurahan_id'      => ['required', 'string'],

            'alamat_rumah'      => ['required', 'string', 'max:255'],
            'no_telp'           => ['nullable', 'string', 'max:20'],
            'puskesmas_id'     => ['nullable', 'integer', 'exists:puskesmas,id'],
            'jabatan_id'     => ['nullable', 'integer', 'exists:puskesmas,id'],
        ]);

        try {
            $validated['faskes_rujukan_id'] = blank($validated['faskes_rujukan_id'] ?? null) ? null : (int)$validated['faskes_rujukan_id'];
            return DB::transaction(function () use ($request, $validated) {
                // 1) User
                $user = User::create([
                    'role_id'    => 2, //puskesmas
                    'name'       => $validated['name'],
                    'jabatan_id' => $validated['jabatan_id'],
                    'username'   => $validated['username'],
                    'email'      => $validated['email'],
                    'password'   => Hash::make($validated['password']),
                ]);

                // 2) Biodata Puskesmas (DataDiri)
                DataDiri::create([
                    'user_id'           => $user->id,
                    'nama'              => $validated['name'],
                    'nik'               => $validated['nik'],

                    'kode_prov'         => $validated['prov_id'],
                    'kode_kab'         => $validated['kota_id'],
                    'kode_kec'          => $validated['kec_id'],
                    'kode_des'    => $validated['kelurahan_id'],

                    'alamat_rumah'      => $validated['alamat_rumah'],
                    'no_telp'           => $validated['no_telp'] ?? null,
                    'no_jkn'            => $validated['no_jkn'] ?? null,

                    'puskesmas_id'     => $validated['puskesmas_id'] ?? null,
                ]);

                return back()->with('success', 'Data puskesmas berhasil dibuat.')
                    ->with('tab', 'puskesmas');
            });
        } catch (\Throwable $e) {
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
            'name'         => ['required', 'string', 'max:255'],
            'prov_id'           => ['nullable', 'string', 'max:16', Rule::exists('indonesia_provinces', 'code')],
            'kota_id'           => ['nullable', 'string', 'max:16', Rule::exists('indonesia_cities', 'code')],
            'kec_id'            => ['nullable', 'string', 'max:16', Rule::exists('indonesia_districts', 'code')],
            'kelurahan_id'      => ['nullable', 'string', 'max:16', Rule::exists('indonesia_villages', 'code')],
            'alamat_rumah' => ['nullable', 'string', 'max:1000'],
            'no_telp'      => ['nullable', 'string', 'max:32'],
            'puskesmas_id' => ['nullable', Rule::exists('puskesmas', 'id')],
            'jabatan_id'   => ['required', Rule::exists('jabatan', 'id')],
        ]);

        try {
            $id = $request->input('id');
            $dataDiri = DataDiri::findOrFail($id);
            $dataDiri->update([
                'nama'         => $validated['name'],
                'kode_prov'    => $validated['prov_id'] ?? null,
                'kode_kab'     => $validated['kota_id'] ?? null,
                'kode_kec'     => $validated['kec_id'] ?? null,
                'kode_des'     => $validated['kelurahan_id'] ?? null,
                'alamat_rumah' => $validated['alamat_rumah'] ?? null,
                'no_telp'      => $validated['no_telp'] ?? null,
                'puskesmas_id' => $validated['puskesmas_id'] ?? null,
            ]);

            $user = User::findOrFail($request->user_id);
            $user->update([
                'name' => $validated['name']
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
