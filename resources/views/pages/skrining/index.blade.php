<x-app-layout>
    @section('page_title', 'Pilih Jenis Skrining')
    <x-slot name="title">Pilih Jenis Skrining | Skrining Ibu Hamil</x-slot>

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
    @endphp

    <!-- Pilihan Skrining -->
    <x-header-back>Skrining</x-header-back>

    <div class="flex justify-center mt-20 bg-white p-4 rounded-xl shadow-md mb-6">
        <div class="w-full max-w-4xl p-4">
            <h3 class="text-3xl font-semibold text-center text-gray-700 mb-8">Pilih Jenis Skrining</h3>
            <div id="divSkrining">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="flex justify-center">
                        <button type="button" onclick="window.selectSkrining('epds')"
                            class="btn-secondary w-full md:w-72 md:h-48 p-6 rounded-xl text-lg font-medium transition duration-300 transform hover:scale-105">
                            Skrining EPDS (10 Pertanyaan)
                        </button>
                    </div>
                    <div class="flex justify-center">
                        <button type="button" onclick="window.selectSkrining('dass')"
                            class="btn-secondary w-full md:w-72 md:h-48 p-6 rounded-xl text-lg font-medium transition duration-300 transform hover:scale-105">
                            Skrining DASS-21 (21 Pertanyaan)
                        </button>
                    </div>
                </div>
            </div>

            <!-- Form Skrining -->
            <div id="form-skrining" class="max-w-3xl mx-auto" style="display:none;">

                {{-- Panel info usia hamil --}}
                @if(isset($usia_hamil))
                @php
                $trimesterLabel = [
                'trimester_1' => 'Trimester I',
                'trimester_2' => 'Trimester II',
                'trimester_3' => 'Trimester III',
                'pasca_hamil' => 'Pasca Hamil',
                ][$usia_hamil['trimester']] ?? \Illuminate\Support\Str::headline($usia_hamil['trimester'] ?? '-');
                @endphp
                <div class="mb-6 p-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-900">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM9 9h2v6H9V9zm0-4h2v2H9V5z" />
                        </svg>
                        <div>
                            <p class="font-semibold">Usia kehamilan saat ini</p>
                            <p class="text-sm">
                                {{ $usia_hamil['keterangan'] ?? '-' }} — <span class="font-medium">{{ $trimesterLabel }}</span>
                            </p>
                            <p class="text-xs text-amber-800 mt-1">
                                HPHT: {{ \Carbon\Carbon::parse($usia_hamil['hpht'])->translatedFormat('d M Y') }}
                                • HPL: {{ \Carbon\Carbon::parse($usia_hamil['hpl'])->translatedFormat('d M Y') }}
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Badge Skrining ke-N (muncul bila rescreen) -->
                <div id="badge-batch" class="mb-3 hidden">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs
                                 bg-indigo-50 text-indigo-700 border border-indigo-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 11h10v2H7z" />
                        </svg>
                        <span id="badge-batch-text">Skrining ke-1</span>
                    </span>
                </div>

                <!-- Progress Bar + Counter -->
                <div class="mb-6">
                    <div class="flex items-center">
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div id="progress-bar" class="bg-teal-600 h-2.5 rounded-full transition-all duration-500" style="width:0%"></div>
                        </div>
                    </div>
                    <div class="mt-2 text-center text-sm text-gray-600">
                        <span id="counter-text">Pertanyaan 0 dari 0</span>
                    </div>
                </div>

                <!-- Kontainer pertanyaan -->
                <div id="steps-container" class="transition-all duration-500 ease-in-out"></div>

                <!-- NAV -->
                <div class="flex justify-between mt-8">
                    <button id="prev-btn" onclick="prevOrCancel()" disabled class="btn-primary text-white rounded-lg text-sm">
                        Sebelumnya
                    </button>

                    <div class="flex gap-2">
                        <button id="next-btn" onclick="nextStep()" disabled class="btn-primary text-white rounded-lg text-sm">
                            Selanjutnya
                        </button>

                        <button id="submit-btn" type="button" onclick="submitCurrent()" style="display:none"
                            class="btn-primary text-white rounded-lg text-sm">
                            Selesai & Kirim
                        </button>
                    </div>
                </div>
            </div>

            <!-- Hasil EPDS -->
            <div id="result-epds" class="mb-8 p-6 rounded-xl border border-gray-200 bg-gray-50 max-w-lg mx-auto shadow-md" style="display:none">
                <h3 class="text-lg font-semibold mb-4 text-center text-gray-700">Hasil Skrining EPDS Anda</h3>
                <div class="bg-white p-6 rounded-xl shadow-md text-center">
                    <p class="text-gray-600 text-lg mb-2">Skor Total Anda:</p>
                    <p id="res-score" class="text-6xl font-extrabold text-[#63b3ed] mb-6">0</p>
                    <p id="res-desc" class="text-gray-700 mb-6 text-sm">—</p>
                    <div class="grid gap-3">
                        <a href="{{ url('/materi/edukasi') }}" class="btn-primary w-full text-center">Lihat Materi Edukasi</a>
                        <a href="{{ url('/') }}" class="btn-secondary w-full text-center">Kembali ke Beranda</a>
                    </div>
                </div>
            </div>

            <!-- Hasil DASS -->
            <div id="result-dass" class="mb-8 p-6 rounded-xl border border-gray-200 bg-gray-50 max-w-2xl mx-auto shadow-md" style="display:none">
                <h3 class="text-lg font-semibold mb-4 text-center text-gray-700">Hasil Skrining DASS-21 Anda</h3>
                <div class="bg-white p-3 rounded-xl shadow-md">
                    <div class="grid md:grid-cols-3 gap-4 text-center">
                        <div class="p-2 rounded-lg border">
                            <p class="text-sm text-gray-500 mb-1">Depresi</p>
                            <p id="dass-dep-score" class="text-3xl font-bold">0</p>
                            <p id="dass-dep-level" class="text-xs mt-1 text-gray-600">—</p>
                        </div>
                        <div class="p-2 rounded-lg border">
                            <p class="text-sm text-gray-500 mb-1">Kecemasan</p>
                            <p id="dass-anx-score" class="text-3xl font-bold">0</p>
                            <p id="dass-anx-level" class="text-xs mt-1 text-gray-600">—</p>
                        </div>
                        <div class="p-2 rounded-lg border">
                            <p class="text-sm text-gray-500 mb-1">Stres</p>
                            <p id="dass-str-score" class="text-3xl font-bold">0</p>
                            <p id="dass-str-level" class="text-xs mt-1 text-gray-600">—</p>
                        </div>
                    </div>

                    <div class="mt-6 text-sm text-gray-700 space-y-3">
                        <p id="dass-summary">—</p>
                        <div class="grid gap-3 mt-3">
                            <a href="{{ url('/materi/edukasi') }}" class="btn-primary w-full text-center">Lihat Materi Edukasi</a>
                            <a href="{{ url('/') }}" class="btn-secondary w-full text-center">Kembali ke Beranda</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Hidden forms khusus EPDS -->
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

    <!-- Hidden forms khusus DASS -->
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

    <!-- Modal HPHT -->
    <x-modal name="request-create">
        <form id="formRequestUsiaHamil" method="post" action="{{ route('first.create.usia.hamil') }}" class="p-6">
            @csrf
            @method('post')
            <div class="mt-10 space-y-2">
                <label class="block text-gray-700 text-sm font-medium mb-2">Hari Pertama Haid Terakhir (HPHT)</label>
                <input type="date" class="input-field" name="hpht" value="{{ old('hpht') }}" required>
            </div>
            <div class="mt-6 flex justify-center">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-primary-button type="button" onclick="fSimpanUsiaHamil('formRequestUsiaHamil')" class="ms-3 btn-primary hover:text-white">
                    Simpan
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <!-- Modal pop-up rekomendasi DASS -->
    <x-modal name="dass-advice">
        <div class="p-6">
            <h4 class="text-lg font-semibold mb-2">Rekomendasi Tindak Lanjut</h4>
            <div id="dass-advice-body" class="text-sm text-gray-700 space-y-2"></div>
            <div class="mt-6 text-right">
                <x-primary-button x-on:click="$dispatch('close')">Tutup</x-primary-button>
            </div>
        </div>
    </x-modal>

    <x-slot name="scripts">
        <script data-swup-reload-script>
            window.SKRININGS = window.SKRININGS || {};
            SKRININGS.skrining = SKRININGS.skrining || {};

            // ==== Modal helpers ====

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

            // ==== Data pertanyaan (EPDS & DASS) ====
            if (!SKRININGS.skrining.QUESTIONS) {
                SKRININGS.skrining.QUESTIONS = {
                    epds: @js($epdsQuestions),
                    dass: @js($dassQuestions),
                };
            }
            var QUESTIONS = SKRININGS.skrining.QUESTIONS;

            // ==== State global ====
            SKRININGS.skrining.state = SKRININGS.skrining.state || {
                selectedSkrining: '',
                currentStep: 0,
                totalSteps: 0,
                sessionToken: null,
                answeredCount: 0,
                localAnswers: {}, // key: epds_id/dass_id -> jawaban_id
                batchNo: 1, // NEW: nomor putaran skrining pada trimester ini
            };
            S = SKRININGS.skrining.state;

            // ==== Helpers request EPDS ====
            async function startEpds() {
                const f = new Fetch("{{ route('epds.start') }}");
                f.method = 'GET';
                return await f.run();
            }
            async function postEpdsAnswer(epdsId, answersEpdsId) {
                const form = document.getElementById('formEpdsAnswer');
                form.querySelector('input[name="session_token"]').value = S.sessionToken || '';
                form.querySelector('input[name="epds_id"]').value = epdsId;
                form.querySelector('input[name="answers_epds_id"]').value = answersEpdsId;
                const f = new Fetch(form.action);
                f.method = 'POST';
                return await f.run('formEpdsAnswer');
            }
            async function postEpdsSubmit() {
                const form = document.getElementById('formEpdsSubmit');
                form.querySelector('input[name="session_token"]').value = S.sessionToken || '';
                const f = new Fetch(form.action);
                f.method = 'POST';
                return await f.run('formEpdsSubmit');
            }
            async function postEpdsCancel() {
                const form = document.getElementById('formEpdsCancel');
                form.querySelector('input[name="session_token"]').value = S.sessionToken || '';
                const f = new Fetch(form.action);
                f.method = 'POST';
                return await f.run('formEpdsCancel');
            }

            // ==== Helpers request DASS ====
            async function startDass() {
                const f = new Fetch("{{ route('dass.start') }}");
                f.method = 'GET';
                return await f.run();
            }
            async function postDassAnswer(dassId, answersDassId) {
                const f = new Fetch(document.getElementById('formDassAnswer').action);
                f.method = 'POST';
                const form = document.getElementById('formDassAnswer');
                form.session_token.value = S.sessionToken || '';
                form.dass_id.value = dassId;
                form.answers_dass_id.value = answersDassId;
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

            // ==== ENTRY: pilih jenis skrining ====
            window.selectSkrining = window.selectSkrining || (async function(type) {
                // reset state
                S.selectedSkrining = type;
                S.currentStep = 0;
                S.totalSteps = 0;
                S.sessionToken = null;
                S.answeredCount = 0;
                S.batchNo = 1; // NEW: reset
                Object.keys(S.localAnswers).forEach(k => delete S.localAnswers[k]);

                // sembunyikan hasil sebelumnya
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
                    S.totalSteps = (QUESTIONS.epds || []).length || 0;
                    S.batchNo = Number(res.data.batch_no || 1); // NEW
                    if (!S.totalSteps) {
                        ALERT('Pertanyaan EPDS kosong.', 'bad');
                        return;
                    }
                    S.currentStep = Math.min(Math.max(0, S.answeredCount), Math.max(0, S.totalSteps - 1));

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

                    S.sessionToken = res.data.session_token;
                    S.answeredCount = res.data.answered || 0;
                    S.totalSteps = (QUESTIONS.dass || []).length || 0;
                    S.batchNo = Number(res.data.batch_no || 1); // NEW
                    if (!S.totalSteps) {
                        ALERT('Pertanyaan DASS kosong.', 'bad');
                        return;
                    }
                    S.currentStep = Math.min(Math.max(0, S.answeredCount), Math.max(0, S.totalSteps - 1));

                } else {
                    ALERT('Jenis skrining tidak dikenal.', 'bad');
                    return;
                }

                document.getElementById('divSkrining')?.style && (document.getElementById('divSkrining').style.display = 'none');
                document.getElementById('form-skrining')?.style && (document.getElementById('form-skrining').style.display = 'block');

                SKRININGS.skrining.renderForm();
                SKRININGS.skrining.updateProgress();
            });

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

            // ==== Render 1 step ====
            SKRININGS.skrining.renderForm = function() {
                const wrap = document.getElementById('steps-container');
                if (!wrap || !S.selectedSkrining) return;
                wrap.innerHTML = '';

                const isEpds = S.selectedSkrining === 'epds';
                const data = isEpds ? (QUESTIONS.epds || []) : (QUESTIONS.dass || []);
                const q = data[S.currentStep];
                if (!q) return;

                const title = document.createElement('h2');
                title.className = 'font-semibold mb-4';
                title.textContent = isEpds ? `Pertanyaan EPDS #${S.currentStep+1}` : `Pertanyaan DASS #${S.currentStep+1}`;

                const qLabel = document.createElement('p');
                qLabel.className = 'text-gray-800 font-medium mb-4 capitalize';
                qLabel.textContent = q.pertanyaan;

                const list = document.createElement('div');
                list.className = 'space-y-2 grid grid-cols-1';

                const key = isEpds ? (q.epds_id ?? `epds_${S.currentStep}`) : (q.dass_id ?? `dass_${S.currentStep}`);

                (q.answers || []).forEach(ans => {
                    const row = document.createElement('label');
                    row.className = 'inline-flex items-center font-light text-sm';

                    const input = document.createElement('input');
                    input.type = 'radio';
                    input.className = 'form-radio text-[#14b8a6] h-4 w-4';
                    input.name = `q_${key}`;

                    const valueId = isEpds ? ans.answers_epds_id : ans.id;
                    input.value = valueId;
                    input.checked = (S.localAnswers[key] === valueId);

                    input.addEventListener('change', async () => {
                        S.localAnswers[key] = valueId;

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
                    });

                    const span = document.createElement('span');
                    span.className = 'ml-2 text-gray-700';
                    span.textContent = ans.jawaban;

                    row.appendChild(input);
                    row.appendChild(span);
                    list.appendChild(row);
                });

                wrap.appendChild(title);
                wrap.appendChild(qLabel);
                wrap.appendChild(list);

                SKRININGS.skrining.toggleButtons();
            };

            SKRININGS.skrining.updateProgress = function() {
                const pct = S.totalSteps ? Math.round(((S.currentStep + 1) / S.totalSteps) * 100) : 0;
                const bar = document.getElementById('progress-bar');
                if (bar) bar.style.width = pct + '%';
                const counter = document.getElementById('counter-text');
                if (counter) counter.textContent = `Pertanyaan ${Math.min(S.currentStep+1, S.totalSteps)} dari ${S.totalSteps}`;

                // NEW: tampilkan badge skrining ke-N bila batchNo > 1
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

                prev.textContent = (S.currentStep === 0) ? 'Cancel' : 'Sebelumnya';
                prev.disabled = false;

                const isEpds = S.selectedSkrining === 'epds';
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

            // ==== NAV / CANCEL ====
            window.nextStep = window.nextStep || function() {
                const isEpds = S.selectedSkrining === 'epds';
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
                            const res = await postEpdsCancel();
                            if (res?.ack !== 'ok') ALERT(res?.message || 'Gagal membatalkan sesi di server.', 'bad');
                        }
                        if (S.selectedSkrining === 'dass' && S.sessionToken) {
                            const res = await postDassCancel();
                            if (res?.ack !== 'ok') ALERT(res?.message || 'Gagal membatalkan sesi di server.', 'bad');
                        }
                    } catch (e) {
                        console.error('Cancel skrining error:', e);
                    } finally {
                        S.selectedSkrining = '';
                        S.currentStep = 0;
                        S.totalSteps = 0;
                        S.sessionToken = null;
                        S.answeredCount = 0;
                        S.batchNo = 1; // NEW: reset
                        Object.keys(S.localAnswers).forEach(k => delete S.localAnswers[k]);
                        const form = document.getElementById('form-skrining');
                        if (form) form.style.display = 'none';
                        const menu = document.getElementById('divSkrining');
                        if (menu) menu.style.display = 'block';
                        const bar = document.getElementById('progress-bar');
                        if (bar) bar.style.width = '0%';
                        const counter = document.getElementById('counter-text');
                        if (counter) counter.textContent = 'Pertanyaan 0 dari 0';
                        SKRININGS.skrining.toggleButtons();
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

            // ====== DASS: aturan skor & interpretasi ======
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
                const baseSum = {
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

                for (const no of DASS_GROUPS.depression) baseSum.depression += (scoreByNo[no] || 0);
                for (const no of DASS_GROUPS.anxiety) baseSum.anxiety += (scoreByNo[no] || 0);
                for (const no of DASS_GROUPS.stress) baseSum.stress += (scoreByNo[no] || 0);

                const sum = {
                    depression: baseSum.depression * 2,
                    anxiety: baseSum.anxiety * 2,
                    stress: baseSum.stress * 2,
                };
                const level = {
                    depression: severity('depression', sum.depression),
                    anxiety: severity('anxiety', sum.anxiety),
                    stress: severity('stress', sum.stress),
                };
                return {
                    sum,
                    level
                };
            }

            // ==== EPDS: tampilkan hasil ====
            SKRININGS.skrining.showEpdsResult = function(payload) {
                const form = document.getElementById('form-skrining');
                if (form) form.style.display = 'none';
                const box = document.getElementById('result-epds');

                const scoreEl = document.getElementById('res-score');
                const descEl = document.getElementById('res-desc');

                const total = Number(payload?.total_score ?? 0);
                const risk = payload?.risk_title || '—';
                const advice = payload?.advice || '';
                const n = Number(S.batchNo || payload?.batch_no || 1);
                const batchLabel = n > 1 ? ` (Skrining ke-${n})` : '';

                if (scoreEl) scoreEl.textContent = String(total);
                if (descEl) descEl.textContent = `${risk}${batchLabel} — ${advice}`;

                if (box) box.style.display = 'block';
            };

            // ==== DASS: tampilkan hasil + unduh + pop-up rekomendasi ====
            SKRININGS.skrining.showDassResult = function(payload) {
                const form = document.getElementById('form-skrining');
                if (form) form.style.display = 'none';

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
                if (flags?.anxiety_alert || sum.anxiety >= 12) msgs.push('Kecemasan: Silakan Anda bertemu dan konsultasi ke <b>Bidan</b> dan <b>Psikolog</b>.');
                if (flags?.depression_alert || sum.depression >= 15) msgs.push('Depresi: Sebaiknya Anda konsultasi ke <b>Psikolog</b>.');
                if (flags?.stress_alert || sum.stress >= 20) msgs.push('Stres: Sebaiknya Anda konsultasi ke <b>Dokter</b> dan <b>Psikolog</b>.');

                if (msgs.length) {
                    const box = document.getElementById('dass-advice-body');
                    if (box) {
                        box.innerHTML = msgs.map(m => `<p class="leading-relaxed">${m}</p>`).join('');
                        openModal('dass-advice');
                    }
                }

                document.getElementById('result-dass').style.display = 'block';
            };

            // ==== SUBMIT current ====
            window.submitCurrent = window.submitCurrent || (async function() {
                const isEpds = S.selectedSkrining === 'epds';
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

            // ==== Init per view ====
            function initSkriningView() {
                if (document.getElementById('form-skrining')) {
                    SKRININGS.skrining.updateProgress();
                    SKRININGS.skrining.toggleButtons();
                }
            }
            document.addEventListener('DOMContentLoaded', initSkriningView);
            document.addEventListener('swup:contentReplaced', initSkriningView);
        </script>
    </x-slot>
</x-app-layout>