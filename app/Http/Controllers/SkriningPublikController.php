<?php

namespace App\Http\Controllers;

use App\Models\DataDiri;
use App\Models\UsiaHamil;
use App\Models\HasilEpds;
use App\Models\HasilDass;
use App\Models\SkriningEpds;
use App\Models\SkriningDass;
use App\Models\RescreenToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SkriningPublikController extends Controller
{
    /* ============================================================
     * EPDS - Versi Publik (tanpa Auth)
     * ============================================================ */

    public function startEpdsPublik(Request $request)
    {
        $request->validate([
            'ibu_id' => 'required|exists:data_diri,id'
        ]);

        try {
            $data_diri = DataDiri::find($request->ibu_id);

            if (!$data_diri) {
                return response()->json([
                    'ack' => 'bad',
                    'message' => 'Data diri tidak ditemukan',
                    'data' => null
                ], 200);
            }

            // Cek HPHT/HPL
            $riwayat = UsiaHamil::where('ibu_id', $data_diri->id)
                ->whereNotNull('hpht')
                ->whereNotNull('hpl')
                ->latest('created_at')
                ->first();

            if (!$riwayat) {
                return response()->json([
                    'ack' => 'need_hpht',
                    'message' => 'HPHT belum diisi. Silakan isi HPHT terlebih dahulu.',
                    'data' => null
                ], 200);
            }

            // Hitung trimester
            $usiaMgg = hitungUsiaKehamilanMinggu($riwayat->hpht);
            $trimester = tentukanTrimester($usiaMgg);

            // Cek sudah pernah submit?
            $submittedCount = HasilEpds::where('ibu_id', $data_diri->id)
                ->where('trimester', $trimester)
                ->where('status', 'submitted')
                ->count();

            // Cek token aktif (untuk rescreen)
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

            // Cek draft yang bisa dilanjutkan
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
                        'batch_no'      => (int)($draft->batch_no ?? 1),
                    ]
                ]);
            }

            // Buat session baru
            $payload = DB::transaction(function () use ($data_diri, $riwayat, $trimester, $submittedCount, $activeToken) {
                $now = now();
                $riwayatLock = UsiaHamil::lockForUpdate()->find($riwayat->id);

                $session = HasilEpds::create([
                    'ibu_id'            => $data_diri->id,
                    'usia_hamil_id'     => $riwayatLock->id,
                    'status'            => 'draft',
                    'mode'              => 'kehamilan',
                    'periode'           => null,
                    'session_token'     => (string) Str::uuid(),
                    'trimester'         => $trimester,
                    'started_at'        => $now,
                    'screening_date'    => $now,
                    'rescreen_token_id' => $activeToken?->id,
                    'batch_no'          => $submittedCount + 1,
                ]);

                // Stamp trimester
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
                        'total'         => SkriningEpds::count(),
                        'batch_no'      => (int)$session->batch_no,
                    ]
                ];
            });

            return response()->json($payload);
        } catch (\Exception $e) {
            Log::error("startEpdsPublik error: " . $e->getMessage());
            return response()->json([
                'ack' => 'bad',
                'message' => 'Gagal memulai skrining',
                'data' => null
            ], 200);
        }
    }

    public function saveEpdsAnswerPublik(Request $request)
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
                    return [
                        'ack' => 'bad',
                        'message' => 'Session tidak ditemukan / sudah ditutup',
                        'data' => null
                    ];
                }

                HasilEpds::updateOrCreate(
                    ['session_token' => $request->session_token, 'epds_id' => $request->epds_id],
                    [
                        'ibu_id'            => $seed->ibu_id,
                        'usia_hamil_id'     => $seed->usia_hamil_id,
                        'status'            => 'draft',
                        'mode'              => 'kehamilan',
                        'periode'           => null,
                        'trimester'         => $seed->trimester,
                        'answers_epds_id'   => $request->answers_epds_id,
                        'screening_date'    => now(),
                        'rescreen_token_id' => $seed->rescreen_token_id,
                        'batch_no'          => $seed->batch_no,
                    ]
                );

                $answered = HasilEpds::where('session_token', $request->session_token)
                    ->whereNotNull('epds_id')->count();
                $total = SkriningEpds::count();

                return [
                    'ack' => 'ok',
                    'message' => 'Jawaban disimpan.',
                    'data' => ['answered' => $answered, 'total' => $total]
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("saveEpdsAnswerPublik error: " . $e->getMessage());
            return response()->json([
                'ack' => 'bad',
                'message' => 'Gagal menyimpan jawaban',
                'data' => null
            ], 200);
        }
    }

    public function submitEpdsPublik(Request $request)
    {
        $request->validate(['session_token' => 'required|uuid']);

        try {
            $result = DB::transaction(function () use ($request) {
                $seed = HasilEpds::where('session_token', $request->session_token)
                    ->lockForUpdate()->first();

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

                $q10Row = $rows->firstWhere('epds_id', 10);
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

                // Update token jika ada
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
                        'total_score'     => $totalScore,
                        'answered'        => $rows->count(),
                        'trimester'       => $seed->trimester,
                        'q10_agak_sering' => $q10AgakSering,
                        'is_risk'         => $isRisk,
                        'risk_title'      => $riskTitle,
                        'advice'          => $advice,
                        'batch_no'        => (int)$seed->batch_no,
                    ]
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("submitEpdsPublik error: " . $e->getMessage());
            return response()->json([
                'ack' => 'bad',
                'message' => 'Gagal submit skrining',
                'data' => null
            ], 200);
        }
    }

    public function cancelEpdsPublik(Request $request)
    {
        $request->validate([
            'session_token' => 'required|uuid',
            'ibu_id' => 'required|exists:data_diri,id'
        ]);

        try {
            $seed = HasilEpds::where('session_token', $request->session_token)->first();

            if (!$seed) {
                return response()->json([
                    'ack' => 'ok',
                    'message' => 'Session sudah tidak ada.'
                ], 200);
            }

            if ((int)$seed->ibu_id !== (int)$request->ibu_id) {
                return response()->json([
                    'ack' => 'bad',
                    'message' => 'Tidak berwenang membatalkan session ini.'
                ], 200);
            }

            $sudahSubmit = HasilEpds::where('session_token', $request->session_token)
                ->where('status', 'submitted')
                ->exists();

            if ($sudahSubmit) {
                return response()->json([
                    'ack' => 'bad',
                    'message' => 'Session sudah disubmit dan tidak bisa dibatalkan.'
                ], 200);
            }

            DB::transaction(function () use ($request) {
                HasilEpds::where('session_token', $request->session_token)
                    ->lockForUpdate()
                    ->delete();
            });

            return response()->json([
                'ack' => 'ok',
                'message' => 'Draft skrining dibatalkan.'
            ], 200);
        } catch (\Exception $e) {
            Log::error('cancelEpdsPublik error: ' . $e->getMessage());
            return response()->json([
                'ack' => 'bad',
                'message' => 'Gagal membatalkan draft skrining.'
            ], 200);
        }
    }

    /* ============================================================
     * DASS-21 - Versi Publik (tanpa Auth)
     * ============================================================ */

    public function startDassPublik(Request $request)
    {
        $request->validate([
            'ibu_id' => 'required|exists:data_diri,id',
            'mode' => 'nullable|in:kehamilan,umum'
        ]);

        try {
            $data_diri = DataDiri::find($request->ibu_id);

            if (!$data_diri) {
                return response()->json([
                    'ack' => 'bad',
                    'message' => 'Data diri tidak ditemukan',
                    'data' => null
                ], 200);
            }

            // Cek data kehamilan
            $riwayat = UsiaHamil::where('ibu_id', $data_diri->id)
                ->whereNotNull('hpht')
                ->whereNotNull('hpl')
                ->latest('created_at')
                ->first();

            // Tentukan mode otomatis jika tidak diberikan
            $mode = $request->mode ?? ($riwayat ? 'kehamilan' : 'umum');

            if ($mode === 'kehamilan') {
                return $this->startDassKehamilan($data_diri, $riwayat);
            } else {
                return $this->startDassUmum($data_diri);
            }
        } catch (\Exception $e) {
            Log::error("startDassPublik error: " . $e->getMessage());
            return response()->json([
                'ack' => 'bad',
                'message' => 'Gagal memulai skrining',
                'data' => null
            ], 200);
        }
    }

    private function startDassKehamilan($data_diri, $riwayat)
    {
        if (!$riwayat) {
            return response()->json([
                'ack' => 'need_hpht',
                'message' => 'HPHT belum diisi untuk mode kehamilan.',
                'data' => null
            ], 200);
        }

        $usiaMgg = hitungUsiaKehamilanMinggu($riwayat->hpht);
        $trimester = tentukanTrimester($usiaMgg);

        $submittedCount = HasilDass::where('ibu_id', $data_diri->id)
            ->where('trimester', $trimester)
            ->where('mode', 'kehamilan')
            ->where('status', 'submitted')
            ->count();

        $activeToken = null;
        if ($submittedCount > 0) {
            $activeToken = RescreenToken::active()
                ->where('ibu_id', $data_diri->id)
                ->where('jenis', 'dass')
                ->where('trimester', $trimester)
                ->where('mode', 'kehamilan')
                ->orderByDesc('created_at')
                ->first();

            $usable = $activeToken && $activeToken->used_count < $activeToken->max_uses;
            if (!$usable) {
                return response()->json([
                    'ack' => 'bad',
                    'message' => 'Skrining DASS-21 untuk trimester ini sudah diselesaikan.',
                    'data' => null
                ], 200);
            }
        }

        $draft = HasilDass::where('ibu_id', $data_diri->id)
            ->where('trimester', $trimester)
            ->where('mode', 'kehamilan')
            ->where('status', 'draft')
            ->orderByDesc('started_at')
            ->first();

        if ($draft) {
            $answered = HasilDass::where('session_token', $draft->session_token)
                ->whereNotNull('dass_id')->count();

            return response()->json([
                'ack' => 'ok',
                'message' => 'Lanjutkan skrining kehamilan yang tertunda.',
                'data' => [
                    'session_token' => $draft->session_token,
                    'type'          => 'kehamilan',
                    'trimester'     => $trimester,
                    'usia_hamil_id' => $riwayat->id,
                    'answered'      => $answered,
                    'total'         => SkriningDass::count(),
                    'batch_no'      => (int)($draft->batch_no ?? 1),
                ]
            ], 200);
        }

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
                'mode'              => 'kehamilan',
                'periode'           => null,
                'rescreen_token_id' => $activeToken?->id,
                'batch_no'          => $submittedCount + 1,
            ]);

            if ($trimester === 'trimester_1' && !$riwayatLock->trimester_1) $riwayatLock->trimester_1 = $now;
            if ($trimester === 'trimester_2' && !$riwayatLock->trimester_2) $riwayatLock->trimester_2 = $now;
            if ($trimester === 'trimester_3' && !$riwayatLock->trimester_3) $riwayatLock->trimester_3 = $now;
            if ($trimester === 'pasca_hamil' && !$riwayatLock->pasca_hamil) $riwayatLock->pasca_hamil = $now;
            $riwayatLock->save();

            return [
                'ack' => 'ok',
                'message' => 'Session skrining kehamilan dimulai.',
                'data' => [
                    'session_token' => $session->session_token,
                    'type'          => 'kehamilan',
                    'trimester'     => $trimester,
                    'usia_hamil_id' => $riwayatLock->id,
                    'answered'      => 0,
                    'total'         => SkriningDass::count(),
                    'batch_no'      => (int)$session->batch_no,
                ]
            ];
        });

        return response()->json($payload, 200);
    }

    private function startDassUmum($data_diri)
    {
        $periode = now()->format('Y-m');

        $submittedCount = HasilDass::where('ibu_id', $data_diri->id)
            ->where('mode', 'umum')
            ->where('periode', $periode)
            ->where('status', 'submitted')
            ->count();

        $activeToken = null;
        if ($submittedCount > 0) {
            $activeToken = RescreenToken::active()
                ->where('ibu_id', $data_diri->id)
                ->where('jenis', 'dass')
                ->where('mode', 'umum')
                ->where('periode', $periode)
                ->orderByDesc('created_at')
                ->first();

            $usable = $activeToken && $activeToken->used_count < $activeToken->max_uses;
            if (!$usable) {
                return response()->json([
                    'ack' => 'bad',
                    'message' => 'Skrining DASS-21 periode ini sudah diselesaikan.',
                    'data' => null
                ], 200);
            }
        }

        $draft = HasilDass::where('ibu_id', $data_diri->id)
            ->where('mode', 'umum')
            ->where('periode', $periode)
            ->where('status', 'draft')
            ->orderByDesc('started_at')
            ->first();

        if ($draft) {
            $answered = HasilDass::where('session_token', $draft->session_token)
                ->whereNotNull('dass_id')->count();

            return response()->json([
                'ack' => 'ok',
                'message' => 'Lanjutkan skrining umum yang tertunda.',
                'data' => [
                    'session_token' => $draft->session_token,
                    'type'          => 'umum',
                    'periode'       => $periode,
                    'answered'      => $answered,
                    'total'         => SkriningDass::count(),
                    'batch_no'      => (int)($draft->batch_no ?? 1),
                ]
            ], 200);
        }

        $payload = DB::transaction(function () use ($data_diri, $periode, $submittedCount, $activeToken) {
            $now = now();

            $session = HasilDass::create([
                'ibu_id'            => $data_diri->id,
                'mode'              => 'umum',
                'periode'           => $periode,
                'status'            => 'draft',
                'session_token'     => (string) Str::uuid(),
                'started_at'        => $now,
                'screening_date'    => $now,
                'usia_hamil_id'     => null,
                'trimester'         => null,
                'rescreen_token_id' => $activeToken?->id,
                'batch_no'          => $submittedCount + 1,
            ]);

            return [
                'ack' => 'ok',
                'message' => 'Session skrining umum dimulai.',
                'data' => [
                    'session_token' => $session->session_token,
                    'type'          => 'umum',
                    'periode'       => $periode,
                    'answered'      => 0,
                    'total'         => SkriningDass::count(),
                    'batch_no'      => (int)$session->batch_no,
                ]
            ];
        });

        return response()->json($payload, 200);
    }

    // Method save, submit, cancel untuk DASS (sama strukturnya dengan EPDS)
    public function saveDassAnswerPublik(Request $request)
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
                        'ibu_id'             => $seed->ibu_id,
                        'mode'               => $seed->mode,
                        'periode'            => $seed->periode,
                        'usia_hamil_id'      => $seed->usia_hamil_id,
                        'trimester'          => $seed->trimester,
                        'status'             => 'draft',
                        'answers_dass_id'    => $request->answers_dass_id,
                        'screening_date'     => now(),
                        'rescreen_token_id'  => $seed->rescreen_token_id,
                        'batch_no'           => $seed->batch_no,
                    ]
                );

                $answered = HasilDass::where('session_token', $request->session_token)
                    ->whereNotNull('dass_id')->count();

                return [
                    'ack' => 'ok',
                    'message' => 'Jawaban disimpan.',
                    'data' => [
                        'answered' => $answered,
                        'total' => SkriningDass::count()
                    ]
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("saveDassAnswerPublik error: " . $e->getMessage());
            return response()->json([
                'ack' => 'bad',
                'message' => 'Gagal menyimpan jawaban',
                'data' => null
            ], 200);
        }
    }

    public function submitDassPublik(Request $request)
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

                // Skoring DASS-21 (sama untuk kehamilan dan umum)
                $grpDep = [3, 5, 10, 13, 16, 17, 21];
                $grpAnx = [2, 4, 7, 9, 15, 19, 20];
                $grpStr = [1, 6, 8, 11, 12, 14, 18];

                $scoreByNo = [];
                foreach ($rows as $r) {
                    $no = (int) $r->dass_id;
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

                // Advice berdasarkan apakah kehamilan atau umum
                $isPregnancy = !empty($seed->usia_hamil_id);
                $advice = 'Hasil berada dalam rentang normal.';

                if ($levels['depression'] !== 'Normal' || $levels['anxiety'] !== 'Normal' || $levels['stress'] !== 'Normal') {
                    if ($isPregnancy) {
                        $advice = 'Pertimbangkan konsultasi dengan bidan, dokter kandungan, atau psikolog untuk mendapatkan dukungan yang tepat selama kehamilan.';
                    } else {
                        $advice = 'Pertimbangkan edukasi kesehatan jiwa, teknik relaksasi/napas, sleep hygiene, dan diskusi dengan tenaga kesehatan.';
                    }
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
                        'sum'       => ['depression' => $sumDep, 'anxiety' => $sumAnx, 'stress' => $sumStr],
                        'level'     => $levels,
                        'advice'    => $advice,
                        'flags'     => $flags,
                        'answered'  => $rows->count(),
                        'type'      => $isPregnancy ? 'kehamilan' : 'umum',
                        'trimester' => $seed->trimester,
                        'periode'   => $seed->periode,
                        'batch_no'  => (int)$seed->batch_no,
                    ]
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("submitDass error: " . $e->getMessage());
            return response()->json(['ack' => 'bad', 'message' => 'Gagal submit skrining', 'data' => null], 200);
        }
    }

    public function cancelDassPublik(Request $request)
    {
        $request->validate([
            'session_token' => 'required|uuid',
            'ibu_id' => 'required|exists:data_diri,id'
        ]);

        try {
            $seed = HasilDass::where('session_token', $request->session_token)->first();

            if (!$seed) {
                return response()->json([
                    'ack' => 'ok',
                    'message' => 'Session sudah tidak ada.'
                ], 200);
            }

            if ((int)$seed->ibu_id !== (int)$request->ibu_id) {
                return response()->json([
                    'ack' => 'bad',
                    'message' => 'Tidak berwenang membatalkan session ini.'
                ], 200);
            }

            $sudahSubmit = HasilDass::where('session_token', $request->session_token)
                ->where('status', 'submitted')
                ->exists();

            if ($sudahSubmit) {
                return response()->json([
                    'ack' => 'bad',
                    'message' => 'Session sudah disubmit dan tidak bisa dibatalkan.'
                ], 200);
            }

            DB::transaction(function () use ($request) {
                HasilDass::where('session_token', $request->session_token)
                    ->lockForUpdate()
                    ->delete();
            });

            return response()->json([
                'ack' => 'ok',
                'message' => 'Draft skrining dibatalkan.'
            ], 200);
        } catch (\Exception $e) {
            Log::error('cancelDassPublik error: ' . $e->getMessage());
            return response()->json([
                'ack' => 'bad',
                'message' => 'Gagal membatalkan draft skrining.'
            ], 200);
        }
    }
}
