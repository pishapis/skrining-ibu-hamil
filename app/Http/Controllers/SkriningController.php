<?php

namespace App\Http\Controllers;

use App\Models\AnswerDass;
use Illuminate\Http\Request;
use App\Models\SkriningEpds;
use App\Models\AnswerEpds;
use App\Models\DataDiri;
use App\Models\HasilEpds;
use App\Models\HasilDass;
use App\Support\Kehamilan;
use App\Models\SkriningDass;
use App\Models\UsiaHamil;
use App\Models\GeneratedLink;
use App\Models\RiwayatKesehatan;
use App\Models\User;
use App\Models\RescreenToken; // <--- NEW
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\PseudoTypes\LowercaseString;

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

    public function skrining_umum(Request $request)
    {
        // Validasi short_code
        $fullUrl = $request->fullUrl();
        $parsedUrl = parse_url($fullUrl);
        if (!isset($parsedUrl['query'])) abort(404, "Halaman Tidak Ditemukan!");

        $shortCode = $parsedUrl['query'];
        $shortCodeValue = str_replace("=", "", $shortCode);

        // Cari data link yang sesuai dengan short_code
        $generatedLink = GeneratedLink::where('short_code', $shortCodeValue)->first();

        if (!$generatedLink) {
            abort(404, "Halaman Tidak Ditemukan!");
        }

        // Record access
        $generatedLink->recordAccess();

        // Ambil puskesmas_id dan token dari original_url
        parse_str(parse_url($generatedLink->original_url, PHP_URL_QUERY), $params);

        if (!isset($params['puskesmas_id']) || !isset($params['token']) || !isset($params['ts'])) {
            abort(404, "Halaman Tidak Ditemukan!");
        }

        $puskesmasId = $params['puskesmas_id'];
        $token = $params['token'];
        $timestamp = $params['ts'];

        // Verifikasi token
        $expectedToken = sha1($puskesmasId . $timestamp . $shortCodeValue . config('app.key'));

        if ($token !== $expectedToken) abort(404, "Halaman Tidak Ditemukan!");

        // Verifikasi apakah link sudah kadaluwarsa
        if ($generatedLink->isExpired()) {
            abort(404, "Link sudah kadaluwarsa!");
        }

        // Data skrining
        $title = "Skrining Kesehatan Mental";
        $answer_epds = AnswerEpds::with(['epds'])->get();
        $answer_dass = AnswerDass::all();
        $skrining_dass = SkriningDass::all();

        return view('pages.skrining.skrining-umum', compact(
            'title',
            'answer_epds',
            'answer_dass',
            'skrining_dass',
            'puskesmasId',
            'shortCodeValue'
        ));
    }

    // Endpoint untuk cek NIK
    public function checkNik(Request $request)
    {
        $request->validate([
            'nik' => 'required|string|size:16',
            'puskesmas_id' => 'required|exists:puskesmas,id'
        ]);

        $dataDiri = DataDiri::where('nik', $request->nik)
            ->where('puskesmas_id', $request->puskesmas_id)
            ->first();

        if ($dataDiri) {
            // User sudah terdaftar
            $usiaHamil = UsiaHamil::where('ibu_id', $dataDiri->id)
                ->whereNotNull('hpht')
                ->whereNotNull('hpl')
                ->latest('created_at')
                ->first();

            $usia_hamil = null;
            if ($usiaHamil) {
                $usiaMinggu = hitungUsiaKehamilanMinggu($usiaHamil->hpht);
                $trimester = tentukanTrimester($usiaMinggu);
                $keterangan = hitungUsiaKehamilanString($usiaHamil->hpht);

                $usia_hamil = [
                    'id' => $usiaHamil->id,
                    'hpht' => $usiaHamil->hpht,
                    'hpl' => $usiaHamil->hpl,
                    'usia_minggu' => $usiaMinggu,
                    'keterangan' => $keterangan,
                    'trimester' => $trimester,
                ];
            }

            return response()->json([
                'ack' => 'ok',
                'message' => 'NIK ditemukan',
                'data' => [
                    'exists' => true,
                    'ibu_id' => $dataDiri->id,
                    'nama' => $dataDiri->nama,
                    'usia_hamil' => $usia_hamil
                ]
            ]);
        }

        // User belum terdaftar
        return response()->json([
            'ack' => 'ok',
            'message' => 'NIK tidak ditemukan',
            'data' => [
                'exists' => false
            ]
        ]);
    }

    // Endpoint untuk registrasi user baru via shortlink
    public function registerViaShortlink(Request $request)
    {
        $request->validate([
            'nik' => 'required|string|size:16|unique:data_diri,nik',
            'nama' => 'required|string|max:255',
            'no_hp' => 'nullable|string|max:15',
            'puskesmas_id' => 'required|exists:puskesmas,id',
            'hpht' => 'nullable|date|before_or_equal:today'
        ]);

        DB::beginTransaction();
        try {
            // Buat user dummy untuk keperluan skrining
            $nameOriginal = $request->nama;
            $name = mb_strtolower(trim($request->nama), 'UTF-8');
            $middle = $name;

            if (preg_match('/^\s*(\S+)(?:\s+(\S+))?/', $name, $m)) {
                $middle = isset($m[2]) ? $m[2] : $m[1];
            }

            $middle = preg_replace('/[^a-z0-9]/', '', $middle);

            $user = User::create([
                'email' => 'temp_' . $request->nik . '@skrining.temp',
                'password' => bcrypt(Str::random(32)),
                'name'         => $nameOriginal,
                'username'     => $middle . Str::random(2),
                'role_id' => 1, // role ibu
                'puskesmas_id' => $request->puskesmas_id,
            ]);

            // Buat data diri
            $dataDiri = DataDiri::create([
                'user_id' => $user->id,
                'nik' => $request->nik,
                'nama' => $request->nama,
                'no_telp' => $request->no_hp,
                'puskesmas_id' => $request->puskesmas_id
            ]);

            // Jika ada HPHT, simpan usia hamil
            $usia_hamil = null;
            if ($request->hpht) {
                $hpht = Carbon::parse($request->hpht);
                $hpl = $hpht->copy()->addDays(280);

                $usiaHamil = UsiaHamil::create([
                    'ibu_id' => $dataDiri->id,
                    'hpht' => $hpht,
                    'hpl' => $hpl
                ]);

                $usiaMinggu = hitungUsiaKehamilanMinggu($hpht);
                $trimester = tentukanTrimester($usiaMinggu);
                $keterangan = hitungUsiaKehamilanString($hpht);

                $usia_hamil = [
                    'id' => $usiaHamil->id,
                    'hpht' => $usiaHamil->hpht,
                    'hpl' => $usiaHamil->hpl,
                    'usia_minggu' => $usiaMinggu,
                    'keterangan' => $keterangan,
                    'trimester' => $trimester,
                ];
            }

            DB::commit();

            return response()->json([
                'ack' => 'ok',
                'message' => 'Data berhasil disimpan',
                'data' => [
                    'ibu_id' => $dataDiri->id,
                    'nama' => $dataDiri->nama,
                    'usia_hamil' => $usia_hamil
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error register via shortlink: ' . $e->getMessage());

            return response()->json([
                'ack' => 'bad',
                'message' => 'Terjadi kesalahan saat menyimpan data'
            ], 500);
        }
    }

    // Method untuk update data diri user yang sudah login tapi belum lengkap
    public function updateDataDiri(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'ack' => 'error',
                'message' => 'Anda harus login terlebih dahulu.'
            ], 401);
        }

        $request->validate([
            'nik' => 'required|string|max:16|unique:data_diris,nik,' . Auth::id() . ',user_id',
            'nama' => 'required|string|max:255',
            'no_jkn' => 'required|string|max:20',
            'kehamilan_ke' => 'required|integer|min:1',
            'no_hp' => 'nullable|string|max:15'
        ]);

        try {
            $dataDiri = DataDiri::where('user_id', Auth::id())->first();

            if ($dataDiri) {
                $dataDiri->update([
                    'nik' => $request->nik,
                    'nama' => $request->nama,
                    'no_jkn' => $request->no_jkn,
                    'kehamilan_ke' => $request->kehamilan_ke,
                    'no_hp' => $request->no_hp,
                ]);
            } else {
                DataDiri::create([
                    'user_id' => Auth::id(),
                    'nik' => $request->nik,
                    'nama' => $request->nama,
                    'no_jkn' => $request->no_jkn,
                    'kehamilan_ke' => $request->kehamilan_ke,
                    'no_hp' => $request->no_hp,
                ]);
            }

            return response()->json([
                'ack' => 'ok',
                'message' => 'Data berhasil disimpan!',
            ]);
        } catch (\Throwable $e) {
            Log::error('Update data diri failed: ' . $e->getMessage());

            return response()->json([
                'ack' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data.'
            ], 500);
        }
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
            $keterangan  = hitungUsiaKehamilanString($riwayat->hpht);

            // sudah pernah submit pada trimester ini?
            $submittedCount = HasilEpds::where('ibu_id', $data_diri->id)
                ->where('trimester', $trimester)
                ->where('status', 'submitted')
                ->select('batch_no')
                ->first();

            $batchNo = $submittedCount ? $submittedCount->batch_no + 1 : 1;

            // Jika sudah ada submitted, cek token aktif (bolehkan skrining ulang bila ada token & masih ada kuota)
            $activeToken = null;
            if ($submittedCount && $batchNo > 0) {
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
                        'hpht'          => $riwayat->hpht,
                        'hpl'           => $riwayat->hpl,
                        'usia_minggu'   => $usiaMgg,
                        'keterangan'    => $keterangan,
                        'answered'      => $answered,
                        'total'         => $total,
                        'batch_no'      => (int)($draft->batch_no ?? 1), // <--- NEW
                    ]
                ]);
            }

            // === Atomic: buat session baru + stamp trimester ===
            $payload = DB::transaction(function () use ($data_diri, $riwayat, $trimester, $batchNo, $activeToken, $usiaMgg, $keterangan) {
                $now = now();

                $riwayatLock = UsiaHamil::lockForUpdate()->find($riwayat->id);

                $session = HasilEpds::create([
                    'ibu_id'           => $data_diri->id,
                    'usia_hamil_id'    => $riwayatLock->id,
                    'hpht'             => $riwayatLock->hpht,
                    'hpl'              => $riwayatLock->hpl,
                    'usia_minggu'      => $usiaMgg,
                    'status'           => 'draft',
                    'mode'             => 'kehamilan',
                    'periode'          => null,
                    'session_token'    => (string) Str::uuid(),
                    'trimester'        => $trimester,
                    'started_at'       => $now,
                    'screening_date'   => $now,
                    'rescreen_token_id' => $activeToken?->id,           // <--- NEW
                    'batch_no'         => $batchNo,         // <--- NEW
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
                        'hpht'           => $riwayat->hpht,
                        'hpl'            => $riwayat->hpl,
                        'keterangan'     => $keterangan,
                        'usia_minggu'    => $usiaMgg,
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
                        'mode'            => 'kehamilan',
                        'periode'         => null,
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

                // Cek Q10 (sesuaikan dengan epds_id yang sebenarnya)
                $q10Row = $rows->firstWhere('epds_id', 10);
                $q10Label = mb_strtolower($q10Row->answersEpds->jawaban ?? '');
                $q10AgakSering = str_contains($q10Label, 'agak') && str_contains($q10Label, 'sering');

                $isRisk = ($totalScore >= 13) || $q10AgakSering;

                // === PESAN YANG DIPERBAIKI ===
                if ($isRisk) {
                    $advice = 'Segera lakukan konsultasi dengan bidan, dokter, psikolog klinis, atau perawat di puskesmas untuk pemeriksaan lanjutan dan mendapatkan dukungan yang tepat.';
                    $textWa = "
                    Halo! 👋 Ini adalah pesan otomatis dari sistem skrining kami. Berdasarkan hasil yang kami terima, kami menyarankan agar Anda segera berkonsultasi dengan bidan, dokter, psikolog klinis, atau perawat di puskesmas untuk pemeriksaan lanjutan dan mendapatkan dukungan yang tepat. 💬
                    
                    Harap dicatat bahwa pesan ini tidak dapat dibalas karena ini hanya chatbot. 😊
                    Jaga kesehatan dan semangat selalu!
                    ";
                } else {
                    $advice = '🎉 Selamat! Kondisi kesehatan mental Anda saat ini baik. Tetap jaga kesehatan dengan istirahat cukup, makan bergizi, dan berbagi cerita dengan orang terdekat. 📅 Lakukan skrining ulang pada trimester berikutnya atau saat kontrol kehamilan rutin.';
                    $textWa = "
                    Halo! 👋 Ini adalah pesan otomatis dari sistem skrining kami. Berdasarkan hasil yang kami terima, 🎉 Selamat! Kondisi kesehatan mental Anda saat ini baik. Tetap jaga kesehatan dengan istirahat cukup, makan bergizi, dan berbagi cerita dengan orang terdekat. 📅 Lakukan skrining ulang pada trimester berikutnya atau saat kontrol kehamilan rutin.
                    
                    Harap dicatat bahwa pesan ini tidak dapat dibalas karena ini hanya chatbot. 😊
                    Jaga kesehatan dan semangat selalu!
                    ";
                }


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

                // === HITUNG JADWAL SKRINING BERIKUTNYA ===
                $nextSchedule = null;
                $nextScheduleText = null;

                if ($seed->usia_hamil_id) {
                    $usiaHamil = UsiaHamil::find($seed->usia_hamil_id);

                    if ($usiaHamil && $usiaHamil->hpht) {
                        $hpht = Carbon::parse($usiaHamil->hpht);
                        $hpl = $usiaHamil->hpl ? Carbon::parse($usiaHamil->hpl) : null;

                        // Ambil semua trimester yang sudah disubmit untuk ibu ini
                        $submittedTrimesters = HasilEpds::where('ibu_id', $seed->ibu_id)
                            ->where('status', 'submitted')
                            ->whereNotNull('trimester')
                            ->pluck('trimester')
                            ->unique()
                            ->values()
                            ->all();

                        // Gunakan fungsi yang sama dengan dashboard
                        $nextScheduleData = $this->computeNextScreeningSchedule($hpht, $hpl, $submittedTrimesters);

                        if ($nextScheduleData) {
                            $nextSchedule = $nextScheduleData['date']?->format('Y-m-d');
                            $nextScheduleText = $nextScheduleData['phase'];
                        }
                    }
                }

                $userId = Auth::user()->id;
                $phone_number = DataDiri::where('user_id', $userId)->select('no_telp')->first();

                if ($phone_number) {
                    sendNotificationWhatsApp($phone_number->no_telp, $textWa);
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
                        'advice'           => $advice,
                        'batch_no'         => (int)$seed->batch_no,
                        'next_schedule'    => $nextSchedule,
                        'next_schedule_text' => $nextScheduleText,
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

            // Auto-detect berdasarkan keberadaan data kehamilan
            $kesehatan = RiwayatKesehatan::where('ibu_id', $data_diri->id)->first();
            $riwayat = UsiaHamil::where('ibu_id', $data_diri->id)
                ->whereNotNull('hpht')
                ->whereNotNull('hpl')
                ->latest('created_at')
                ->first();

            if (!$riwayat && !$kesehatan) {
                return response()->json([
                    'ack' => 'need_hpht',
                    'message' => 'HPHT belum diisi. Silakan isi HPHT terlebih dahulu.',
                    'data' => null
                ], 200);
            }

            if ($riwayat) {
                // MODE KEHAMILAN - berdasarkan trimester
                $usiaMgg = hitungUsiaKehamilanMinggu($riwayat->hpht);
                $trimester = tentukanTrimester($usiaMgg);
                $keterangan  = hitungUsiaKehamilanString($riwayat->hpht);

                $submittedCount = HasilDass::where('ibu_id', $data_diri->id)
                    ->where('trimester', $trimester)
                    ->where('status', 'submitted')
                    ->select('batch_no')
                    ->first();

                $batchNo = $submittedCount ? $submittedCount->batch_no + 1 : 1;

                // Cek token aktif untuk kehamilan
                $activeToken = null;
                if ($submittedCount && $batchNo > 0) {
                    $activeToken = RescreenToken::active()
                        ->where('ibu_id', $data_diri->id)
                        ->where('jenis', 'dass')
                        ->where('trimester', $trimester)
                        ->whereNull('mode') // kehamilan tidak pakai mode
                        ->orderByDesc('created_at')
                        ->first();

                    $usable = $activeToken && $activeToken->used_count < $activeToken->max_uses;
                    if (!$usable) {
                        return response()->json([
                            'ack' => 'bad',
                            'message' => 'Skrining DASS-21 untuk trimester ini sudah diselesaikan. Hubungi petugas puskesmas bila perlu skrining ulang.',
                            'data' => null
                        ], 200);
                    }
                }

                // Cari draft kehamilan yang bisa dilanjutkan
                $draft = HasilDass::where('ibu_id', $data_diri->id)
                    ->where('trimester', $trimester)
                    ->whereNotNull('usia_hamil_id') // pastikan ini kehamilan
                    ->where('status', 'draft')
                    ->orderByDesc('started_at')
                    ->first();

                if ($draft) {
                    $answered = HasilDass::where('session_token', $draft->session_token)
                        ->whereNotNull('dass_id')
                        ->count();
                    $total = SkriningDass::count();

                    return response()->json([
                        'ack' => 'ok',
                        'message' => 'Lanjutkan skrining kehamilan yang tertunda.',
                        'data' => [
                            'session_token' => $draft->session_token,
                            'type'          => 'kehamilan',
                            'trimester'     => $trimester,
                            'usia_hamil_id' => $riwayat->id,
                            'hpht'          => $riwayat->hpht,
                            'hpl'           => $riwayat->hpl,
                            'usia_minggu'   => $usiaMgg,
                            'keterangan'    => $keterangan,
                            'answered'      => $answered,
                            'total'         => $total,
                            'batch_no'      => (int)($draft->batch_no ?? 1),
                        ]
                    ], 200);
                }

                // Buat session baru untuk kehamilan
                $payload = DB::transaction(function () use ($data_diri, $riwayat, $trimester, $batchNo, $activeToken, $usiaMgg, $keterangan) {
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
                        'rescreen_token_id' => $activeToken?->id,
                        'batch_no'         => $batchNo,
                        // Kolom umum dikosongkan
                        'mode'              => 'kehamilan',
                        'periode'           => null,
                    ]);

                    // Stamp kolom trimester (kalau kosong)
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
                            'hpht'          => $riwayatLock->hpht,
                            'hpl'           => $riwayatLock->hpl,
                            'usia_minggu'   => $usiaMgg,
                            'keterangan'    => $keterangan,
                            'answered'      => 0,
                            'total'         => SkriningDass::count(),
                            'batch_no'      => (int)$session->batch_no,
                        ]
                    ];
                });

                return response()->json($payload, 200);
            } else {
                // MODE UMUM - berdasarkan periode bulanan
                $periode = now()->format('Y-m');

                $submittedCount = HasilDass::where('ibu_id', $data_diri->id)
                    ->where('mode', 'umum')
                    ->where('periode', $periode)
                    ->where('status', 'submitted')
                    ->select('batch_no')
                    ->first();

                $batchNo = $submittedCount ? $submittedCount->batch_no + 1 : 1;

                // Cek token aktif untuk umum
                $activeToken = null;
                if ($submittedCount && $batchNo > 0) {
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
                            'message' => 'Skrining DASS-21 periode ini sudah diselesaikan. Hubungi petugas bila perlu skrining ulang.',
                            'data' => null
                        ], 200);
                    }
                }

                // Cari draft umum yang bisa dilanjutkan
                $draft = HasilDass::where('ibu_id', $data_diri->id)
                    ->where('mode', 'umum')
                    ->where('periode', $periode)
                    ->whereNull('usia_hamil_id') // pastikan ini umum
                    ->where('status', 'draft')
                    ->orderByDesc('started_at')
                    ->first();

                if ($draft) {
                    $answered = HasilDass::where('session_token', $draft->session_token)
                        ->whereNotNull('dass_id')
                        ->count();
                    $total = SkriningDass::count();

                    return response()->json([
                        'ack' => 'ok',
                        'message' => 'Lanjutkan skrining umum yang tertunda.',
                        'data' => [
                            'session_token' => $draft->session_token,
                            'type'          => 'umum',
                            'periode'       => $periode,
                            'answered'      => $answered,
                            'total'         => $total,
                            'batch_no'      => (int)($draft->batch_no ?? 1),
                        ]
                    ], 200);
                }

                // Buat session baru untuk umum
                $payload = DB::transaction(function () use ($data_diri, $periode, $batchNo, $activeToken) {
                    $now = now();

                    $session = HasilDass::create([
                        'ibu_id'            => $data_diri->id,
                        'mode'              => 'umum',
                        'periode'           => $periode,
                        'status'            => 'draft',
                        'session_token'     => (string) Str::uuid(),
                        'started_at'        => $now,
                        'screening_date'    => $now,
                        'rescreen_token_id' => $activeToken?->id,
                        'batch_no'          => $batchNo,
                        // Kolom kehamilan dikosongkan
                        'usia_hamil_id'     => null,
                        'trimester'         => null,
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
        } catch (\Exception $e) {
            Log::error("startDass error: " . $e->getMessage());
            return response()->json(['ack' => 'bad', 'message' => 'Gagal memulai skrining', 'data' => null], 200);
        }
    }

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
                $total = SkriningDass::count();

                return ['ack' => 'ok', 'message' => 'Jawaban disimpan.', 'data' => ['answered' => $answered, 'total' => $total]];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("saveDassAnswer error: " . $e->getMessage());
            return response()->json(['ack' => 'bad', 'message' => 'Gagal menyimpan jawaban', 'data' => null], 200);
        }
    }

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

                // Skoring DASS-21
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

                // === PESAN YANG DIPERBAIKI ===
                $isPregnancy = !empty($seed->usia_hamil_id);
                $allNormal = ($levels['depression'] === 'Normal' && $levels['anxiety'] === 'Normal' && $levels['stress'] === 'Normal');

                if ($allNormal) {
                    // HASIL BAIK
                    if ($isPregnancy) {
                        $advice = '🎉 Selamat! Kondisi kesehatan mental Anda saat ini baik. Tetap jaga keseimbangan dengan istirahat cukup, olahraga ringan, dan dukungan keluarga. 📅 Lakukan skrining ulang pada trimester berikutnya atau jika merasa ada perubahan kondisi emosional.';
                        $textWa = "
                        Halo! 👋 Ini adalah pesan otomatis dari sistem skrining kami. 🎉 Selamat! Kondisi kesehatan mental Anda saat ini baik. Tetap jaga keseimbangan dengan istirahat cukup, olahraga ringan, dan dukungan keluarga. 📅 Lakukan skrining ulang pada trimester berikutnya atau jika merasa ada perubahan kondisi emosional.
                        
                        Harap dicatat bahwa pesan ini tidak dapat dibalas karena ini hanya chatbot. 😊
                        Jaga kesehatan dan semangat selalu!
                        ";
                    } else {
                        $advice = '🎉 Selamat! Hasil skrining menunjukkan kondisi kesehatan mental Anda dalam rentang normal. Tetap jaga kesehatan mental dengan pola hidup sehat, istirahat cukup, dan aktivitas yang menyenangkan. 📅 Skrining ulang disarankan 1 bulan kemudian atau saat merasa ada perubahan kondisi emosional.';
                        $textWa = "
                        Halo! 👋 Ini adalah pesan otomatis dari sistem skrining kami. 🎉 Selamat! Hasil skrining menunjukkan kondisi kesehatan mental Anda dalam rentang normal. Tetap jaga kesehatan mental dengan pola hidup sehat, istirahat cukup, dan aktivitas yang menyenangkan. 📅 Skrining ulang disarankan 1 bulan kemudian atau saat merasa ada perubahan kondisi emosional.
                        
                        Harap dicatat bahwa pesan ini tidak dapat dibalas karena ini hanya chatbot. 😊
                        Jaga kesehatan dan semangat selalu!
                        ";
                    }
                } else {
                    // ADA INDIKASI
                    if ($isPregnancy) {
                        $advice = 'Terdeteksi adanya gejala pada satu atau lebih dimensi. Kami menyarankan konsultasi dengan bidan, dokter kandungan, atau psikolog untuk mendapatkan dukungan yang tepat selama kehamilan.';
                        $textWa = "
                        Halo! 👋 Ini adalah pesan otomatis dari sistem skrining kami. Terdeteksi adanya gejala pada satu atau lebih dimensi. Kami menyarankan konsultasi dengan bidan, dokter kandungan, atau psikolog untuk mendapatkan dukungan yang tepat selama kehamilan.
                        
                        Harap dicatat bahwa pesan ini tidak dapat dibalas karena ini hanya chatbot. 😊
                        Jaga kesehatan dan semangat selalu!
                        ";
                    } else {
                        $advice = 'Terdeteksi adanya gejala pada satu atau lebih dimensi. Pertimbangkan untuk mempelajari teknik relaksasi/napas, sleep hygiene, dan berbicara dengan tenaga kesehatan untuk dukungan lebih lanjut.';
                        $textWa = "
                        Halo! 👋 Ini adalah pesan otomatis dari sistem skrining kami. Terdeteksi adanya gejala pada satu atau lebih dimensi. Pertimbangkan untuk mempelajari teknik relaksasi/napas, sleep hygiene, dan berbicara dengan tenaga kesehatan untuk dukungan lebih lanjut.
                        
                        Harap dicatat bahwa pesan ini tidak dapat dibalas karena ini hanya chatbot. 😊
                        Jaga kesehatan dan semangat selalu!
                        ";
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

                // === HITUNG JADWAL SKRINING BERIKUTNYA ===
                $nextSchedule = null;
                $nextScheduleText = null;

                if ($seed->usia_hamil_id) {
                    $usiaHamil = UsiaHamil::find($seed->usia_hamil_id);

                    if ($usiaHamil && $usiaHamil->hpht) {
                        $hpht = Carbon::parse($usiaHamil->hpht);
                        $hpl = $usiaHamil->hpl ? Carbon::parse($usiaHamil->hpl) : null;

                        // Ambil semua trimester yang sudah disubmit untuk ibu ini
                        // Untuk DASS kehamilan, gunakan trimester
                        $submittedTrimesters = HasilDass::where('ibu_id', $seed->ibu_id)
                            ->where('status', 'submitted')
                            ->whereNotNull('trimester')
                            ->pluck('trimester')
                            ->unique()
                            ->values()
                            ->all();

                        // Gunakan fungsi yang sama dengan dashboard
                        $nextScheduleData = $this->computeNextScreeningSchedule($hpht, $hpl, $submittedTrimesters);

                        if ($nextScheduleData) {
                            $nextSchedule = $nextScheduleData['date']?->format('Y-m-d');
                            $nextScheduleText = $nextScheduleData['phase'];
                        }
                    }
                } elseif (!$isPregnancy) {
                    // Untuk skrining umum (non-kehamilan), jadwalkan 1 bulan kemudian
                    $nextSchedule = now()->addMonth()->format('Y-m-d');
                    $nextScheduleText = 'Kontrol Rutin';
                }

                $userId = Auth::user()->id;
                $phone_number = DataDiri::where('user_id', $userId)->select('no_telp')->first();

                if ($phone_number) {
                    sendNotificationWhatsApp($phone_number->no_telp, $textWa);
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
                        'all_normal' => $allNormal,
                        'next_schedule' => $nextSchedule,
                        'next_schedule_text' => $nextScheduleText,
                    ]
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("submitDass error: " . $e->getMessage());
            return response()->json(['ack' => 'bad', 'message' => 'Gagal submit skrining', 'data' => null], 200);
        }
    }

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
    private function computeNextScreeningSchedule(?Carbon $hpht, ?Carbon $hpl, array $submittedByTrimester): ?array
    {
        if (!$hpht) return null;
        $now = Carbon::now();

        $t1 = $hpht->copy();
        $t2 = $hpht->copy()->addWeeks(14);
        $t3 = $hpht->copy()->addWeeks(28);
        $pp = $hpl?->copy() ?? $hpht->copy()->addWeeks(40);

        $phases = [
            ['code' => 'trimester_1', 'name' => 'Trimester I',   'start' => $t1],
            ['code' => 'trimester_2', 'name' => 'Trimester II',  'start' => $t2],
            ['code' => 'trimester_3', 'name' => 'Trimester III', 'start' => $t3],
            ['code' => 'pasca_hamil', 'name' => 'Pasca Melahirkan',   'start' => $pp],
        ];

        $currentCode = null;
        try {
            $usiaMingguNow = Kehamilan::hitungUsiaMinggu($hpht->toDateString());
            $currentCode = Kehamilan::tentukanTrimester($usiaMingguNow, $hpl?->toDateString());
        } catch (\Throwable $e) {
            $currentCode = null;
        }

        $current = $currentCode ? collect($phases)->firstWhere('code', $currentCode) : null;

        if ($current && !in_array($current['code'], $submittedByTrimester, true)) {
            return [
                'phase'      => $current['name'],
                'code'       => $current['code'],
                'date'       => $now,
                'date_human' => $now->translatedFormat('d M Y'),
                'is_now'     => true,
            ];
        }

        $ordered = collect($phases)->sortBy(fn($p) => $p['start']->timestamp)->values();
        $startIndex = 0;
        if ($current) {
            $startIndex = max(0, $ordered->search(fn($p) => $p['code'] === $current['code']) + 1);
        }

        for ($i = $startIndex; $i < $ordered->count(); $i++) {
            $p = $ordered[$i];
            if (!in_array($p['code'], $submittedByTrimester, true)) {
                return [
                    'phase'      => $p['name'],
                    'code'       => $p['code'],
                    'date'       => $p['start'],
                    'date_human' => $p['start']->translatedFormat('d M Y'),
                    'is_now'     => $p['start']->isSameDay($now) || $p['start']->lessThan($now),
                ];
            }
        }

        return null;
    }

    // Tambahkan di SkriningController.php

    /**
     * Start EPDS untuk user umum (via shortlink)
     */
    public function umumEpdsStart(Request $request)
    {
        $request->validate([
            'ibu_id' => 'required|exists:data_diri,id'
        ]);

        try {
            $data_diri = DataDiri::find($request->ibu_id);

            if (!$data_diri) {
                return response()->json(['ack' => 'bad', 'message' => 'Data diri tidak ditemukan', 'data' => null], 200);
            }

            $riwayat = UsiaHamil::where('ibu_id', $data_diri->id)
                ->whereNotNull('hpht')->whereNotNull('hpl')->first();

            if (!$riwayat) {
                return response()->json([
                    'ack' => 'need_hpht',
                    'message' => 'HPHT belum diisi.',
                    'data' => null
                ], 200);
            }

            $usiaMgg   = hitungUsiaKehamilanMinggu($riwayat->hpht);
            $trimester = tentukanTrimester($usiaMgg);
            $keterangan  = hitungUsiaKehamilanString($riwayat->hpht);

            $submittedCount = HasilEpds::where('ibu_id', $data_diri->id)
                ->where('trimester', $trimester)
                ->where('status', 'submitted')
                ->select('batch_no')
                ->first();

            $batchNo = $submittedCount ? $submittedCount->batch_no + 1 : 1;

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
                        'hpht'          => $riwayat->hpht,
                        'hpl'           => $riwayat->hpl,
                        'usia_minggu'   => $usiaMgg,
                        'keterangan'    => $keterangan,
                        'answered'      => $answered,
                        'total'         => $total,
                        'batch_no'      => (int)($draft->batch_no ?? 1),
                    ]
                ]);
            }

            $payload = DB::transaction(function () use ($data_diri, $riwayat, $trimester, $batchNo, $usiaMgg, $keterangan) {
                $now = now();
                $riwayatLock = UsiaHamil::lockForUpdate()->find($riwayat->id);

                $session = HasilEpds::create([
                    'ibu_id'           => $data_diri->id,
                    'usia_hamil_id'    => $riwayatLock->id,
                    'hpht'             => $riwayatLock->hpht,
                    'hpl'              => $riwayatLock->hpl,
                    'usia_minggu'      => $usiaMgg,
                    'status'           => 'draft',
                    'mode'             => 'kehamilan',
                    'periode'          => null,
                    'session_token'    => (string) Str::uuid(),
                    'trimester'        => $trimester,
                    'started_at'       => $now,
                    'screening_date'   => $now,
                    'batch_no'         => $batchNo,
                ]);

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
                        'hpht'           => $riwayat->hpht,
                        'hpl'            => $riwayat->hpl,
                        'keterangan'     => $keterangan,
                        'usia_minggu'    => $usiaMgg,
                        'answered'       => 0,
                        'total'          => $total,
                        'batch_no'       => (int)$session->batch_no,
                    ]
                ];
            });

            return response()->json($payload);
        } catch (\Exception $e) {
            Log::error("umumEpdsStart error: " . $e->getMessage());
            return response()->json(['ack' => 'bad', 'message' => 'Gagal memulai skrining', 'data' => null], 200);
        }
    }

    /**
     * Start DASS untuk user umum (via shortlink)
     */
    public function umumDassStart(Request $request)
    {
        $request->validate([
            'ibu_id' => 'required|exists:data_diri,id',
            'mode' => 'required|in:umum,kehamilan'
        ]);

        try {
            $data_diri = DataDiri::find($request->ibu_id);

            if (!$data_diri) {
                return response()->json(['ack' => 'bad', 'message' => 'Data diri tidak ditemukan', 'data' => null], 200);
            }

            $mode = $request->mode;
            $riwayat = UsiaHamil::where('ibu_id', $data_diri->id)
                ->whereNotNull('hpht')
                ->whereNotNull('hpl')
                ->latest('created_at')
                ->first();

            if ($mode === 'kehamilan') {
                if (!$riwayat) {
                    return response()->json([
                        'ack' => 'need_hpht',
                        'message' => 'HPHT belum diisi.',
                        'data' => null
                    ], 200);
                }

                $usiaMgg = hitungUsiaKehamilanMinggu($riwayat->hpht);
                $trimester = tentukanTrimester($usiaMgg);
                $keterangan  = hitungUsiaKehamilanString($riwayat->hpht);

                $submittedCount = HasilDass::where('ibu_id', $data_diri->id)
                    ->where('trimester', $trimester)
                    ->where('status', 'submitted')
                    ->select('batch_no')
                    ->first();

                $batchNo = $submittedCount ? $submittedCount->batch_no + 1 : 1;

                $draft = HasilDass::where('ibu_id', $data_diri->id)
                    ->where('trimester', $trimester)
                    ->whereNotNull('usia_hamil_id')
                    ->where('status', 'draft')
                    ->orderByDesc('started_at')
                    ->first();

                if ($draft) {
                    $answered = HasilDass::where('session_token', $draft->session_token)
                        ->whereNotNull('dass_id')
                        ->count();
                    $total = SkriningDass::count();

                    return response()->json([
                        'ack' => 'ok',
                        'message' => 'Lanjutkan skrining kehamilan yang tertunda.',
                        'data' => [
                            'session_token' => $draft->session_token,
                            'type'          => 'kehamilan',
                            'trimester'     => $trimester,
                            'usia_hamil_id' => $riwayat->id,
                            'hpht'          => $riwayat->hpht,
                            'hpl'           => $riwayat->hpl,
                            'usia_minggu'   => $usiaMgg,
                            'keterangan'    => $keterangan,
                            'answered'      => $answered,
                            'total'         => $total,
                            'batch_no'      => (int)($draft->batch_no ?? 1),
                        ]
                    ], 200);
                }

                $payload = DB::transaction(function () use ($data_diri, $riwayat, $trimester, $batchNo, $usiaMgg, $keterangan) {
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
                        'batch_no'          => $batchNo,
                        'mode'              => 'kehamilan',
                        'periode'           => null,
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
                            'hpht'          => $riwayatLock->hpht,
                            'hpl'           => $riwayatLock->hpl,
                            'usia_minggu'   => $usiaMgg,
                            'keterangan'    => $keterangan,
                            'answered'      => 0,
                            'total'         => SkriningDass::count(),
                            'batch_no'      => (int)$session->batch_no,
                        ]
                    ];
                });

                return response()->json($payload, 200);
            } else {
                // MODE UMUM
                $periode = now()->format('Y-m');

                $submittedCount = HasilDass::where('ibu_id', $data_diri->id)
                    ->where('mode', 'umum')
                    ->where('periode', $periode)
                    ->where('status', 'submitted')
                    ->select('batch_no')
                    ->first();

                $batchNo = $submittedCount ? $submittedCount->batch_no + 1 : 1;

                $draft = HasilDass::where('ibu_id', $data_diri->id)
                    ->where('mode', 'umum')
                    ->where('periode', $periode)
                    ->whereNull('usia_hamil_id')
                    ->where('status', 'draft')
                    ->orderByDesc('started_at')
                    ->first();

                if ($draft) {
                    $answered = HasilDass::where('session_token', $draft->session_token)
                        ->whereNotNull('dass_id')
                        ->count();
                    $total = SkriningDass::count();

                    return response()->json([
                        'ack' => 'ok',
                        'message' => 'Lanjutkan skrining umum yang tertunda.',
                        'data' => [
                            'session_token' => $draft->session_token,
                            'type'          => 'umum',
                            'periode'       => $periode,
                            'answered'      => $answered,
                            'total'         => $total,
                            'batch_no'      => (int)($draft->batch_no ?? 1),
                        ]
                    ], 200);
                }

                $payload = DB::transaction(function () use ($data_diri, $periode, $batchNo) {
                    $now = now();

                    $session = HasilDass::create([
                        'ibu_id'            => $data_diri->id,
                        'mode'              => 'umum',
                        'periode'           => $periode,
                        'status'            => 'draft',
                        'session_token'     => (string) Str::uuid(),
                        'started_at'        => $now,
                        'screening_date'    => $now,
                        'batch_no'          => $batchNo,
                        'usia_hamil_id'     => null,
                        'trimester'         => null,
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
        } catch (\Exception $e) {
            Log::error("umumDassStart error: " . $e->getMessage());
            return response()->json(['ack' => 'bad', 'message' => 'Gagal memulai skrining', 'data' => null], 200);
        }
    }
}
