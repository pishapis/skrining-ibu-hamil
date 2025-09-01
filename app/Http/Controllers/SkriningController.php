<?php

namespace App\Http\Controllers;

use App\Models\AnswerDass;
use Illuminate\Http\Request;
use App\Models\SkriningEpds;
use App\Models\AnswerEpds;
use App\Models\DataDiri;
use App\Models\HasilEpds;
use App\Models\SkriningDass;
use App\Models\UsiaHamil;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SkriningController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function index()
    {
        $title = "Skrining EPDS";
        $answer_epds = AnswerEpds::with(relations: ['epds'])->get();
        $answer_dass = AnswerDass::all();
        $skrining_dass = SkriningDass::all();

        $usia_hamil = null;
        try {
            $userId = Auth::id();
            $dataDiri = DataDiri::where('user_id', $userId)->first();

            if ($dataDiri) {
                $riwayat = UsiaHamil::where('ibu_id', $dataDiri->id)
                    ->whereNotNull('hpht')
                    ->whereNotNull('hpl')
                    ->latest('created_at')
                    ->first();

                if ($riwayat) {
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
        } catch (\Throwable $e) {
            Log::warning('Load usia_hamil gagal: ' . $e->getMessage());
        }
        return view('pages.skrining.index', compact('title', 'answer_epds', 'answer_dass', 'usia_hamil', 'skrining_dass'));
    }

    public function startEpds(Request $request)
    {
        try {
            $id = Auth::id();
            $data_diri = DataDiri::where('user_id', $id)->first();

            if (!$data_diri) {
                return response()->json(['ack' => 'bad', 'message' => 'Data diri pengguna tidak ditemukan', 'data' => null], 200);
            }

            // cari usia hamil (HPHT sudah pernah diinput)
            $riwayat = UsiaHamil::where('ibu_id', $data_diri->id)
                ->whereNotNull('hpht')->whereNotNull('hpl')->first();

            if (!$riwayat) {
                return response()->json([
                    'ack' => 'need_hpht',
                    'message' => 'HPHT belum diisi. Silakan isi HPHT terlebih dahulu.',
                    'data' => null
                ], 200);
            }

            // hitung trimester saat ini
            $usiaMgg   = hitungUsiaKehamilanMinggu($riwayat->hpht);
            $trimester = tentukanTrimester($usiaMgg); // 'trimester_1'|'trimester_2'|'trimester_3'|'pasca_hamil'

            // blokir jika sudah selesai di trimester ini
            $sudahSubmit = HasilEpds::where('ibu_id', $data_diri->id)
                ->where('trimester', $trimester)
                ->where('status', 'submitted')
                ->exists();

            if ($sudahSubmit) {
                return response()->json([
                    'ack' => 'bad',
                    'message' => 'Skrining EPDS untuk trimester ini sudah diselesaikan.',
                    'data' => null
                ], 200);
            }

            // cek session draft yang bisa di-resume (di luar transaksi OK)
            $draft = HasilEpds::where('ibu_id', $data_diri->id)
                ->where('trimester', $trimester)
                ->where('status', 'draft')
                ->orderByDesc('started_at')
                ->first();

            if ($draft) {
                $answered = HasilEpds::where('session_token', $draft->session_token)
                    ->whereNotNull('epds_id')
                    ->count();
                $total = SkriningEpds::count();

                return response()->json([
                    'ack' => 'ok',
                    'message' => 'Lanjutkan skrining yang tertunda.',
                    'data' => [
                        'session_token' => $draft->session_token,
                        'trimester' => $trimester,
                        'usia_hamil_id' => $riwayat->id,
                        'answered' => $answered,
                        'total' => $total
                    ]
                ]);
            }

            // === Atomic: buat session baru + stamp trimester ===
            $payload = DB::transaction(function () use ($data_diri, $riwayat, $trimester) {
                $now = now();

                // lock baris usia_hamil yang akan kita update
                $riwayatLock = UsiaHamil::lockForUpdate()->find($riwayat->id);

                // buat session header (seed row)
                $session = HasilEpds::create([
                    'ibu_id'         => $data_diri->id,
                    'usia_hamil_id'  => $riwayatLock->id,
                    'status'         => 'draft',
                    'session_token'  => (string) Str::uuid(),
                    'trimester'      => $trimester,
                    'started_at'     => $now,
                    'screening_date' => $now,
                ]);

                // stamp kolom trimester (kalau kosong)
                if ($trimester === 'trimester_1' && !$riwayatLock->trimester_1) {
                    $riwayatLock->trimester_1 = $now;
                }
                if ($trimester === 'trimester_2' && !$riwayatLock->trimester_2) {
                    $riwayatLock->trimester_2 = $now;
                }
                if ($trimester === 'trimester_3' && !$riwayatLock->trimester_3) {
                    $riwayatLock->trimester_3 = $now;
                }
                if ($trimester === 'pasca_hamil' && !$riwayatLock->pasca_hamil) {
                    $riwayatLock->pasca_hamil = $now;
                }
                $riwayatLock->save();

                $total = SkriningEpds::count();

                return [
                    'ack' => 'ok',
                    'message' => 'Session skrining dimulai.',
                    'data' => [
                        'session_token'  => $session->session_token,
                        'trimester'      => $trimester,
                        'usia_hamil_id'  => $riwayatLock->id,
                        'answered'       => 0,
                        'total'          => $total,
                    ]
                ];
            });

            return response()->json($payload);
        } catch (\Exception $e) {
            Log::error("startEpds error: " . $e->getMessage());
            return response()->json(['ack' => 'bad', 'message' => 'Gagal memulai skrining', 'data' => null], 200);
        }
    }


    public function saveEpdsAnswer(Request $request)
    {
        $request->validate([
            'session_token'   => 'required|uuid',
            'epds_id'         => 'required|exists:skrining_epds,id',
            'answers_epds_id' => 'required|exists:answers_epds,id',
        ]);

        try {
            $result = DB::transaction(function () use ($request) {
                // kunci seed agar tak race dengan submit
                $seed = HasilEpds::where('session_token', $request->session_token)
                    ->lockForUpdate()
                    ->first();

                if (!$seed || $seed->status !== 'draft') {
                    return ['ack' => 'bad', 'message' => 'Session tidak ditemukan / sudah ditutup', 'data' => null];
                }

                HasilEpds::updateOrCreate(
                    ['session_token' => $request->session_token, 'epds_id' => $request->epds_id],
                    [
                        'ibu_id'          => $seed->ibu_id,
                        'usia_hamil_id'   => $seed->usia_hamil_id,
                        'status'          => 'draft',
                        'trimester'       => $seed->trimester,
                        'answers_epds_id' => $request->answers_epds_id,
                        'screening_date'  => now(),
                    ]
                );

                $answered = HasilEpds::where('session_token', $request->session_token)
                    ->whereNotNull('epds_id')->count();
                $total    = SkriningEpds::count();

                return [
                    'ack' => 'ok',
                    'message' => 'Jawaban disimpan.',
                    'data' => ['answered' => $answered, 'total' => $total]
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("saveEpdsAnswer error: " . $e->getMessage());
            return response()->json(['ack' => 'bad', 'message' => 'Gagal menyimpan jawaban', 'data' => null], 200);
        }
    }


    public function submitEpds(Request $request)
    {
        $request->validate([
            'session_token' => 'required|uuid',
        ]);

        try {
            $result = DB::transaction(function () use ($request) {
                // kunci semua baris session ini
                $seed = HasilEpds::where('session_token', $request->session_token)
                    ->lockForUpdate()
                    ->first();

                if (!$seed) {
                    return ['ack' => 'bad', 'message' => 'Session tidak ditemukan', 'data' => null];
                }
                if ($seed->status === 'submitted') {
                    return ['ack' => 'bad', 'message' => 'Session sudah disubmit', 'data' => null];
                }

                $rows = HasilEpds::where('session_token', $request->session_token)
                    ->whereNotNull('epds_id')
                    ->with('answersEpds')
                    ->lockForUpdate()
                    ->get();

                if ($rows->isEmpty()) {
                    return ['ack' => 'bad', 'message' => 'Belum ada jawaban yang disimpan', 'data' => null];
                }

                $totalScore = $rows->sum(fn($r) => $r->answersEpds->score ?? 0);

                $q10Row = $rows->firstWhere('epds_id', 10); // sesuaikan kalau ID Q10 berbeda
                $q10Label = mb_strtolower($q10Row->answersEpds->jawaban ?? '');
                $q10AgakSering = str_contains($q10Label, 'agak') && str_contains($q10Label, 'sering');

                $isRisk = ($totalScore >= 13) || $q10AgakSering;
                $riskTitle = $isRisk ? 'Terindikasi Depresi' : 'Tidak menunjukkan gejala signifikan';

                $advice = $isRisk
                    ? 'Lakukan pemeriksaan kesehatan jiwa untuk menegakkan diagnosis dan tata laksana sesuai kompetensi tenaga medis/tenaga kesehatan puskesmas (dokter, psikolog klinis, perawat).'
                    : 'Edukasi kesehatan jiwa dan/atau lakukan skrining ulang pada kunjungan ANC berikutnya (oleh bidan, perawat, dokter, psikolog klinis).';

                HasilEpds::where('session_token', $request->session_token)->update([
                    'status'       => 'submitted',
                    'submitted_at' => now(),
                    'total_score'  => $totalScore,
                ]);

                return [
                    'ack' => 'ok',
                    'message' => 'Skrining tersubmit.',
                    'data' => [
                        'total_score'      => $totalScore,
                        'answered'         => $rows->count(),
                        'trimester'        => $seed->trimester,
                        'q10_agak_sering'  => $q10AgakSering,
                        'is_risk'          => $isRisk,
                        'risk_title'       => $riskTitle,
                        'advice'           => $advice,
                    ]
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("submitEpds error: " . $e->getMessage());
            return response()->json(['ack' => 'bad', 'message' => 'Gagal submit skrining', 'data' => null], 200);
        }
    }

    public function cancelEpds(Request $request)
    {
        $request->validate([
            'session_token' => 'required|uuid',
        ]);

        try {
            $userId = Auth::id();
            $dataDiri = DataDiri::where('user_id', $userId)->first();

            if (!$dataDiri) {
                return response()->json(['ack' => 'bad', 'message' => 'Data diri tidak ditemukan'], 200);
            }

            // Ambil salah satu baris session untuk cek kepemilikan & status
            $seed = HasilEpds::where('session_token', $request->session_token)->first();

            // Kalau sudah tidak ada (mungkin sudah dibersihkan), anggap OK supaya UI bisa lanjut
            if (!$seed) {
                return response()->json(['ack' => 'ok', 'message' => 'Session sudah tidak ada (mungkin sudah dibersihkan).'], 200);
            }

            if ((int) $seed->ibu_id !== (int) $dataDiri->id) {
                return response()->json(['ack' => 'bad', 'message' => 'Tidak berwenang membatalkan session ini.'], 200);
            }

            // Jangan hapus kalau sudah submitted
            $sudahSubmit = HasilEpds::where('session_token', $request->session_token)
                ->where('status', 'submitted')
                ->exists();

            if ($sudahSubmit) {
                return response()->json(['ack' => 'bad', 'message' => 'Session sudah disubmit dan tidak bisa dibatalkan.'], 200);
            }

            // Hapus atomic semua baris draft dengan session_token tsb
            DB::transaction(function () use ($request) {
                HasilEpds::where('session_token', $request->session_token)
                    ->lockForUpdate()
                    ->delete();
            });

            return response()->json(['ack' => 'ok', 'message' => 'Draft skrining dibatalkan.'], 200);
        } catch (\Exception $e) {
            Log::error('cancelEpds error: ' . $e->getMessage());
            return response()->json(['ack' => 'bad', 'message' => 'Gagal membatalkan draft skrining.'], 200);
        }
    }

    public function first_create_usia_hamil(Request $request)
    {
        try {
            $validated = $request->validate([
                'hpht' => 'required|date',
            ]);

            $id = Auth::user()->id;
            $data_diri = DataDiri::where('user_id', $id)->first();

            if (!$data_diri) {
                return response()->json([
                    'ack' => 'bad',
                    'message' => 'Data diri pengguna tidak ditemukan.',
                    'data' => null
                ], 200);
            }

            $result = DB::transaction(function () use ($data_diri, $validated) {
                $now = now();

                $riwayat = UsiaHamil::create([
                    'ibu_id'                     => $data_diri->id,
                    'perkiraan_usia_kehamilan'   => hitungUsiaKehamilanMinggu($validated['hpht']),
                    'hpht'                       => $validated['hpht'],
                    'hpl'                        => hitungHPL($validated['hpht']),
                    'trimester_1'                => $now,
                    'keterangan'                 => hitungUsiaKehamilanString($validated['hpht']),
                ]);

                return [
                    'ack'     => 'ok',
                    'message' => 'Data berhasil disimpan.',
                    'data'    => $riwayat
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("Error creating pregnancy history: " . $e->getMessage());
            return response()->json([
                'ack' => 'bad',
                'message' => 'Terjadi kesalahan saat menyimpan data.',
                'data' => null
            ], 200);
        }
    }
}
