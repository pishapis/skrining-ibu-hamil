@props(['title' => 'Simkeswa'])

@php
/* ===================== EPDS ===================== */
$grouped = $answer_epds->groupBy('epds_id');
$epdsQuestions = $grouped->map(function($answers){
$epds = optional($answers->first()->epds);
return [
'epds_id' => $epds?->id,
'pertanyaan' => (string) ($epds?->pertanyaan ?? ''),
'answers' => $answers->map(fn($a) => [
'answers_epds_id' => $a->id,
'jawaban' => $a->jawaban,
'score' => $a->score,
])->values(),
];
})->sortBy('epds_id')->values();

/* ===================== DASS-21 (tanpa relasi) ===================== */
$answerDass = $answer_dass;
$dassQuestions = $skrining_dass
->sortBy('id') // ganti ke kolom 'urutan' jika ada
->map(function($q) use ($answerDass) {
return [
'dass_id' => $q->id,
'pertanyaan' => (string) ($q->pertanyaan ?? ''),
'answers' => $answerDass->map(fn($a) => [
'id' => $a->id,
'jawaban' => $a->jawaban, // 4 opsi, skor 0..3
'score' => $a->score,
])->values(),
];
})->values();

$showDataDiriForm = isset($needsDataDiri) && $needsDataDiri;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="manifest" href="{{ asset('./manifest.json') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/animate-style.css') }}" />
    @vite(['resources/css/app.css','resources/js/app.js'])
    <script src="{{ asset('assets/js/skrining.js') }}" defer></script>

    <style>
        [x-cloak] {
            display: none !important
        }

        body {
            font-family: 'Poppins', sans-serif
        }

        .choice-tile {
            @apply w-full text-left p-3 md:p-4 rounded-xl border bg-white hover:shadow-sm transition-all cursor-pointer;
        }

        .choice-tile.selected {
            @apply border-teal-500 ring-1 ring-teal-200 bg-teal-50;
        }

        .dot {
            width: .5rem;
            height: .5rem;
            border-radius: 9999px;
        }
    </style>
</head>

<body class="bg-cover bg-no-repeat md:bg-slate-100 md:bg-none" style="background-image: url('/assets/img/bg-mobile.png');">
    <div id="swup-progress" aria-hidden="true"></div>
    <div id="app-frame" data-swup-container x-data="{ openSidebar:false }" x-bind:class="{ 'overflow-hidden': openSidebar }" class="overflow-auto min-h-screen md:flex transition-opacity duration-75">
        <x-session />
        <div class="flex-1">
            <main class="p-4 md:p-8 pb-24 md:pb-8">
                <div id="swup">
                    <x-header-back>Skrining</x-header-back>
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">

                        {{-- ======= Form Data Diri untuk User yang sudah login tapi data belum lengkap ======= --}}
                        @if($showDataDiriForm)
                        <div id="divDataDiri" class="bg-white rounded-2xl border shadow-sm p-5 md:p-8 mb-6">
                            <div class="text-center mb-6">
                                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-2xl md:text-3xl font-bold text-gray-800">Lengkapi Data Diri</h3>
                                <p class="text-sm text-gray-600 mt-2">Silakan lengkapi data diri Anda untuk melanjutkan skrining</p>
                            </div>

                            <form id="formDataDiri" class="max-w-md mx-auto space-y-4">
                                @csrf
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">NIK <span class="text-red-500">*</span></label>
                                    <input type="text" name="nik" class="input-field" placeholder="Nomor Induk Kependudukan" maxlength="16" required>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                                    <input type="text" name="nama" class="input-field" placeholder="Nama lengkap sesuai KTP" required>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor JKN/BPJS <span class="text-red-500">*</span></label>
                                    <input type="text" name="no_jkn" class="input-field" placeholder="Nomor kartu JKN/BPJS" required>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Kehamilan Ke- <span class="text-red-500">*</span></label>
                                    <select name="kehamilan_ke" class="input-field" required>
                                        <option value="">Pilih kehamilan ke berapa</option>
                                        @for($i = 1; $i <= 10; $i++)
                                            <option value="{{ $i }}">Kehamilan ke-{{ $i }}</option>
                                            @endfor
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor HP</label>
                                    <input type="tel" name="no_hp" class="input-field" placeholder="08xxxxxxxxxx">
                                </div>

                                <button type="button" onclick="submitDataDiri()" class="w-full btn-primary py-3 text-center">
                                    Simpan & Lanjutkan Skrining
                                </button>
                            </form>
                        </div>
                        @endif

                        {{-- ======= Pilihan Jenis Skrining ======= --}}
                        <div id="divSkrining" class="bg-white rounded-2xl border shadow-sm p-5 md:p-8" style="{{ $showDataDiriForm ? 'display:none' : '' }}">
                            <div class="text-center mb-1">
                                <h3 class="text-2xl md:text-3xl font-bold text-gray-800">Pilih Jenis Skrining</h3>
                                <p class="text-sm text-gray-600 mt-1">Proses cepat & rahasia — pilih yang ingin Anda mulai</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mt-6">
                                <button type="button" onclick="window.selectSkrining('epds')"
                                    class="group relative overflow-hidden rounded-2xl border p-5 md:p-7 bg-gradient-to-br from-[#e7fff8] to-white hover:shadow-lg transition">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-xl bg-teal-500/10 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-teal-600" viewBox="0 0 24 24" fill="none">
                                                <path d="M4 6h16v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6zM7 10h10M7 14h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-lg font-semibold text-gray-900">Skrining EPDS</div>
                                            <p class="text-sm text-gray-600">10 Pertanyaan • 3–5 menit</p>
                                        </div>
                                    </div>
                                </button>

                                <button type="button" onclick="window.selectSkrining('dass')"
                                    class="group relative overflow-hidden rounded-2xl border p-5 md:p-7 bg-gradient-to-br from-[#eaf5ff] to-white hover:shadow-lg transition">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-xl bg-sky-500/10 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-sky-600" viewBox="0 0 24 24" fill="none">
                                                <path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-lg font-semibold text-gray-900">Skrining DASS-21</div>
                                            <p class="text-sm text-gray-600">21 Pertanyaan • 5–8 menit</p>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        {{-- ======= Form Skrining (EPDS/DASS) ======= --}}
                        <div id="form-skrining" class="mt-6 pb-24 md:pb-0 max-w-3xl mx-auto" style="display:none;">
                            <div class="bg-white rounded-2xl border shadow-sm p-4 md:p-6 lg:p-8">

                                {{-- Info kehamilan --}}
                                @if(isset($usia_hamil))
                                @php
                                $trimesterLabel = [
                                'trimester_1' => 'Trimester I',
                                'trimester_2' => 'Trimester II',
                                'trimester_3' => 'Trimester III',
                                'pasca_hamil' => 'Pasca Melahirkan',
                                ][$usia_hamil['trimester']] ?? \Illuminate\Support\Str::headline($usia_hamil['trimester'] ?? '-');
                                @endphp
                                <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-3 md:p-4 text-amber-900">
                                    <div class="flex items-start gap-3">
                                        <svg class="w-5 h-5 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM9 9h2v6H9V9zm0-4h2v2H9V5z" />
                                        </svg>
                                        <div>
                                            <p class="font-semibold">Usia kehamilan saat ini</p>
                                            <p class="text-sm">{{ $usia_hamil['keterangan'] ?? '-' }} — <span class="font-medium">{{ $trimesterLabel }}</span></p>
                                            <p class="text-xs text-amber-800 mt-1">
                                                HPHT: {{ \Carbon\Carbon::parse($usia_hamil['hpht'])->translatedFormat('d M Y') }}
                                                • HPL: {{ \Carbon\Carbon::parse($usia_hamil['hpl'])->translatedFormat('d M Y') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                {{-- Badge skrining ke-N (rescreen) --}}
                                <div id="badge-batch" class="mb-3 hidden">
                                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M7 11h10v2H7z" />
                                        </svg>
                                        <span id="badge-batch-text">Skrining ke-1</span>
                                    </span>
                                </div>

                                {{-- Progress --}}
                                <div class="mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div id="progress-bar" class="bg-teal-600 h-2 rounded-full transition-all" style="width:0%"></div>
                                        </div>
                                        <span id="progress-percent" class="text-xs text-gray-600 min-w-[38px] text-right">0%</span>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between text-xs text-gray-600">
                                        <span id="counter-text">Pertanyaan 0 dari 0</span>
                                        <span id="tip-label" class="hidden md:inline"></span>
                                    </div>
                                </div>

                                {{-- Kontainer pertanyaan --}}
                                <div id="steps-container" class="transition-all duration-300 ease-in-out"></div>

                                <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
                                    <button id="prev-btn" onclick="prevOrCancel()" disabled class="flex-1 btn-secondary text-sm md:text-base py-2 rounded-lg">
                                        Sebelumnya
                                    </button>
                                    <div class="flex-1 flex gap-2">
                                        <button id="next-btn" onclick="nextStep()" disabled class="flex-1 btn-primary text-sm md:text-base py-2 rounded-lg">
                                            Selanjutnya
                                        </button>
                                        <button id="submit-btn" type="button" onclick="submitCurrent()" style="display:none"
                                            class="flex-1 btn-primary text-sm md:text-base py-2 rounded-lg">
                                            Selesai & Kirim
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Info DASS (hanya muncul saat DASS) --}}
                            <div id="dass-info" class="max-w-3xl mx-auto mt-4 hidden">
                                <div class="rounded-2xl border bg-white shadow-sm p-4 md:p-5">
                                    <h4 class="font-semibold text-gray-900 mb-2">Tentang Skor DASS-21</h4>
                                    <p class="text-sm text-gray-700">DASS-21 berisi 21 pertanyaan (skor 0–3). Penjumlahan domain dikali 2:</p>
                                    <ul class="list-disc pl-5 text-sm text-gray-700 mt-2 space-y-1">
                                        <li>Depresi: 3, 5, 10, 13, 16, 17, 21 → jumlah ×2</li>
                                        <li>Kecemasan: 2, 4, 7, 9, 15, 19, 20 → jumlah ×2</li>
                                        <li>Stres: 1, 6, 8, 11, 12, 14, 18 → jumlah ×2</li>
                                    </ul>
                                    <p class="text-xs text-gray-500 mt-3">Setelah skor muncul, rekomendasi otomatis akan ditampilkan.</p>
                                </div>
                            </div>
                        </div>

                        {{-- ======= Hasil EPDS ======= --}}
                        <div id="result-epds" class="mt-6 max-w-3xl mx-auto" style="display:none">
                            <div class="bg-white p-6 rounded-2xl border shadow-sm text-center">
                                <h3 class="text-lg font-semibold mb-2 text-gray-800">Hasil Skrining EPDS</h3>
                                <p class="text-gray-600 mb-1">Skor Total</p>
                                <p id="res-score" class="text-6xl font-extrabold text-[#63b3ed] mb-4">0</p>
                                <p id="res-desc" class="text-gray-700 mb-6 text-sm">—</p>
                                <div class="grid gap-3 md:grid-cols-2">
                                    <a href="{{ url('/materi/edukasi') }}" class="btn-primary w-full text-center">Lihat Materi Edukasi</a>
                                    <a href="{{ url('/') }}" class="btn-secondary w-full text-center">Kembali ke Beranda</a>
                                </div>
                            </div>
                        </div>

                        {{-- ======= Hasil DASS ======= --}}
                        <div id="result-dass" class="mt-6 max-w-3xl mx-auto" style="display:none">
                            <div class="bg-white p-6 rounded-2xl border shadow-sm">
                                <h3 class="text-lg font-semibold mb-4 text-center text-gray-800">Hasil Skrining DASS-21</h3>
                                <div class="grid md:grid-cols-3 gap-4 text-center">
                                    <div class="p-3 rounded-lg border bg-gray-50">
                                        <p class="text-xs text-gray-500 mb-1">Depresi</p>
                                        <p id="dass-dep-score" class="text-3xl font-bold">0</p>
                                        <p id="dass-dep-level" class="text-xs mt-1 text-gray-600">—</p>
                                    </div>
                                    <div class="p-3 rounded-lg border bg-gray-50">
                                        <p class="text-xs text-gray-500 mb-1">Kecemasan</p>
                                        <p id="dass-anx-score" class="text-3xl font-bold">0</p>
                                        <p id="dass-anx-level" class="text-xs mt-1 text-gray-600">—</p>
                                    </div>
                                    <div class="p-3 rounded-lg border bg-gray-50">
                                        <p class="text-xs text-gray-500 mb-1">Stres</p>
                                        <p id="dass-str-score" class="text-3xl font-bold">0</p>
                                        <p id="dass-str-level" class="text-xs mt-1 text-gray-600">—</p>
                                    </div>
                                </div>
                                <div class="mt-6 text-sm text-gray-700 space-y-3">
                                    <p id="dass-summary">—</p>
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <a href="{{ url('/materi/edukasi') }}" class="btn-primary w-full text-center">Lihat Materi Edukasi</a>
                                        <a href="{{ url('/') }}" class="btn-secondary w-full text-center">Kembali ke Beranda</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Hidden forms khusus EPDS --}}
                    <form id="formEpdsAnswer" method="post" action="{{ route('epds.save') }}" style="display:none">
                        @csrf
                        <input type="hidden" name="session_token">
                        <input type="hidden" name="epds_id">
                        <input type="hidden" name="answers_epds_id">
                    </form>
                    <form id="formEpdsSubmit" method="post" action="{{ route('epds.submit') }}" style="display:none">
                        @csrf
                        <input type="hidden" name="session_token">
                    </form>
                    <form id="formEpdsCancel" method="post" action="{{ route('epds.cancel') }}" style="display:none">
                        @csrf
                        <input type="hidden" name="session_token">
                    </form>

                    {{-- Hidden forms khusus DASS --}}
                    <form id="formDassAnswer" method="post" action="{{ route('dass.save') }}" style="display:none">
                        @csrf
                        <input type="hidden" name="session_token">
                        <input type="hidden" name="dass_id">
                        <input type="hidden" name="answers_dass_id">
                    </form>
                    <form id="formDassSubmit" method="post" action="{{ route('dass.submit') }}" style="display:none">
                        @csrf
                        <input type="hidden" name="session_token">
                    </form>
                    <form id="formDassCancel" method="post" action="{{ route('dass.cancel') }}" style="display:none">
                        @csrf
                        <input type="hidden" name="session_token">
                    </form>

                    {{-- Modal HPHT --}}
                    <x-modal name="request-create">
                        <form id="formRequestUsiaHamil" method="post" action="{{ route('first.create.usia.hamil') }}" class="p-6">
                            @csrf
                            @method('post')
                            <div class="space-y-2">
                                <label class="block text-gray-700 text-sm font-medium">Hari Pertama Haid Terakhir (HPHT)</label>
                                <input type="date" class="input-field" name="hpht" value="{{ old('hpht') }}" required>
                            </div>
                            <div class="mt-6 flex justify-end gap-2">
                                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                                <x-primary-button type="button" onclick="fSimpanUsiaHamil('formRequestUsiaHamil')" class="btn-primary hover:text-white">
                                    Simpan
                                </x-primary-button>
                            </div>
                        </form>
                    </x-modal>

                    {{-- Modal pop-up rekomendasi DASS --}}
                    <x-modal name="dass-advice">
                        <div class="p-6">
                            <h4 class="text-lg font-semibold mb-2">Rekomendasi Tindak Lanjut</h4>
                            <div id="dass-advice-body" class="text-sm text-gray-700 space-y-2"></div>
                            <div class="mt-6 text-right">
                                <x-primary-button x-on:click="$dispatch('close')">Tutup</x-primary-button>
                            </div>
                        </div>
                    </x-modal>
                </div>
            </main>
        </div>

        <script data-swup-reload-script>
            // Function untuk submit data diri
            window.submitDataDiri = async function() {
                const form = document.getElementById('formDataDiri');
                const formData = new FormData(form);

                try {
                    const response = await fetch('{{ route("update.data.diri") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || form.querySelector('[name="_token"]').value
                        }
                    });

                    const result = await response.json();

                    if (result.ack === 'ok') {
                        ALERT(result.message, 'ok');
                        document.getElementById('divDataDiri').style.display = 'none';
                        document.getElementById('divSkrining').style.display = 'block';
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    } else {
                        ALERT(result.message || 'Terjadi kesalahan saat menyimpan data.', 'bad');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    ALERT('Terjadi kesalahan jaringan. Silakan coba lagi.', 'bad');
                }
            };

            window.SKRININGS = window.SKRININGS || {};
            SKRININGS.skrining = SKRININGS.skrining || {};

            // Modal helpers (tetap)
            function openModal(name) {
                window.dispatchEvent(new CustomEvent('open-modal', {
                    detail: name
                }));
            }

            function closeModal(name) {
                window.dispatchEvent(new CustomEvent('close-modal', {
                    detail: name
                }));
            }

            // Data pertanyaan (tetap)
            if (!SKRININGS.skrining.QUESTIONS) {
                SKRININGS.skrining.QUESTIONS = {
                    epds: @js($epdsQuestions),
                    dass: @js($dassQuestions)
                };
            }
            var QUESTIONS = SKRININGS.skrining.QUESTIONS;

            // State (tetap)
            SKRININGS.skrining.state = SKRININGS.skrining.state || {
                selectedSkrining: '',
                currentStep: 0,
                totalSteps: 0,
                sessionToken: null,
                answeredCount: 0,
                localAnswers: {}, // key: epds_id/dass_id -> jawaban_id
                batchNo: 1,
            };
            S = SKRININGS.skrining.state;

            // ===== Endpoint helpers (ikuti punyamu) =====
            async function startEpds() {
                const f = new Fetch("{{ route('epds.start') }}");
                f.method = 'GET';
                return await f.run();
            }
            async function postEpdsAnswer(epdsId, answersEpdsId) {
                const form = document.getElementById('formEpdsAnswer');
                form.session_token.value = S.sessionToken || '';
                form.epds_id.value = epdsId;
                form.answers_epds_id.value = answersEpdsId;
                const f = new Fetch(form.action);
                f.method = 'POST';
                return await f.run('formEpdsAnswer');
            }
            async function postEpdsSubmit() {
                const form = document.getElementById('formEpdsSubmit');
                form.session_token.value = S.sessionToken || '';
                const f = new Fetch(form.action);
                f.method = 'POST';
                return await f.run('formEpdsSubmit');
            }
            async function postEpdsCancel() {
                const form = document.getElementById('formEpdsCancel');
                form.session_token.value = S.sessionToken || '';
                const f = new Fetch(form.action);
                f.method = 'POST';
                return await f.run('formEpdsCancel');
            }

            async function startDass() {
                const f = new Fetch("{{ route('dass.start') }}");
                f.method = 'GET';
                return await f.run();
            }
            async function postDassAnswer(dassId, answersDassId) {
                const form = document.getElementById('formDassAnswer');
                form.session_token.value = S.sessionToken || '';
                form.dass_id.value = dassId;
                form.answers_dass_id.value = answersDassId;
                const f = new Fetch(form.action);
                f.method = 'POST';
                return await f.run('formDassAnswer');
            }
            async function postDassSubmit() {
                const form = document.getElementById('formDassSubmit');
                form.session_token.value = S.sessionToken || '';
                const f = new Fetch(form.action);
                f.method = 'POST';
                return await f.run('formDassSubmit');
            }
            async function postDassCancel() {
                const form = document.getElementById('formDassCancel');
                form.session_token.value = S.sessionToken || '';
                const f = new Fetch(form.action);
                f.method = 'POST';
                return await f.run('formDassCancel');
            }

            // ===== Entry pilih skrining (tetap, +UX kecil) =====
            window.selectSkrining = window.selectSkrining || (async function(type) {
                S.selectedSkrining = type;
                S.currentStep = 0;
                S.totalSteps = 0;
                S.sessionToken = null;
                S.answeredCount = 0;
                S.batchNo = 1;
                Object.keys(S.localAnswers).forEach(k => delete S.localAnswers[k]);

                // hide hasil lama
                const r1 = document.getElementById('result-epds');
                if (r1) r1.style.display = 'none';
                const r2 = document.getElementById('result-dass');
                if (r2) r2.style.display = 'none';

                if (type === 'epds') {
                    const res = await startEpds();
                    if (res.ack === 'need_hpht') {
                        openModal('request-create');
                        return;
                    }
                    if (res.ack !== 'ok') {
                        ALERT(res.message || 'Tidak bisa memulai skrining.', 'bad');
                        return;
                    }
                    S.sessionToken = res.data.session_token;
                    S.answeredCount = res.data.answered || 0;
                    S.batchNo = Number(res.data.batch_no || 1);
                    S.totalSteps = (QUESTIONS.epds || []).length || 0;
                    if (!S.totalSteps) {
                        ALERT('Pertanyaan EPDS kosong.', 'bad');
                        return;
                    }
                    S.currentStep = Math.min(Math.max(0, S.answeredCount), Math.max(0, S.totalSteps - 1));
                    document.getElementById('dass-info')?.classList.add('hidden');
                    document.getElementById('tip-label').textContent = 'Jawab sesuai 7 hari terakhir.';
                } else if (type === 'dass') {
                    const res = await startDass();
                    if (res.ack === 'need_hpht') {
                        openModal('request-create');
                        return;
                    }
                    if (res.ack !== 'ok') {
                        ALERT(res.message || 'Tidak bisa memulai skrining.', 'bad');
                        return;
                    }
                    S.sessionToken = res.data?.session_token || null;
                    S.answeredCount = res.data?.answered || 0;
                    S.batchNo = Number(res.data?.batch_no || 1);
                    S.totalSteps = (QUESTIONS.dass || []).length || 0;
                    if (!S.totalSteps) {
                        ALERT('Pertanyaan DASS kosong.', 'bad');
                        return;
                    }
                    document.getElementById('dass-info')?.classList.remove('hidden');
                    document.getElementById('tip-label').textContent = 'Pilih jawaban paling menggambarkan Anda.';
                } else {
                    ALERT('Jenis skrining tidak dikenal.', 'bad');
                    return;
                }

                document.getElementById('divSkrining').style.display = 'none';
                document.getElementById('form-skrining').style.display = 'block';

                SKRININGS.skrining.renderForm();
                SKRININGS.skrining.updateProgress();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            // Setelah isi HPHT → auto start EPDS (tetap)
            window.fSimpanUsiaHamil = window.fSimpanUsiaHamil || (async function(formId) {
                try {
                    const form = document.getElementById(formId);
                    if (!form) return;
                    const f = new Fetch(form.action);
                    f.method = 'POST';
                    const res = await f.run(formId);
                    ALERT(res.message, res.ack);
                    if (res.ack === 'ok') {
                        closeModal('request-create');
                        await selectSkrining('epds');
                    }
                } catch (e) {
                    console.error(e);
                    ALERT('Terjadi kesalahan saat menyimpan HPHT.', 'bad');
                }
            });

            // ===== Render pertanyaan (tile card) =====
            SKRININGS.skrining.renderForm = function() {
                const wrap = document.getElementById('steps-container');
                if (!wrap || !S.selectedSkrining) return;
                wrap.innerHTML = '';

                const isEpds = (S.selectedSkrining === 'epds');
                const data = isEpds ? (QUESTIONS.epds || []) : (QUESTIONS.dass || []);
                const q = data[S.currentStep];
                if (!q) return;

                // Header pertanyaan
                const header = document.createElement('div');
                header.className = 'mb-3';
                header.innerHTML = `
                    <div class="text-xs text-gray-500 mb-1">${isEpds ? 'EPDS' : 'DASS-21'} • Pertanyaan #${S.currentStep + 1}</div>
                    <h2 class="text-lg md:text-xl font-semibold text-gray-900">${q.pertanyaan}</h2>
                `;
                wrap.appendChild(header);

                const list = document.createElement('div');
                list.className = 'mt-4 grid gap-2';
                const key = isEpds ? (q.epds_id ?? `epds_${S.currentStep}`) : (q.dass_id ?? `dass_${S.currentStep}`);

                // Handler untuk memilih jawaban
                const selectThis = async (row, input, dot, valueId) => {
                    // unselect siblings
                    [...list.children].forEach(el => {
                        el.classList.remove('border-teal-500', 'ring-1', 'ring-teal-200', 'bg-teal-50');
                        const d = el.querySelector('.dot');
                        if (d) {
                            d.classList.remove('bg-teal-600');
                            d.classList.add('bg-gray-300');
                        }
                    });

                    // select current
                    input.checked = true;
                    row.classList.add('border-teal-500', 'ring-1', 'ring-teal-200', 'bg-teal-50');
                    dot.classList.remove('bg-gray-300');
                    dot.classList.add('bg-teal-600');

                    // simpan ke state
                    S.localAnswers[key] = valueId;

                    // simpan ke backend
                    if (isEpds && q.epds_id) {
                        const res = await postEpdsAnswer(q.epds_id, valueId);
                        if (res.ack !== 'ok') {
                            ALERT(res.message || 'Gagal menyimpan jawaban.', 'bad');
                            return;
                        }
                        S.answeredCount = res.data?.answered ?? S.answeredCount;
                    }
                    if (!isEpds && q.dass_id) {
                        const res = await postDassAnswer(q.dass_id, valueId);
                        if (res.ack !== 'ok') {
                            ALERT(res.message || 'Gagal menyimpan jawaban.', 'bad');
                            return;
                        }
                        S.answeredCount = res.data?.answered ?? S.answeredCount;
                    }

                    SKRININGS.skrining.toggleButtons();
                };

                // Render opsi jawaban
                (q.answers || []).forEach(ans => {
                    const valueId = isEpds ? ans.answers_epds_id : ans.id;

                    // Kartu opsi
                    const row = document.createElement('label');
                    row.className = 'w-full text-left p-3 md:p-4 rounded-xl border bg-white hover:shadow-sm transition-all cursor-pointer flex items-start gap-3';

                    const input = document.createElement('input');
                    input.type = 'radio';
                    input.className = 'sr-only';
                    input.name = `q_${key}`;
                    input.value = valueId;

                    const dotWrap = document.createElement('div');
                    dotWrap.className = 'w-5 h-5 mt-0.5 rounded-full border flex items-center justify-center';
                    const dot = document.createElement('div');
                    dot.className = 'dot bg-gray-300';
                    dotWrap.appendChild(dot);

                    const text = document.createElement('div');
                    text.className = 'text-sm text-gray-800';
                    text.textContent = ans.jawaban;

                    row.appendChild(input);
                    row.appendChild(dotWrap);
                    row.appendChild(text);

                    // Preselect jika sudah ada di state
                    if (S.localAnswers[key] === valueId) {
                        row.classList.add('border-teal-500', 'ring-1', 'ring-teal-200', 'bg-teal-50');
                        dot.classList.remove('bg-gray-300');
                        dot.classList.add('bg-teal-600');
                        input.checked = true;
                    }

                    // Klik pada kartu
                    row.addEventListener('click', async (e) => {
                        e.preventDefault();
                        await selectThis(row, input, dot, valueId);
                    });

                    // Aksesibilitas
                    input.addEventListener('change', async () => {
                        await selectThis(row, input, dot, valueId);
                    });

                    list.appendChild(row);
                });

                wrap.appendChild(list);
                SKRININGS.skrining.toggleButtons();
            };

            SKRININGS.skrining.updateProgress = function() {
                const pct = S.totalSteps ? Math.round(((S.currentStep + 1) / S.totalSteps) * 100) : 0;
                const bar = document.getElementById('progress-bar');
                if (bar) bar.style.width = pct + '%';
                const pc = document.getElementById('progress-percent');
                if (pc) pc.textContent = pct + '%';
                const counter = document.getElementById('counter-text');
                if (counter) counter.textContent = `Pertanyaan ${Math.min(S.currentStep+1,S.totalSteps)} dari ${S.totalSteps}`;

                const badgeWrap = document.getElementById('badge-batch');
                const badgeText = document.getElementById('badge-batch-text');
                if (badgeWrap && badgeText) {
                    const n = Number(S.batchNo || 1);
                    if (n > 1) {
                        badgeWrap.classList.remove('hidden');
                        badgeText.textContent = `Skrining ke-${n}`;
                    } else {
                        badgeWrap.classList.add('hidden');
                    }
                }
            };

            SKRININGS.skrining.toggleButtons = function() {
                const prev = document.getElementById('prev-btn');
                const next = document.getElementById('next-btn');
                const submit = document.getElementById('submit-btn');
                if (!prev || !next || !submit) return;

                prev.textContent = (S.currentStep === 0) ? 'Batal' : 'Sebelumnya';
                prev.disabled = false;

                const isEpds = (S.selectedSkrining === 'epds');
                const data = isEpds ? (QUESTIONS.epds || []) : (QUESTIONS.dass || []);
                const q = data[S.currentStep];
                const key = isEpds ? (q?.epds_id ?? `epds_${S.currentStep}`) : (q?.dass_id ?? `dass_${S.currentStep}`);
                const hasAns = !!S.localAnswers[key];
                const isLast = (S.currentStep === S.totalSteps - 1);

                if (isLast) {
                    next.style.display = 'none';
                    submit.style.display = '';
                    submit.disabled = !hasAns;
                } else {
                    next.style.display = '';
                    submit.style.display = 'none';
                    next.disabled = !hasAns;
                    next.textContent = 'Selanjutnya';
                }
            };

            // ===== NAV / CANCEL =====
            window.nextStep = window.nextStep || function() {
                const isEpds = (S.selectedSkrining === 'epds');
                const data = isEpds ? (QUESTIONS.epds || []) : (QUESTIONS.dass || []);
                const q = data[S.currentStep];
                const key = isEpds ? (q?.epds_id ?? `epds_${S.currentStep}`) : (q?.dass_id ?? `dass_${S.currentStep}`);
                if (!S.localAnswers[key]) {
                    ALERT('Pilih salah satu jawaban dulu.', 'bad');
                    return;
                }
                if (S.currentStep < S.totalSteps - 1) {
                    S.currentStep++;
                    SKRININGS.skrining.renderForm();
                    SKRININGS.skrining.updateProgress();
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            };

            window.prevOrCancel = window.prevOrCancel || (async function() {
                if (S.currentStep === 0) {
                    try {
                        if (S.selectedSkrining === 'epds' && S.sessionToken) {
                            const r = await postEpdsCancel();
                            if (r?.ack !== 'ok') ALERT(r?.message || 'Gagal membatalkan sesi.', 'bad');
                        }
                        if (S.selectedSkrining === 'dass' && S.sessionToken) {
                            try {
                                const r = await postDassCancel();
                                if (r?.ack !== 'ok') ALERT(r?.message || 'Gagal membatalkan sesi.', 'bad');
                            } catch {}
                        }
                    } finally {
                        S.selectedSkrining = '';
                        S.currentStep = 0;
                        S.totalSteps = 0;
                        S.sessionToken = null;
                        S.answeredCount = 0;
                        S.batchNo = 1;
                        Object.keys(S.localAnswers).forEach(k => delete S.localAnswers[k]);
                        document.getElementById('form-skrining').style.display = 'none';
                        document.getElementById('divSkrining').style.display = 'block';
                        const bar = document.getElementById('progress-bar');
                        if (bar) bar.style.width = '0%';
                        const pc = document.getElementById('progress-percent');
                        if (pc) pc.textContent = '0%';
                        const counter = document.getElementById('counter-text');
                        if (counter) counter.textContent = 'Pertanyaan 0 dari 0';
                    }
                } else {
                    S.currentStep--;
                    SKRININGS.skrining.renderForm();
                    SKRININGS.skrining.updateProgress();
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            });

            // ===== DASS mapping & threshold =====
            DASS_GROUPS = {
                depression: [3, 5, 10, 13, 16, 17, 21],
                anxiety: [2, 4, 7, 9, 15, 19, 20],
                stress: [1, 6, 8, 11, 12, 14, 18],
            };
            DASS_THRESH = {
                depression: [{
                    max: 9,
                    label: 'Normal'
                }, {
                    max: 13,
                    label: 'Ringan'
                }, {
                    max: 20,
                    label: 'Sedang'
                }, {
                    max: 27,
                    label: 'Berat'
                }, {
                    max: Infinity,
                    label: 'Sangat Berat'
                }],
                anxiety: [{
                    max: 7,
                    label: 'Normal'
                }, {
                    max: 9,
                    label: 'Ringan'
                }, {
                    max: 14,
                    label: 'Sedang'
                }, {
                    max: 19,
                    label: 'Berat'
                }, {
                    max: Infinity,
                    label: 'Sangat Berat'
                }],
                stress: [{
                    max: 14,
                    label: 'Normal'
                }, {
                    max: 18,
                    label: 'Ringan'
                }, {
                    max: 25,
                    label: 'Sedang'
                }, {
                    max: 33,
                    label: 'Berat'
                }, {
                    max: Infinity,
                    label: 'Sangat Berat'
                }],
            };

            function severity(domain, score) {
                return (DASS_THRESH[domain] || []).find(t => score <= t.max)?.label || '—';
            }

            function computeDassResult() {
                const data = QUESTIONS.dass || [];
                const base = {
                    depression: 0,
                    anxiety: 0,
                    stress: 0
                };
                const scoreByNo = {};
                for (let i = 0; i < data.length; i++) {
                    const item = data[i];
                    const key = item.dass_id ?? `dass_${i}`;
                    const selectedId = S.localAnswers[key];
                    if (!selectedId) continue;
                    const ans = (item.answers || []).find(a => String(a.id) === String(selectedId));
                    scoreByNo[i + 1] = ans ? Number(ans.score || 0) : 0;
                }
                for (const n of DASS_GROUPS.depression) base.depression += (scoreByNo[n] || 0);
                for (const n of DASS_GROUPS.anxiety) base.anxiety += (scoreByNo[n] || 0);
                for (const n of DASS_GROUPS.stress) base.stress += (scoreByNo[n] || 0);
                const sum = {
                    depression: base.depression * 2,
                    anxiety: base.anxiety * 2,
                    stress: base.stress * 2
                };
                const level = {
                    depression: severity('depression', sum.depression),
                    anxiety: severity('anxiety', sum.anxiety),
                    stress: severity('stress', sum.stress)
                };
                return {
                    sum,
                    level
                };
            }

            // ===== Hasil EPDS =====
            SKRININGS.skrining.showEpdsResult = function(payload) {
                document.getElementById('form-skrining').style.display = 'none';
                const total = Number(payload?.total_score ?? 0);
                const risk = payload?.risk_title || '—';
                const advice = payload?.advice || '';
                const n = Number(S.batchNo || payload?.batch_no || 1);
                const batchLabel = n > 1 ? ` (Skrining ke-${n})` : '';
                document.getElementById('res-score').textContent = String(total);
                document.getElementById('res-desc').textContent = `${risk}${batchLabel} — ${advice}`;
                document.getElementById('result-epds').style.display = 'block';
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            };

            // ===== Hasil DASS + popup =====
            SKRININGS.skrining.showDassResult = function(payload) {
                document.getElementById('form-skrining').style.display = 'none';
                let sum, level, advice, flags;
                if (payload && payload.sum) {
                    sum = payload.sum;
                    level = payload.level;
                    advice = payload.advice;
                    flags = payload.flags;
                } else {
                    const r = computeDassResult();
                    sum = r.sum;
                    level = r.level;
                    advice = '—';
                    flags = {};
                }

                document.getElementById('dass-dep-score').textContent = String(sum.depression);
                document.getElementById('dass-anx-score').textContent = String(sum.anxiety);
                document.getElementById('dass-str-score').textContent = String(sum.stress);
                document.getElementById('dass-dep-level').textContent = level.depression;
                document.getElementById('dass-anx-level').textContent = level.anxiety;
                document.getElementById('dass-str-level').textContent = level.stress;

                const n = Number(S.batchNo || payload?.batch_no || 1);
                const batchLabel = n > 1 ? ` (Skrining ke-${n})` : '';
                document.getElementById('dass-summary').textContent =
                    `Depresi: ${sum.depression} (${level.depression}) • Kecemasan: ${sum.anxiety} (${level.anxiety}) • Stres: ${sum.stress} (${level.stress})${batchLabel} — ${advice}`;

                const msgs = [];
                if (flags?.anxiety_alert || sum.anxiety >= 12) msgs.push('Kecemasan: Silakan bertemu & konsultasi ke <b>Bidan</b> dan <b>Psikolog</b>.');
                if (flags?.depression_alert || sum.depression >= 15) msgs.push('Depresi: Sebaiknya konsultasi ke <b>Psikolog</b>.');
                if (flags?.stress_alert || sum.stress >= 20) msgs.push('Stres: Sebaiknya konsultasi ke <b>Dokter</b> dan <b>Psikolog</b>.');
                if (msgs.length) {
                    const body = document.getElementById('dass-advice-body');
                    body.innerHTML = msgs.map(m => `<p class="leading-relaxed">${m}</p>`).join('');
                    openModal('dass-advice');
                }

                document.getElementById('result-dass').style.display = 'block';
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            };

            // ===== Submit =====
            window.submitCurrent = window.submitCurrent || (async function() {
                const isEpds = (S.selectedSkrining === 'epds');
                const data = isEpds ? (QUESTIONS.epds || []) : (QUESTIONS.dass || []);
                const q = data[S.currentStep];
                const key = isEpds ? (q?.epds_id ?? `epds_${S.currentStep}`) : (q?.dass_id ?? `dass_${S.currentStep}`);
                if (!S.localAnswers[key]) {
                    ALERT('Pilih salah satu jawaban dulu.', 'bad');
                    return;
                }
                if (isEpds) {
                    const res = await postEpdsSubmit();
                    if (res.ack === 'ok') SKRININGS.skrining.showEpdsResult(res.data);
                    else ALERT(res.message || 'Gagal submit skrining.', 'bad');
                } else {
                    const res = await postDassSubmit();
                    if (res.ack !== 'ok') {
                        ALERT(res.message || 'Gagal submit skrining.', 'bad');
                        return;
                    }
                    SKRININGS.skrining.showDassResult(res.data);
                }
            });

            // ===== Init =====
            function initSkriningView() {
                const tip = document.getElementById('tip-label');
                if (tip) tip.classList.remove('hidden');
                const next = document.getElementById('next-btn');
                const prev = document.getElementById('prev-btn');
                const submit = document.getElementById('submit-btn');
                if (prev) {
                    prev.disabled = true;
                    prev.style.display = '';
                }
                if (next) {
                    next.disabled = true;
                    next.style.display = '';
                }
                if (submit) {
                    submit.disabled = true;
                    submit.style.display = 'none';
                }
            }
            document.addEventListener('DOMContentLoaded', initSkriningView);
            document.addEventListener('swup:contentReplaced', initSkriningView);
        </script>
    </div>
</body>

</html>