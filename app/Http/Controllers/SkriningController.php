<?php

namespace App\Http\Controllers;

use App\Models\AnswerDass;
use Illuminate\Http\Request;
use App\Models\SkriningEpds;
use App\Models\AnswerEpds;
use App\Models\DataDiri;
use App\Models\HasilEpds;
use App\Models\HasilDass;
use App\Models\SkriningDass;
use App\Models\UsiaHamil;
use App\Models\RescreenToken; // <--- NEW
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

    /* ============================================================
     * EPDS
     * ============================================================ */

    public function startEpds(Request $request)
    {
        try {
            $id = Auth::id();
            $data_diri = DataDiri::where('user_id', $id)->first();

            if (!$data_diri) {
                return response()->json(['ack' => 'bad', 'message' => 'Data diri pengguna tidak ditemukan', 'data' => null], 200);
            }

            // wajib punya HPHT/HPL agar tahu trimester
            $riwayat = UsiaHamil::where('ibu_id', $data_diri->id)
                ->whereNotNull('hpht')->whereNotNull('hpl')->first();

            if (!$riwayat) {
                return response()->json([
                    'ack' => 'need_hpht',
                    'message' => 'HPHT belum diisi. Silakan isi HPHT terlebih dahulu.',
                    'data' => null
                ], 200);
            }

            // hitung trimester
            $usiaMgg   = hitungUsiaKehamilanMinggu($riwayat->hpht);
            $trimester = tentukanTrimester($usiaMgg); // 'trimester_1'|'trimester_2'|'trimester_3'|'pasca_hamil'

            // sudah pernah submit pada trimester ini?
            $submittedCount = HasilEpds::where('ibu_id', $data_diri->id)
                ->where('trimester', $trimester)
                ->where('status', 'submitted')
                ->count();

            // Jika sudah ada submitted, cek token aktif (bolehkan skrining ulang bila ada token & masih ada kuota)
            $activeToken = null;
            if ($submittedCount > 0) {
                $activeToken = RescreenToken::active()
                    ->where('ibu_id', $data_diri->id)
                    ->where('jenis', 'epds')
                    ->where('trimester', $trimester)
                    ->orderByDesc('created_at')
                    ->first();

                $usable = $activeToken && $activeToken->used_count < $activeToken->max_uses;
                if (!$usable) {
                    return response()->json([
                        'ack' => 'bad',
                        'message' => 'Skrining EPDS untuk trimester ini sudah diselesaikan. Hubungi petugas puskesmas bila perlu skrining ulang.',
                        'data' => null
                    ], 200);
                }
            }

            // cek draft yang bisa di-resume
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
                        'trimester'     => $trimester,
                        'usia_hamil_id' => $riwayat->id,
                        'answered'      => $answered,
                        'total'         => $total,
                        'batch_no'      => (int)($draft->batch_no ?? 1), // <--- NEW
                    ]
                ]);
            }

            // === Atomic: buat session baru + stamp trimester ===
            $payload = DB::transaction(function () use ($data_diri, $riwayat, $trimester, $submittedCount, $activeToken) {
                $now = now();

                $riwayatLock = UsiaHamil::lockForUpdate()->find($riwayat->id);

                $session = HasilEpds::create([
                    'ibu_id'           => $data_diri->id,
                    'usia_hamil_id'    => $riwayatLock->id,
                    'status'           => 'draft',
                    'session_token'    => (string) Str::uuid(),
                    'trimester'        => $trimester,
                    'started_at'       => $now,
                    'screening_date'   => $now,
                    'rescreen_token_id' => $activeToken?->id,           // <--- NEW
                    'batch_no'         => $submittedCount + 1,         // <--- NEW
                ]);

                // stamp kolom trimester (kalau kosong)
                if ($trimester === 'trimester_1' && !$riwayatLock->trimester_1) $riwayatLock->trimester_1 = $now;
                if ($trimester === 'trimester_2' && !$riwayatLock->trimester_2) $riwayatLock->trimester_2 = $now;
                if ($trimester === 'trimester_3' && !$riwayatLock->trimester_3) $riwayatLock->trimester_3 = $now;
                if ($trimester === 'pasca_hamil' && !$riwayatLock->pasca_hamil) $riwayatLock->pasca_hamil = $now;
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
                        'batch_no'       => (int)$session->batch_no,   // <--- NEW
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
                        'rescreen_token_id' => $seed->rescreen_token_id, // ikutkan di baris detail
                        'batch_no'        => $seed->batch_no,
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

                // Perhatikan: disini asumsi butir Q10 punya epds_id = 10.
                // Ideal: gunakan kolom 'urutan' untuk cari butir #10.
                $q10Row = $rows->firstWhere('epds_id', 10);
                $q10Label = mb_strtolower($q10Row->answersEpds->jawaban ?? '');
                $q10AgakSering = str_contains($q10Label, 'agak') && str_contains($q10Label, 'sering');

                $isRisk = ($totalScore >= 13) || $q10AgakSering;
                $riskTitle = $isRisk ? 'Terindikasi Depresi' : 'Tidak menunjukkan gejala signifikan';

                $advice = $isRisk
                    ? 'Lakukan pemeriksaan kesehatan jiwa untuk menegakkan diagnosis dan tata laksana sesuai kompetensi tenaga medis/tenaga kesehatan puskesmas (dokter, psikolog klinis, perawat).'
                    : 'Edukasi kesehatan jiwa dan/atau lakukan skrining ulang pada kunjungan ANC berikutnya (oleh bidan, perawat, dokter, psikolog klinis).';

                // Tutup semua baris di session ini
                HasilEpds::where('session_token', $request->session_token)->update([
                    'status'       => 'submitted',
                    'submitted_at' => now(),
                    'total_score'  => $totalScore,
                ]);

                // Jika sesi ini berasal dari token, increment pemakaian
                if ($seed->rescreen_token_id) {
                    $token = RescreenToken::lockForUpdate()->find($seed->rescreen_token_id);
                    if ($token && $token->status === 'active') {
                        $token->used_count++;
                        if ($token->used_count >= $token->max_uses) {
                            $token->status = 'used';
                        }
                        $token->save();
                    }
                }

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
                        'batch_no'         => (int)$seed->batch_no, // opsional untuk UI
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

            $seed = HasilEpds::where('session_token', $request->session_token)->first();

            if (!$seed) {
                return response()->json(['ack' => 'ok', 'message' => 'Session sudah tidak ada (mungkin sudah dibersihkan).'], 200);
            }

            if ((int) $seed->ibu_id !== (int) $dataDiri->id) {
                return response()->json(['ack' => 'bad', 'message' => 'Tidak berwenang membatalkan session ini.'], 200);
            }

            $sudahSubmit = HasilEpds::where('session_token', $request->session_token)
                ->where('status', 'submitted')
                ->exists();

            if ($sudahSubmit) {
                return response()->json(['ack' => 'bad', 'message' => 'Session sudah disubmit dan tidak bisa dibatalkan.'], 200);
            }

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

    /* ============================================================
     * DASS-21
     * ============================================================ */

    public function startDass(Request $request)
    {
        try {
            $id = Auth::id();
            $data_diri = DataDiri::where('user_id', $id)->first();
            if (!$data_diri) {
                return response()->json(['ack' => 'bad', 'message' => 'Data diri pengguna tidak ditemukan', 'data' => null], 200);
            }

            $riwayat = UsiaHamil::where('ibu_id', $data_diri->id)
                ->whereNotNull('hpht')->whereNotNull('hpl')->first();

            if (!$riwayat) {
                return response()->json([
                    'ack' => 'need_hpht',
                    'message' => 'HPHT belum diisi. Silakan isi HPHT terlebih dahulu.',
                    'data' => null
                ], 200);
            }

            $usiaMgg   = hitungUsiaKehamilanMinggu($riwayat->hpht);
            $trimester = tentukanTrimester($usiaMgg);

            // sudah pernah submit DASS di trimester ini?
            $submittedCount = HasilDass::where('ibu_id', $data_diri->id)
                ->where('trimester', $trimester)
                ->where('status', 'submitted')
                ->count();

            // jika sudah, cek token aktif
            $activeToken = null;
            if ($submittedCount > 0) {
                $activeToken = RescreenToken::active()
                    ->where('ibu_id', $data_diri->id)
                    ->where('jenis', 'dass')
                    ->where('trimester', $trimester)
                    ->orderByDesc('created_at')
                    ->first();

                $usable = $activeToken && $activeToken->used_count < $activeToken->max_uses;
                if (!$usable) {
                    return response()->json([
                        'ack' => 'bad',
                        'message' => 'Skrining DASS-21 untuk trimester ini sudah diselesaikan. Hubungi petugas puskesmas bila perlu skrining ulang.',
                        'data' => null
                    ]);
                }
            }

            // Lanjutkan draft kalau ada
            $draft = HasilDass::where('ibu_id', $data_diri->id)
                ->where('trimester', $trimester)
                ->where('status', 'draft')
                ->orderByDesc('started_at')
                ->first();

            if ($draft) {
                $answered = HasilDass::where('session_token', $draft->session_token)
                    ->whereNotNull('dass_id')->count();
                $total = SkriningDass::count();
                return response()->json([
                    'ack' => 'ok',
                    'message' => 'Lanjutkan skrining yang tertunda.',
                    'data' => [
                        'session_token' => $draft->session_token,
                        'trimester'     => $trimester,
                        'usia_hamil_id' => $riwayat->id,
                        'answered'      => $answered,
                        'total'         => $total,
                        'batch_no'      => (int)($draft->batch_no ?? 1), // <--- NEW
                    ]
                ]);
            }

            // Session baru
            $payload = DB::transaction(function () use ($data_diri, $riwayat, $trimester, $submittedCount, $activeToken) {
                $now = now();
                $riwayatLock = UsiaHamil::lockForUpdate()->find($riwayat->id);

                $session = HasilDass::create([
                    'ibu_id'            => $data_diri->id,
                    'usia_hamil_id'     => $riwayatLock->id,
                    'status'            => 'draft',
                    'session_token'     => (string) Str::uuid(),
                    'trimester'         => $trimester,
                    'started_at'        => $now,
                    'screening_date'    => $now,
                    'rescreen_token_id' => $activeToken?->id,          // <--- NEW
                    'batch_no'          => $submittedCount + 1,        // <--- NEW
                ]);

                if ($trimester === 'trimester_1' && !$riwayatLock->trimester_1) $riwayatLock->trimester_1 = $now;
                if ($trimester === 'trimester_2' && !$riwayatLock->trimester_2) $riwayatLock->trimester_2 = $now;
                if ($trimester === 'trimester_3' && !$riwayatLock->trimester_3) $riwayatLock->trimester_3 = $now;
                if ($trimester === 'pasca_hamil' && !$riwayatLock->pasca_hamil) $riwayatLock->pasca_hamil = $now;
                $riwayatLock->save();

                return [
                    'ack' => 'ok',
                    'message' => 'Session skrining dimulai.',
                    'data' => [
                        'session_token' => $session->session_token,
                        'trimester'     => $trimester,
                        'usia_hamil_id' => $riwayatLock->id,
                        'answered'      => 0,
                        'total'         => SkriningDass::count(),
                        'batch_no'      => (int)$session->batch_no,     // <--- NEW
                    ]
                ];
            });

            return response()->json($payload);
        } catch (\Exception $e) {
            Log::error("startDass error: " . $e->getMessage());
            return response()->json(['ack' => 'bad', 'message' => 'Gagal memulai skrining', 'data' => null], 200);
        }
    }

    /** POST /dass/answer */
    public function saveDassAnswer(Request $request)
    {
        $request->validate([
            'session_token'   => 'required|uuid',
            'dass_id'         => 'required|exists:skrining_dass,id',
            'answers_dass_id' => 'required|exists:answers_dass,id',
        ]);

        try {
            $result = DB::transaction(function () use ($request) {
                $seed = HasilDass::where('session_token', $request->session_token)
                    ->lockForUpdate()->first();

                if (!$seed || $seed->status !== 'draft') {
                    return ['ack' => 'bad', 'message' => 'Session tidak ditemukan / sudah ditutup', 'data' => null];
                }

                HasilDass::updateOrCreate(
                    ['session_token' => $request->session_token, 'dass_id' => $request->dass_id],
                    [
                        'ibu_id'          => $seed->ibu_id,
                        'usia_hamil_id'   => $seed->usia_hamil_id,
                        'status'          => 'draft',
                        'trimester'       => $seed->trimester,
                        'answers_dass_id' => $request->answers_dass_id,
                        'screening_date'  => now(),
                        'rescreen_token_id' => $seed->rescreen_token_id, // ikutkan
                        'batch_no'        => $seed->batch_no,
                    ]
                );

                $answered = HasilDass::where('session_token', $request->session_token)
                    ->whereNotNull('dass_id')->count();
                $total = SkriningDass::count();

                return ['ack' => 'ok', 'message' => 'Jawaban disimpan.', 'data' => ['answered' => $answered, 'total' => $total]];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("saveDassAnswer error: " . $e->getMessage());
            return response()->json(['ack' => 'bad', 'message' => 'Gagal menyimpan jawaban', 'data' => null], 200);
        }
    }

    /** POST /dass/submit */
    public function submitDass(Request $request)
    {
        $request->validate([
            'session_token' => 'required|uuid',
        ]);

        try {
            $result = DB::transaction(function () use ($request) {

                $seed = HasilDass::where('session_token', $request->session_token)
                    ->lockForUpdate()->first();

                if (!$seed) return ['ack' => 'bad', 'message' => 'Session tidak ditemukan', 'data' => null];
                if ($seed->status === 'submitted') {
                    return ['ack' => 'bad', 'message' => 'Session sudah disubmit', 'data' => null];
                }

                $rows = HasilDass::where('session_token', $request->session_token)
                    ->whereNotNull('dass_id')
                    ->with('answersDass')
                    ->lockForUpdate()
                    ->get();

                if ($rows->isEmpty()) {
                    return ['ack' => 'bad', 'message' => 'Belum ada jawaban yang disimpan', 'data' => null];
                }

                // ------- Skoring DASS-21 -------
                $grpDep = [3, 5, 10, 13, 16, 17, 21];
                $grpAnx = [2, 4, 7, 9, 15, 19, 20];
                $grpStr = [1, 6, 8, 11, 12, 14, 18];

                $scoreByNo = [];
                foreach ($rows as $r) {
                    $no = (int) $r->dass_id; // ideal: gunakan kolom 'urutan' jika ada
                    $scoreByNo[$no] = (int) ($r->answersDass->score ?? 0);
                }

                $sumDep = 0;
                foreach ($grpDep as $n) $sumDep += ($scoreByNo[$n] ?? 0);
                $sumAnx = 0;
                foreach ($grpAnx as $n) $sumAnx += ($scoreByNo[$n] ?? 0);
                $sumStr = 0;
                foreach ($grpStr as $n) $sumStr += ($scoreByNo[$n] ?? 0);

                // DASS-21 => kalikan 2
                $sumDep *= 2;
                $sumAnx *= 2;
                $sumStr *= 2;

                $sev = function (int $val, string $dim) {
                    $map = [
                        'depression' => [[9, 'Normal'], [13, 'Ringan'], [20, 'Sedang'], [27, 'Berat'], [PHP_INT_MAX, 'Sangat Berat']],
                        'anxiety'    => [[7, 'Normal'], [9, 'Ringan'], [14, 'Sedang'], [19, 'Berat'], [PHP_INT_MAX, 'Sangat Berat']],
                        'stress'     => [[14, 'Normal'], [18, 'Ringan'], [25, 'Sedang'], [33, 'Berat'], [PHP_INT_MAX, 'Sangat Berat']],
                    ][$dim];

                    foreach ($map as [$max, $label]) if ($val <= $max) return $label;
                    return '—';
                };

                $levels = [
                    'depression' => $sev($sumDep, 'depression'),
                    'anxiety'    => $sev($sumAnx, 'anxiety'),
                    'stress'     => $sev($sumStr, 'stress'),
                ];

                $advice = 'Hasil berada dalam rentang normal.';
                if ($levels['depression'] !== 'Normal' || $levels['anxiety'] !== 'Normal' || $levels['stress'] !== 'Normal') {
                    $advice = 'Pertimbangkan edukasi kesehatan jiwa, teknik relaksasi/napas, sleep hygiene, dan diskusi dengan tenaga kesehatan.';
                }

                $flags = [
                    'anxiety_alert'    => $sumAnx >= 12,
                    'depression_alert' => $sumDep >= 15,
                    'stress_alert'     => $sumStr >= 20,
                ];

                // Tutup session
                HasilDass::where('session_token', $request->session_token)->update([
                    'status'           => 'submitted',
                    'submitted_at'     => now(),
                    'total_depression' => $sumDep,
                    'total_anxiety'    => $sumAnx,
                    'total_stress'     => $sumStr,
                ]);

                // Jika sesi dari token, increment pemakaian
                if ($seed->rescreen_token_id) {
                    $token = RescreenToken::lockForUpdate()->find($seed->rescreen_token_id);
                    if ($token && $token->status === 'active') {
                        $token->used_count++;
                        if ($token->used_count >= $token->max_uses) {
                            $token->status = 'used';
                        }
                        $token->save();
                    }
                }

                return [
                    'ack' => 'ok',
                    'message' => 'Skrining tersubmit.',
                    'data' => [
                        'sum'     => ['depression' => $sumDep, 'anxiety' => $sumAnx, 'stress' => $sumStr],
                        'level'   => $levels,
                        'advice'  => $advice,
                        'flags'   => $flags,
                        'answered' => $rows->count(),
                        'trimester' => $seed->trimester,
                        'batch_no' => (int)$seed->batch_no, // opsional untuk UI
                    ]
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("submitDass error: " . $e->getMessage());
            return response()->json(['ack' => 'bad', 'message' => 'Gagal submit skrining', 'data' => null], 200);
        }
    }

    /** POST /dass/cancel */
    public function cancelDass(Request $request)
    {
        $request->validate(['session_token' => 'required|uuid']);

        try {
            $userId = Auth::id();
            $dataDiri = DataDiri::where('user_id', $userId)->first();
            if (!$dataDiri) return response()->json(['ack' => 'bad', 'message' => 'Data diri tidak ditemukan'], 200);

            $seed = HasilDass::where('session_token', $request->session_token)->first();
            if (!$seed) return response()->json(['ack' => 'ok', 'message' => 'Session sudah tidak ada (mungkin sudah dibersihkan).'], 200);
            if ((int)$seed->ibu_id !== (int)$dataDiri->id) {
                return response()->json(['ack' => 'bad', 'message' => 'Tidak berwenang membatalkan session ini.'], 200);
            }

            $sudahSubmit = HasilDass::where('session_token', $request->session_token)
                ->where('status', 'submitted')->exists();
            if ($sudahSubmit) {
                return response()->json(['ack' => 'bad', 'message' => 'Session sudah disubmit dan tidak bisa dibatalkan.'], 200);
            }

            DB::transaction(function () use ($request) {
                HasilDass::where('session_token', $request->session_token)
                    ->lockForUpdate()->delete();
            });

            return response()->json(['ack' => 'ok', 'message' => 'Draft skrining dibatalkan.'], 200);
        } catch (\Exception $e) {
            Log::error('cancelDass error: ' . $e->getMessage());
            return response()->json(['ack' => 'bad', 'message' => 'Gagal membatalkan draft skrining.'], 200);
        }
    }

    /* ============================================================
     * Usia Hamil - First Create
     * ============================================================ */

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
