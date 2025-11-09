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
->sortBy('id')
->map(function($q) use ($answerDass) {
return [
'dass_id' => $q->id,
'pertanyaan' => (string) ($q->pertanyaan ?? ''),
'answers' => $answerDass->map(fn($a) => [
'id' => $a->id,
'jawaban' => $a->jawaban,
'score' => $a->score,
])->values(),
];
})->values();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    <link rel="manifest" href="{{ asset('./manifest.json') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/animate-style.css') }}" />
    @vite(['resources/css/app.css','resources/js/app.js'])
    <script src="{{ asset('assets/js/skrining.js') }}" defer></script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        body {
            font-family: 'Poppins', sans-serif;
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
    {{-- JSON Data Seed untuk JavaScript --}}
    <script type="application/json" id="skrining-seed">
        {!!json_encode([
                'puskesmasId' => $puskesmasId,
                'csrfToken' => csrf_token(),
                'routes' => [
                    'checkNik' => route('check.nik'),
                    'register' => route('register.shortlink'),
                    'epdsStart' => route('umum.epds.start'),
                    'epdsSave' => route('umum.epds.save'),
                    'epdsSubmit' => route('umum.epds.submit'),
                    'epdsCancel' => route('umum.epds.cancel'),
                    'dassStart' => route('umum.dass.start'),
                    'dassSave' => route('umum.dass.save'),
                    'dassSubmit' => route('umum.dass.submit'),
                    'dassCancel' => route('umum.dass.cancel'),
                ],
                'questions' => [
                    'epds' => $epdsQuestions,
                    'dass' => $dassQuestions
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

    <div id="app-frame" class="overflow-auto min-h-screen md:flex transition-opacity duration-75">
        <div class="flex-1">
            <main class="p-4 md:p-8 pb-24 md:pb-8">
                <div id="swup">
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">Skrining Kesehatan Mental</h1>
                        <p class="text-sm text-gray-600">Akses melalui shortlink</p>
                    </div>

                    {{-- Step 1: Input NIK --}}
                    <div id="step-nik" class="bg-white rounded-2xl border shadow-sm p-5 md:p-8 mb-6">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl md:text-3xl font-bold text-gray-800">Mulai Skrining</h3>
                            <p class="text-sm text-gray-600 mt-2">Masukkan NIK Anda untuk melanjutkan</p>
                        </div>

                        <form id="formCheckNik" class="max-w-md mx-auto">
                            <input type="hidden" name="puskesmas_id" id="form-puskesmas-id">

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    NIK <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="nik" id="input-nik" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                    placeholder="Nomor Induk Kependudukan (16 digit)" maxlength="16" pattern="[0-9]{16}" required>
                                <p class="text-xs text-gray-500 mt-1">Masukkan 16 digit NIK Anda</p>
                            </div>

                            <button type="button" onclick="window.checkNikHandler()" class="w-full bg-teal-600 text-white py-3 rounded-lg hover:bg-teal-700 transition">
                                Lanjutkan
                            </button>
                        </form>
                    </div>

                    {{-- Step 2: Form Registrasi --}}
                    <div id="step-register" class="bg-white rounded-2xl border shadow-sm p-5 md:p-8 mb-6" style="display:none">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl md:text-3xl font-bold text-gray-800">Lengkapi Data Diri</h3>
                            <p class="text-sm text-gray-600 mt-2">NIK belum terdaftar. Silakan isi data diri Anda</p>
                        </div>

                        <form id="formRegister" class="max-w-md mx-auto space-y-4">
                            <input type="hidden" name="puskesmas_id" id="register-puskesmas-id">
                            <input type="hidden" name="nik" id="register-nik">

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="nama" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500"
                                    placeholder="Nama lengkap sesuai KTP" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor HP</label>
                                <input type="tel" name="no_hp" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500"
                                    placeholder="08xxxxxxxxxx" pattern="[0-9]{10,15}">
                            </div>

                            <div class="border-t pt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Apakah Anda sedang hamil?</label>
                                <div class="flex gap-3">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="is_pregnant" value="yes" onchange="window.toggleHphtField(true)" class="mr-2">
                                        <span class="text-sm">Ya</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="is_pregnant" value="no" checked onchange="window.toggleHphtField(false)" class="mr-2">
                                        <span class="text-sm">Tidak</span>
                                    </label>
                                </div>
                            </div>

                            <div id="hpht-field" style="display:none">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Hari Pertama Haid Terakhir (HPHT)
                                </label>
                                <input type="date" name="hpht" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500"
                                    max="{{ date('Y-m-d') }}">
                                <p class="text-xs text-gray-500 mt-1">Isi jika Anda sedang hamil untuk skrining berbasis kehamilan</p>
                            </div>

                            <div class="flex gap-2">
                                <button type="button" onclick="window.backToNik()" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg hover:bg-gray-300 transition">
                                    Kembali
                                </button>
                                <button type="button" onclick="window.submitRegister()" class="flex-1 bg-teal-600 text-white py-3 rounded-lg hover:bg-teal-700 transition">
                                    Simpan & Lanjut
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Step 3: Pilih Jenis Skrining --}}
                    <div id="step-skrining" class="bg-white rounded-2xl border shadow-sm p-5 md:p-8" style="display:none">
                        <div class="text-center mb-1">
                            <div class="mb-4 p-3 bg-teal-50 border border-teal-200 rounded-lg">
                                <p class="text-sm text-gray-700">
                                    <span class="font-medium">Halo, <span id="user-nama">-</span>!</span>
                                </p>
                                <p class="text-xs text-gray-600 mt-1" id="pregnancy-status"></p>
                            </div>

                            <h3 class="text-2xl md:text-3xl font-bold text-gray-800">Pilih Jenis Skrining</h3>
                            <p class="text-sm text-gray-600 mt-1">Proses cepat & rahasia — pilih yang ingin Anda mulai</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mt-6">
                            <!-- Card EPDS -->
                            <button type="button" onclick="window.startSkrining('epds')"
                                x-data="{ showTooltip: false }"
                                class="group relative overflow-hidden rounded-2xl border p-5 md:p-7 bg-gradient-to-br from-[#e7fff8] to-white hover:shadow-lg transition text-left">
                                
                                <!-- Tooltip Icon -->
                                <div class="absolute top-4 right-4 z-10"
                                    @mouseenter="showTooltip = true"
                                    @mouseleave="showTooltip = false"
                                    @click.stop>
                                    <div class="relative">
                                        <div class="w-6 h-6 rounded-full bg-teal-100 flex items-center justify-center cursor-help hover:bg-teal-200 transition">
                                            <svg class="w-4 h-4 text-teal-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        
                                        <!-- Tooltip Content -->
                                        <div x-show="showTooltip"
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0 scale-90"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-150"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-90"
                                            class="absolute right-0 top-8 w-64 bg-gray-900 text-white text-xs rounded-lg shadow-xl p-3 z-50"
                                            style="display: none;">
                                            <div class="relative">
                                                <div class="absolute -top-4 right-2 w-0 h-0 border-l-4 border-r-4 border-b-4 border-transparent border-b-gray-900"></div>
                                                <p class="font-semibold mb-1">EPDS (Edinburgh Postnatal Depression Scale)</p>
                                                <p class="text-gray-300">Alat skrining standar internasional untuk mendeteksi risiko gangguan suasana hati pada ibu hamil dan menyusui. Dikembangkan oleh Cox et al. (1987).</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-teal-500/10 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-teal-600" viewBox="0 0 24 24" fill="none">
                                            <path d="M4 6h16v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6zM7 10h10M7 14h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-lg font-semibold text-gray-900">Skrining EPDS</div>
                                        <p class="text-sm text-gray-600 mt-0.5">10 Pertanyaan • 3–5 menit</p>
                                        <p class="text-xs text-gray-500 mb-2">Khusus ibu hamil & menyusui</p>
                                        
                                        <!-- Deskripsi Singkat -->
                                        <div class="mt-3 pt-3 border-t border-teal-100">
                                            <p class="text-xs md:text-sm text-gray-700 leading-relaxed">
                                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-teal-500 mr-2"></span>
                                                Digunakan untuk mengetahui apakah Bunda mengalami tanda-tanda <strong>perubahan suasana hati setelah melahirkan</strong> atau <strong>penyesuaian emosional pasca melahirkan</strong>.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </button>

                            <!-- Card DASS-21 -->
                            <button type="button" onclick="window.startSkrining('dass')"
                                x-data="{ showTooltip: false }"
                                class="group relative overflow-hidden rounded-2xl border p-5 md:p-7 bg-gradient-to-br from-[#eaf5ff] to-white hover:shadow-lg transition text-left">
                                
                                <!-- Tooltip Icon -->
                                <div class="absolute top-4 right-4 z-10"
                                    @mouseenter="showTooltip = true"
                                    @mouseleave="showTooltip = false"
                                    @click.stop>
                                    <div class="relative">
                                        <div class="w-6 h-6 rounded-full bg-sky-100 flex items-center justify-center cursor-help hover:bg-sky-200 transition">
                                            <svg class="w-4 h-4 text-sky-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        
                                        <!-- Tooltip Content -->
                                        <div x-show="showTooltip"
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0 scale-90"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-150"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-90"
                                            class="absolute right-0 top-8 w-64 bg-gray-900 text-white text-xs rounded-lg shadow-xl p-3 z-50"
                                            style="display: none;">
                                            <div class="relative">
                                                <div class="absolute -top-4 right-2 w-0 h-0 border-l-4 border-r-4 border-b-4 border-transparent border-b-gray-900"></div>
                                                <p class="font-semibold mb-1">DASS-21 (Depression Anxiety Stress Scales)</p>
                                                <p class="text-gray-300">Instrumen pengukuran komprehensif untuk menilai tingkat suasana hati, kecemasan, dan stres. Cocok untuk populasi umum maupun ibu hamil. Dikembangkan oleh Lovibond & Lovibond (1995).</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-sky-500/10 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-sky-600" viewBox="0 0 24 24" fill="none">
                                            <path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-lg font-semibold text-gray-900">Skrining DASS-21</div>
                                        <p class="text-sm text-gray-600 mt-0.5">21 Pertanyaan • 5–8 menit</p>
                                        <p class="text-xs text-sky-600 mt-1" id="dass-mode-label">Mode: Umum</p>
                                        <p class="text-xs text-gray-500 mb-2">Otomatis sesuai kondisi Anda</p>
                                        
                                        <!-- Deskripsi Singkat -->
                                        <div class="mt-3 pt-3 border-t border-sky-100">
                                            <p class="text-xs md:text-sm text-gray-700 leading-relaxed">
                                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-sky-500 mr-2"></span>
                                                Mengukur tingkat <strong>stres, kecemasan, dan ketegangan</strong> yang mungkin sedang Bunda rasakan. <span class="text-gray-500">(Dapat digunakan oleh siapa pun)</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    {{-- Form Skrining --}}
                    <div id="form-skrining" class="mt-6 pb-24 md:pb-0 max-w-3xl mx-auto" style="display:none;">
                        <div class="bg-white rounded-2xl border shadow-sm p-4 md:p-6 lg:p-8">
                            <div id="badge-batch" class="mb-3 hidden">
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M7 11h10v2H7z" />
                                    </svg>
                                    <span id="badge-batch-text">Skrining ke-1</span>
                                </span>
                            </div>

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

                            <div id="steps-container" class="transition-all duration-300 ease-in-out"></div>

                            <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
                                <button id="prev-btn" onclick="window.prevOrCancel()" disabled class="flex-1 bg-gray-200 text-gray-700 text-sm md:text-base py-2 rounded-lg hover:bg-gray-300 transition">
                                    Sebelumnya
                                </button>
                                <div class="flex-1 flex gap-2">
                                    <button id="next-btn" onclick="window.nextStep()" disabled class="flex-1 bg-teal-600 text-white text-sm md:text-base py-2 rounded-lg hover:bg-teal-700 transition">
                                        Selanjutnya
                                    </button>
                                    <button id="submit-btn" type="button" onclick="window.submitCurrent()" style="display:none"
                                        class="flex-1 bg-teal-600 text-white text-sm md:text-base py-2 rounded-lg hover:bg-teal-700 transition">
                                        Selesai & Kirim
                                    </button>
                                </div>
                            </div>
                        </div>

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

                    {{-- Hasil EPDS --}}
                    <div id="result-epds" class="mt-6 max-w-3xl mx-auto" style="display:none">
                        <div class="bg-white p-6 rounded-2xl border shadow-sm text-center">
                            <h3 class="text-lg font-semibold mb-2 text-gray-800">Hasil Skrining EPDS</h3>
                            <p class="text-gray-600 mb-1">Skor Total</p>
                            <p id="res-score" class="text-6xl font-extrabold text-[#63b3ed] mb-4">0</p>
                            <p id="res-desc" class="text-gray-700 mb-6 text-sm">—</p>
                            <div class="grid gap-3 md:grid-cols-2">
                                <a href="{{ url('/') }}" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition text-center">
                                    Kembali ke Beranda
                                </a>
                                <button onclick="location.reload()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                                    Skrining Lagi
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Hasil DASS --}}
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
                                    <a href="{{ url('/') }}" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition text-center">
                                        Kembali ke Beranda
                                    </a>
                                    <button onclick="location.reload()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                                        Skrining Lagi
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <script>
            (function() {
                // Load data dari JSON seed
                const seedEl = document.getElementById('skrining-seed');
                if (!seedEl) {
                    console.error('Skrining seed not found');
                    return;
                }

                const SEED = JSON.parse(seedEl.textContent);

                // Set puskesmas_id di form
                document.getElementById('form-puskesmas-id').value = SEED.puskesmasId;
                document.getElementById('register-puskesmas-id').value = SEED.puskesmasId;

                // Global state
                window.SHORTLINK_STATE = {
                    ibuId: null,
                    nama: null,
                    usiaHamil: null,
                    mode: 'umum'
                };

                // Questions data
                window.QUESTIONS = SEED.questions;

                // Skrining state
                window.S = {
                    selectedSkrining: '',
                    currentStep: 0,
                    totalSteps: 0,
                    sessionToken: null,
                    answeredCount: 0,
                    localAnswers: {},
                    batchNo: 1,
                };

                // Toggle HPHT field
                window.toggleHphtField = function(show) {
                    const field = document.getElementById('hpht-field');
                    const input = field.querySelector('input[name="hpht"]');
                    if (show) {
                        field.style.display = 'block';
                        input.required = true;
                    } else {
                        field.style.display = 'none';
                        input.required = false;
                        input.value = '';
                    }
                };

                // Check NIK
                window.checkNikHandler = async function() {
                    const form = document.getElementById('formCheckNik');
                    const nikInput = document.getElementById('input-nik');
                    const nik = nikInput.value.trim();

                    if (nik.length !== 16 || !/^\d{16}$/.test(nik)) {
                        ALERT('NIK harus 16 digit angka', 'bad');
                        return;
                    }

                    const formData = new FormData(form);
                    try {
                        const response = await fetch(SEED.routes.checkNik, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': SEED.csrfToken
                            }
                        });

                        const result = await response.json();
                        if (result.ack === 'ok') {
                            if (result.data.exists) {
                                window.SHORTLINK_STATE.ibuId = result.data.ibu_id;
                                window.SHORTLINK_STATE.nama = result.data.nama;
                                window.SHORTLINK_STATE.usiaHamil = result.data.usia_hamil;
                                window.SHORTLINK_STATE.mode = result.data.usia_hamil ? 'kehamilan' : 'umum';
                                showStepSkrining();
                            } else {
                                document.getElementById('register-nik').value = nik;
                                document.getElementById('step-nik').style.display = 'none';
                                document.getElementById('step-register').style.display = 'block';
                                window.scrollTo({
                                    top: 0,
                                    behavior: 'smooth'
                                });
                            }
                        } else {
                            ALERT(result.message || 'Terjadi kesalahan', 'bad');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        ALERT('Terjadi kesalahan jaringan', 'bad');
                    }
                };

                // Submit registrasi
                window.submitRegister = async function() {
                    const form = document.getElementById('formRegister');
                    const formData = new FormData(form);
                    const nama = formData.get('nama');

                    if (!nama || nama.trim().length < 3) {
                        ALERT('Nama lengkap minimal 3 karakter', 'bad');
                        return;
                    }

                    try {
                        const response = await fetch(SEED.routes.register, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': SEED.csrfToken
                            }
                        });

                        const result = await response.json();
                        if (result.ack === 'ok') {
                            ALERT(result.message, 'ok');
                            window.SHORTLINK_STATE.ibuId = result.data.ibu_id;
                            window.SHORTLINK_STATE.nama = result.data.nama;
                            window.SHORTLINK_STATE.usiaHamil = result.data.usia_hamil;
                            window.SHORTLINK_STATE.mode = result.data.usia_hamil ? 'kehamilan' : 'umum';
                            showStepSkrining();
                        } else {
                            ALERT(result.message || 'Terjadi kesalahan', 'bad');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        ALERT('Terjadi kesalahan jaringan', 'bad');
                    }
                };

                // Kembali ke input NIK
                window.backToNik = function() {
                    document.getElementById('step-register').style.display = 'none';
                    document.getElementById('step-nik').style.display = 'block';
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                };

                // Show step pilih skrining
                function showStepSkrining() {
                    document.getElementById('step-nik').style.display = 'none';
                    document.getElementById('step-register').style.display = 'none';
                    document.getElementById('step-skrining').style.display = 'block';

                    document.getElementById('user-nama').textContent = window.SHORTLINK_STATE.nama || '-';
                    const pregnancyStatus = document.getElementById('pregnancy-status');
                    const dassModeLabel = document.getElementById('dass-mode-label');

                    if (window.SHORTLINK_STATE.usiaHamil) {
                        pregnancyStatus.innerHTML = `<strong>Usia Kehamilan:</strong> ${window.SHORTLINK_STATE.usiaHamil.keterangan} (${window.SHORTLINK_STATE.usiaHamil.trimester})`;
                        dassModeLabel.textContent = 'Mode: Kehamilan';
                    } else {
                        pregnancyStatus.textContent = 'Skrining mode umum';
                        dassModeLabel.textContent = 'Mode: Umum';
                    }
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }

                // Start skrining
                window.startSkrining = async function(type) {
                    if (!window.SHORTLINK_STATE.ibuId) {
                        ALERT('Data ibu tidak ditemukan', 'bad');
                        return;
                    }

                    window.S.selectedSkrining = type;
                    window.S.currentStep = 0;
                    window.S.totalSteps = 0;
                    window.S.sessionToken = null;
                    window.S.answeredCount = 0;
                    window.S.batchNo = 1;
                    window.S.localAnswers = {};

                    document.getElementById('result-epds').style.display = 'none';
                    document.getElementById('result-dass').style.display = 'none';

                    if (type === 'epds') {
                        const res = await startEpds();
                        if (res.ack !== 'ok') {
                            ALERT(res.message || 'Tidak bisa memulai skrining.', 'bad');
                            return;
                        }
                        window.S.sessionToken = res.data.session_token;
                        window.S.answeredCount = res.data.answered || 0;
                        window.S.batchNo = Number(res.data.batch_no || 1);
                        window.S.totalSteps = window.QUESTIONS.epds.length;
                        window.S.currentStep = Math.min(window.S.answeredCount, window.S.totalSteps - 1);
                        document.getElementById('dass-info').classList.add('hidden');
                        document.getElementById('tip-label').textContent = 'Jawab sesuai 7 hari terakhir.';
                    } else if (type === 'dass') {
                        const res = await startDass();
                        if (res.ack !== 'ok') {
                            ALERT(res.message || 'Tidak bisa memulai skrining.', 'bad');
                            return;
                        }
                        window.S.sessionToken = res.data.session_token;
                        window.S.answeredCount = res.data.answered || 0;
                        window.S.batchNo = Number(res.data.batch_no || 1);
                        window.S.totalSteps = window.QUESTIONS.dass.length;
                        window.S.currentStep = Math.min(window.S.answeredCount, window.S.totalSteps - 1);
                        document.getElementById('dass-info').classList.remove('hidden');
                        document.getElementById('tip-label').textContent = 'Pilih jawaban paling menggambarkan Anda.';
                    }

                    document.getElementById('step-skrining').style.display = 'none';
                    document.getElementById('form-skrining').style.display = 'block';
                    renderForm();
                    updateProgress();
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                };

                // API calls
                async function startEpds() {
                    try {
                        const response = await fetch(SEED.routes.epdsStart + '?ibu_id=' + window.SHORTLINK_STATE.ibuId);
                        return await response.json();
                    } catch (error) {
                        console.error('Error:', error);
                        return {
                            ack: 'bad',
                            message: 'Terjadi kesalahan jaringan'
                        };
                    }
                }

                async function startDass() {
                    try {
                        const response = await fetch(SEED.routes.dassStart + '?ibu_id=' + window.SHORTLINK_STATE.ibuId + '&mode=' + window.SHORTLINK_STATE.mode);
                        return await response.json();
                    } catch (error) {
                        console.error('Error:', error);
                        return {
                            ack: 'bad',
                            message: 'Terjadi kesalahan jaringan'
                        };
                    }
                }

                async function saveAnswer(qId, answerId) {
                    const isEpds = window.S.selectedSkrining === 'epds';
                    const route = isEpds ? SEED.routes.epdsSave : SEED.routes.dassSave;
                    const data = {
                        session_token: window.S.sessionToken,
                        ibu_id: window.SHORTLINK_STATE.ibuId
                    };

                    if (isEpds) {
                        data.epds_id = qId;
                        data.answers_epds_id = answerId;
                    } else {
                        data.dass_id = qId;
                        data.answers_dass_id = answerId;
                    }

                    try {
                        const response = await fetch(route, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': SEED.csrfToken
                            },
                            body: JSON.stringify(data)
                        });
                        return await response.json();
                    } catch (error) {
                        console.error('Error:', error);
                        return {
                            ack: 'bad',
                            message: 'Gagal menyimpan jawaban'
                        };
                    }
                }

                async function submitSkrining() {
                    const isEpds = window.S.selectedSkrining === 'epds';
                    const route = isEpds ? SEED.routes.epdsSubmit : SEED.routes.dassSubmit;

                    try {
                        const response = await fetch(route, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': SEED.csrfToken
                            },
                            body: JSON.stringify({
                                session_token: window.S.sessionToken,
                                ibu_id: window.SHORTLINK_STATE.ibuId
                            })
                        });
                        return await response.json();
                    } catch (error) {
                        console.error('Error:', error);
                        return {
                            ack: 'bad',
                            message: 'Gagal submit skrining'
                        };
                    }
                }

                async function cancelSkrining() {
                    const isEpds = window.S.selectedSkrining === 'epds';
                    const route = isEpds ? SEED.routes.epdsCancel : SEED.routes.dassCancel;

                    try {
                        const response = await fetch(route, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': SEED.csrfToken
                            },
                            body: JSON.stringify({
                                session_token: window.S.sessionToken,
                                ibu_id: window.SHORTLINK_STATE.ibuId
                            })
                        });
                        return await response.json();
                    } catch (error) {
                        console.error('Error:', error);
                        return {
                            ack: 'bad'
                        };
                    }
                }

                // Render form
                function renderForm() {
                    const wrap = document.getElementById('steps-container');
                    if (!wrap) return;
                    wrap.innerHTML = '';

                    const isEpds = window.S.selectedSkrining === 'epds';
                    const data = isEpds ? window.QUESTIONS.epds : window.QUESTIONS.dass;
                    const q = data[window.S.currentStep];
                    if (!q) return;

                    const header = document.createElement('div');
                    header.className = 'mb-3';
                    header.innerHTML = `
                        <div class="text-xs text-gray-500 mb-1">${isEpds ? 'EPDS' : 'DASS-21'} • Pertanyaan #${window.S.currentStep + 1}</div>
                        <h2 class="text-lg md:text-xl font-semibold text-gray-900">${q.pertanyaan}</h2>
                    `;
                    wrap.appendChild(header);

                    const list = document.createElement('div');
                    list.className = 'mt-4 grid gap-2';
                    const key = isEpds ? q.epds_id : q.dass_id;

                    q.answers.forEach(ans => {
                        const valueId = isEpds ? ans.answers_epds_id : ans.id;
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

                        if (window.S.localAnswers[key] == valueId) {
                            row.classList.add('border-teal-500', 'ring-1', 'ring-teal-200', 'bg-teal-50');
                            dot.classList.remove('bg-gray-300');
                            dot.classList.add('bg-teal-600');
                            input.checked = true;
                        }

                        row.addEventListener('click', async () => {
                            [...list.children].forEach(el => {
                                el.classList.remove('border-teal-500', 'ring-1', 'ring-teal-200', 'bg-teal-50');
                                const d = el.querySelector('.dot');
                                if (d) {
                                    d.classList.remove('bg-teal-600');
                                    d.classList.add('bg-gray-300');
                                }
                            });

                            input.checked = true;
                            row.classList.add('border-teal-500', 'ring-1', 'ring-teal-200', 'bg-teal-50');
                            dot.classList.remove('bg-gray-300');
                            dot.classList.add('bg-teal-600');

                            window.S.localAnswers[key] = valueId;
                            const res = await saveAnswer(key, valueId);
                            if (res.ack === 'ok') {
                                window.S.answeredCount = res.data?.answered ?? window.S.answeredCount;
                            }
                            toggleButtons();
                        });

                        list.appendChild(row);
                    });

                    wrap.appendChild(list);
                    toggleButtons();
                }

                function updateProgress() {
                    const pct = window.S.totalSteps ? Math.round(((window.S.currentStep + 1) / window.S.totalSteps) * 100) : 0;
                    document.getElementById('progress-bar').style.width = pct + '%';
                    document.getElementById('progress-percent').textContent = pct + '%';
                    document.getElementById('counter-text').textContent = `Pertanyaan ${Math.min(window.S.currentStep+1, window.S.totalSteps)} dari ${window.S.totalSteps}`;

                    const badgeWrap = document.getElementById('badge-batch');
                    const badgeText = document.getElementById('badge-batch-text');
                    if (window.S.batchNo > 1) {
                        badgeWrap.classList.remove('hidden');
                        badgeText.textContent = `Skrining ke-${window.S.batchNo}`;
                    } else {
                        badgeWrap.classList.add('hidden');
                    }
                }

                function toggleButtons() {
                    const prev = document.getElementById('prev-btn');
                    const next = document.getElementById('next-btn');
                    const submit = document.getElementById('submit-btn');

                    prev.textContent = window.S.currentStep === 0 ? 'Batal' : 'Sebelumnya';
                    prev.disabled = false;

                    const isEpds = window.S.selectedSkrining === 'epds';
                    const data = isEpds ? window.QUESTIONS.epds : window.QUESTIONS.dass;
                    const q = data[window.S.currentStep];
                    const key = isEpds ? q?.epds_id : q?.dass_id;
                    const hasAns = !!window.S.localAnswers[key];
                    const isLast = window.S.currentStep === window.S.totalSteps - 1;

                    if (isLast) {
                        next.style.display = 'none';
                        submit.style.display = '';
                        submit.disabled = !hasAns;
                    } else {
                        next.style.display = '';
                        submit.style.display = 'none';
                        next.disabled = !hasAns;
                    }
                }

                window.nextStep = function() {
                    const isEpds = window.S.selectedSkrining === 'epds';
                    const data = isEpds ? window.QUESTIONS.epds : window.QUESTIONS.dass;
                    const q = data[window.S.currentStep];
                    const key = isEpds ? q?.epds_id : q?.dass_id;

                    if (!window.S.localAnswers[key]) {
                        ALERT('Pilih salah satu jawaban dulu.', 'bad');
                        return;
                    }

                    if (window.S.currentStep < window.S.totalSteps - 1) {
                        window.S.currentStep++;
                        renderForm();
                        updateProgress();
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    }
                };

                window.prevOrCancel = async function() {
                    if (window.S.currentStep === 0) {
                        await cancelSkrining();
                        window.S.selectedSkrining = '';
                        window.S.currentStep = 0;
                        window.S.totalSteps = 0;
                        window.S.sessionToken = null;
                        window.S.answeredCount = 0;
                        window.S.batchNo = 1;
                        window.S.localAnswers = {};
                        document.getElementById('form-skrining').style.display = 'none';
                        document.getElementById('step-skrining').style.display = 'block';
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    } else {
                        window.S.currentStep--;
                        renderForm();
                        updateProgress();
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    }
                };

                window.submitCurrent = async function() {
                    const isEpds = window.S.selectedSkrining === 'epds';
                    const data = isEpds ? window.QUESTIONS.epds : window.QUESTIONS.dass;
                    const q = data[window.S.currentStep];
                    const key = isEpds ? q?.epds_id : q?.dass_id;

                    if (!window.S.localAnswers[key]) {
                        ALERT('Pilih salah satu jawaban dulu.', 'bad');
                        return;
                    }

                    const res = await submitSkrining();
                    if (res.ack === 'ok') {
                        if (isEpds) {
                            showEpdsResult(res.data);
                        } else {
                            showDassResult(res.data);
                        }
                    } else {
                        ALERT(res.message || 'Gagal submit skrining.', 'bad');
                    }
                };

                function showEpdsResult(payload) {
                    document.getElementById('form-skrining').style.display = 'none';
                    const total = Number(payload?.total_score ?? 0);
                    const batchLabel = window.S.batchNo > 1 ? ` (Skrining ke-${window.S.batchNo})` : '';
                    document.getElementById('res-score').textContent = String(total);
                    document.getElementById('res-desc').textContent = `${payload?.risk_title || '—'}${batchLabel} — ${payload?.advice || ''}`;
                    document.getElementById('result-epds').style.display = 'block';
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }

                function showDassResult(payload) {
                    document.getElementById('form-skrining').style.display = 'none';
                    const sum = payload.sum;
                    const level = payload.level;
                    const batchLabel = window.S.batchNo > 1 ? ` (Skrining ke-${window.S.batchNo})` : '';

                    document.getElementById('dass-dep-score').textContent = String(sum.depression);
                    document.getElementById('dass-anx-score').textContent = String(sum.anxiety);
                    document.getElementById('dass-str-score').textContent = String(sum.stress);
                    document.getElementById('dass-dep-level').textContent = level.depression;
                    document.getElementById('dass-anx-level').textContent = level.anxiety;
                    document.getElementById('dass-str-level').textContent = level.stress;
                    document.getElementById('dass-summary').textContent =
                        `Depresi: ${sum.depression} (${level.depression}) • Kecemasan: ${sum.anxiety} (${level.anxiety}) • Stres: ${sum.stress} (${level.stress})${batchLabel} — ${payload.advice}`;

                    document.getElementById('result-dass').style.display = 'block';
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            })();
        </script>
    </div>
</body>

</html>