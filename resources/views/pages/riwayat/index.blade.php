<x-app-layout>
    @section('page_title', 'Riwayat Skrining')
    <x-slot name="title">{{ $title ?? 'Riwayat Skrining' }}</x-slot>

    <x-header-back>Riwayat</x-header-back>

    {{-- Header + Filter (SINGLe) --}}
    <div class="bg-white rounded-2xl shadow p-4 sm:p-5 mb-4 sm:mb-6 mt-5 md:mt-0">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-semibold text-gray-800">Riwayat Skrining</h1>
                    <p class="text-sm text-gray-500">Hasil EPDS & DASS-21, bisa disaring.</p>
                </div>

                {{-- Server-side filter: Bulan (+ Puskesmas untuk superadmin) --}}
                @if(($role ?? 'user') !== 'user')
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-3 w-full sm:w-auto">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Bulan</label>
                        <select name="month" class="input-field w-full">
                            <option value="">Semua</option>
                            @foreach(($monthOptions ?? []) as $opt)
                            <option value="{{ $opt['value'] }}" @selected(($filters['month'] ?? '' )===$opt['value'])>
                                {{ $opt['label'] }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    @if(($role ?? '') === 'superadmin')
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Puskesmas</label>
                        <select name="puskesmas_id" class="input-field w-full">
                            <option value="">Semua Puskesmas</option>
                            @foreach(($puskesmasList ?? []) as $p)
                            <option value="{{ $p->id }}" @selected(($filters['puskesmas_id'] ?? null)==$p->id)>{{ $p->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="sm:self-end">
                        <button type="submit" class="btn btn-primary w-full sm:w-auto">Terapkan</button>
                    </div>
                </form>
                @endif
            </div>

            {{-- Client-side filter: Tahun / Tahap / Jenis --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-3 w-full sm:w-auto">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tahun</label>
                    <select id="filter-year" class="input-field w-full">
                        <option value="">Semua</option>
                        {{-- opsi tahun akan diisi via JS --}}
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tahap</label>
                    <select id="filter-term" class="input-field w-full">
                        <option value="">Semua</option>
                        <option value="trimester_1">Trimester I</option>
                        <option value="trimester_2">Trimester II</option>
                        <option value="trimester_3">Trimester III</option>
                        <option value="pasca_hamil">Pasca Hamil</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Jenis</label>
                    <select id="filter-kind" class="input-field w-full">
                        <option value="">Semua</option>
                        <option value="EPDS">EPDS</option>
                        <option value="DASS-21">DASS-21</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- ROOT: data items untuk JS --}}
    <div class="flex flex-wrap gap-2">
        <button type="button" id="btn-export-epds" class="btn btn-primary">Export EPDS</button>
        <button type="button" id="btn-export-dass" class="btn btn-secondary">Export DASS</button>
    </div>

    <div id="riwayat-root" class="max-w-7xl mx-auto p-4 sm:p-6"
        data-role="{{ $role ?? 'user' }}"
        data-usia='@json($usia_hamil)'
        data-items='@json($items)'>

        {{-- MOBILE cards --}}
        <div class="space-y-3 sm:hidden">
            <div id="mobile-empty" class="text-center text-gray-500 bg-white p-6 rounded-xl shadow hidden">Tidak ada data.</div>
            <div id="cards-mobile" class="space-y-3"></div>
        </div>

        {{-- DESKTOP table --}}
        <div class="hidden sm:block bg-white rounded-2xl shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Tanggal</th>
                            @if(($role ?? 'user') !== 'user')
                            <th class="px-4 py-3 text-left">Nama Ibu</th>
                            @endif
                            <th class="px-4 py-3 text-left">Usia Kehamilan</th>
                            <th class="px-4 py-3 text-left">Jenis</th>
                            <th class="px-4 py-3 text-left">Tahap</th>
                            <th class="px-4 py-3 text-left">Skor / Ringkasan</th>
                        </tr>
                    </thead>
                    <tbody id="table-body" class="divide-y">
                        <tr id="desktop-empty" class="hidden">
                            <td colspan="{{ (($role ?? 'user') !== 'user') ? 6 : 5 }}" class="px-4 py-6 text-center text-gray-500">
                                Tidak ada data.
                            </td>
                        </tr>
                        {{-- baris akan diisi via JS --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    {{-- JS murni, dieksekusi ulang oleh @swup/scripts-plugin --}}
    <x-slot name="scripts">
        <script data-swup-reload-script type="application/json" id="answer-epds">
            {!!$answer_epds -> toJson(JSON_UNESCAPED_UNICODE) !!}
        </script>
        <script data-swup-reload-script type="application/json" id="answer-dass">
            {!!$answer_dass -> toJson(JSON_UNESCAPED_UNICODE) !!}
        </script>
        <script data-swup-reload-script type="application/json" id="bank-dass">
            {!!$skrining_dass -> toJson(JSON_UNESCAPED_UNICODE) !!}
        </script>

        {{-- baru: data hasil untuk export --}}
        <script data-swup-reload-script type="application/json" id="hasil-epds">
            {!!$epds_export -> toJson(JSON_UNESCAPED_UNICODE) !!}
        </script>
        <script data-swup-reload-script type="application/json" id="hasil-dass">
            {!!$dass_export -> toJson(JSON_UNESCAPED_UNICODE) !!}
        </script>

        <script data-swup-reload-script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.min.js"></script>
        <script data-swup-reload-script>
            (function() {

                const TRIMESTER_LABELS = {
                    'trimester_1': 'Trimester I',
                    'trimester_2': 'Trimester II',
                    'trimester_3': 'Trimester III',
                    'pasca_hamil': 'Pasca Hamil'
                };

                function $(sel, parent = document) {
                    return parent.querySelector(sel);
                }

                function el(tag, cls) {
                    const n = document.createElement(tag);
                    if (cls) n.className = cls;
                    return n;
                }

                const root = document.getElementById('riwayat-root');
                if (!root) return;

                // Ambil data dari attribute
                const role = root.dataset.role || 'user';
                const isAdminView = role !== 'user';
                let raw = [];
                let usia = []
                try {
                    raw = JSON.parse(root.dataset.items || '[]') || [];
                    usia = JSON.parse(root.dataset.usia || '[]') || [];
                } catch {}

                const state = {
                    raw,
                    usia,
                    filters: {
                        year: '',
                        term: '',
                        kind: ''
                    },
                };

                // Elemen DOM
                const yearSel = $('#filter-year');
                const termSel = $('#filter-term');
                const kindSel = $('#filter-kind');
                const mobileWrap = $('#cards-mobile', root);
                const mobileEmpty = $('#mobile-empty', root);
                const tbody = $('#table-body', root);
                const desktopEmpty = $('#desktop-empty', root);

                function $id(id) {
                    return document.getElementById(id);
                }

                function readSeed(id) {
                    try {
                        return JSON.parse($id(id)?.textContent || '{}');
                    } catch (e) {
                        return {};
                    }
                }

                const epdsRows = readSeed('hasil-epds'); // hasil_epds + data_diri
                const dassRows = readSeed('hasil-dass'); // hasil_dass + data_diri
                const dassBank = readSeed('bank-dass'); // master 21 pertanyaan DASS
                const answerEpds = readSeed('answer-epds'); // master EPDS (punya relasi epds->pertanyaan)
                const answerDass = readSeed('answer-dass');

                function filterForExport(rows) {
                    const year = (document.getElementById('filter-year')?.value || '').trim();
                    const term = (document.getElementById('filter-term')?.value || '').trim(); // trimester_1/2/3/pasca_hamil
                    return (rows || []).filter(r => {
                        const y = String((r.screening_date || r.submitted_at || r.created_at || '').slice(0, 4));
                        if (year && y !== String(year)) return false;
                        if (term && (r.trimester || '') !== term) return false;
                        return true;
                    });
                }

                const btnE = $id('btn-export-epds');
                const btnD = $id('btn-export-dass');

                if (btnE) btnE.addEventListener('click', function() {
                    const month = new Date().getMonth().toString();
                    const name = buildFilename('Export_EPDS', month);
                    exportEpdsXlsx(epdsRows, answerEpds, name);
                });

                if (btnD) btnD.addEventListener('click', function() {
                    const month = new Date().getMonth().toString();
                    const name = buildFilename('Export_EPDS', month);
                    exportDassXlsx(filterForExport(dassRows), dassBank, answerDass, name);
                });

                function exportEpdsXlsx(epdsRows, answerEpds, fileName = 'Export_EPDS.xlsx') {
                    if (!Array.isArray(epdsRows) || epdsRows.length === 0) {
                        alert('Data EPDS kosong.');
                        return;
                    }
                    if (!Array.isArray(answerEpds) || answerEpds.length === 0) {
                        alert('Master jawaban EPDS kosong.');
                        return;
                    }

                    // ===== helpers =====
                    const by = (arr, key) => arr.reduce((m, x) => (m[x[key]] = x, m), {});
                    const groupBy = (arr, key) =>
                        arr.reduce((m, x) => ((m[x[key]] = m[x[key]] || []).push(x), m), {});
                    const safe = (v, d = '') => (v == null ? d : v);
                    const num = (v) => (typeof v === 'number' ? v : parseFloat(v) || 0);

                    // 1) Bangun bank pertanyaan dari answerEpds (kelompok per epds_id)
                    const byQuestion = Object.values(groupBy(answerEpds, 'epds_id'))
                        .map(list => {
                            // ambil pertanyaan dari relasi "epds" pada salah satu jawabannya jika ada
                            const first = list[0] || {};
                            const epdsId = first.epds_id;
                            const qText = safe(first?.epds?.pertanyaan, `Pertanyaan ${epdsId}`);
                            // urutkan opsi berdasarkan skor (3..0) agar sama seperti contoh
                            const opts = [...list].sort((a, b) => num(b.score) - num(a.score))
                                .map(o => `${o.score} : ${o.jawaban}`);
                            const headerText =
                                `${epdsId}. ${qText}\n` +
                                `Jawaban :\n` +
                                opts.join('\n');

                            return {
                                epds_id: epdsId,
                                header: headerText,
                                // bantu lookup jawaban: id -> score, id -> label
                                scoreByAnswerId: by(list, 'id'),
                            };
                        })
                        // urutkan kolom sesuai id pertanyaan
                        .sort((a, b) => (a.epds_id || 0) - (b.epds_id || 0));

                    const questionIds = byQuestion.map(q => q.epds_id);

                    // 2) Normalisasi baris hasil menjadi per-SKOR per pertanyaan (1 baris = 1 sesi)
                    // epdsRows mungkin:
                    //   a) sudah gabungan per sesi, dengan field "detail" berisi {epds_id, answers_epds_id, score}
                    //   b) per jawaban; maka kita kelompokkan via batch/session/submitted_at
                    const keyFor = (r) => r.batch_no || r.session_token || r.submitted_at || r.id || 'unknown';
                    const looksGrouped = epdsRows.some(r => Array.isArray(r.detail));

                    let sessions = [];
                    if (looksGrouped) {
                        sessions = epdsRows.map(r => ({
                            key: keyFor(r),
                            tanggal: safe(r.screening_date || r.submitted_at || r.created_at, ''),
                            ident: {
                                nama: safe(r?.ibu?.nama_lengkap || r?.nama_lengkap, ''),
                                kelurahan: safe(r?.ibu?.alamat_kelurahan || r?.alamat_kelurahan, ''),
                                kecamatan: safe(r?.ibu?.alamat_kecamatan || r?.alamat_kecamatan, ''),
                                rtrw: [safe(r?.ibu?.alamat_rt || r?.alamat_rt, ''), safe(r?.ibu?.alamat_rw || r?.alamat_rw, '')].filter(Boolean).join('/'),
                            },
                            answers: (r.detail || []).reduce((m, d) => {
                                const qid = d.epds_id;
                                const ansId = d.answers_epds_id;
                                const score = d.score != null ? d.score : (byQuestion.find(q => q.epds_id === qid)?.scoreByAnswerId[ansId]?.score);
                                m[qid] = {
                                    ansId,
                                    score: num(score)
                                };
                                return m;
                            }, {})
                        }));
                    } else {
                        // per-jawaban -> kelompokkan
                        const grouped = groupBy(epdsRows, x => keyFor(x));
                        sessions = Object.keys(grouped).map(k => {
                            const list = grouped[k];
                            const any = list[0] || {};
                            const tanggal = safe(any.screening_date || any.submitted_at || any.created_at, '');
                            const {
                                ibu_nama,
                                kelurahan_nama,
                                kecamatan_nama,
                                rt_rw,
                                alamat_rumah
                            } = any || {};
                            const ident = {
                                nama: ibu_nama || '',
                                kelurahan: kelurahan_nama || '',
                                kecamatan: kecamatan_nama || '',
                                rtrw: rt_rw || extractRTRW(alamat_rumah) || ''
                            };
                            const answers = {};
                            for (const row of list) {
                                const qid = row.epds_id;
                                const ansId = row.answers_epds_id;
                                // skor prioritas: row.score -> dari master jawaban
                                let score = row.score;
                                if (score == null) {
                                    const q = byQuestion.find(q => q.epds_id === qid);
                                    const meta = q?.scoreByAnswerId?.[ansId];
                                    score = meta?.score;
                                }
                                answers[qid] = {
                                    ansId,
                                    score: num(score)
                                };
                            }
                            return {
                                key: k,
                                tanggal,
                                ident,
                                answers
                            };
                        });
                    }

                    // 3) Susun AOA (array of arrays) untuk SheetJS
                    const AOA = [];
                    const HEADERS_IDENT = [
                        'Tgl Skrining',
                        'Nama Lengkap',
                        'ALAMAT KELURAHAN',
                        'ALAMAT KECAMATAN',
                        'ALAMAT RT/RW'
                    ];
                    const HEADERS_Q = byQuestion.map(q => q.header);
                    const HEADERS_TAIL = ['JUMLAH', 'KATEGORI'];

                    AOA.push([...HEADERS_IDENT, ...HEADERS_Q, ...HEADERS_TAIL]);

                    function kategoriFromTotal(total, q10Score) {
                        // ambang umum EPDS; silakan sesuaikan di sini bila perlu
                        if (num(q10Score) > 0) return 'Waspada: Pikiran Menyakiti Diri';
                        if (total >= 13) return 'Kemungkinan Depresi (Tinggi)';
                        if (total >= 10) return 'Kemungkinan Depresi (Sedang)';
                        return 'Tidak Ada Risiko Depresi';
                        // (Contoh di gambar: total 5 → "Tidak Ada Risiko Depresi")
                    }

                    for (const s of sessions) {
                        const colsIdent = [
                            safe(s.tanggal, ''),
                            safe(s.ident?.nama, ''),
                            safe(s.ident?.kelurahan, ''),
                            safe(s.ident?.kecamatan, ''),
                            safe(s.ident?.rtrw, ''),
                        ];

                        const scores = questionIds.map(qid => num(s.answers?.[qid]?.score));
                        const total = scores.reduce((a, b) => a + num(b), 0);
                        const q10 = s.answers?.[10]?.score ?? s.answers?.[questionIds[9]]?.score; // fallback jika id bukan 10
                        const kategori = kategoriFromTotal(total, q10);

                        AOA.push([...colsIdent, ...scores, total, kategori]);
                    }

                    // 4) SheetJS: buat workbook + styling
                    const wb = XLSX.utils.book_new();
                    const ws = XLSX.utils.aoa_to_sheet(AOA);

                    // Lebar kolom (mendekati contoh)
                    const questionColWidth = 48; // cukup lebar untuk wrap pertanyaan+opsi
                    ws['!cols'] = [{
                            wch: 18
                        }, // tgl
                        {
                            wch: 28
                        }, // nama
                        {
                            wch: 22
                        }, // kelurahan
                        {
                            wch: 20
                        }, // kecamatan
                        {
                            wch: 12
                        }, // RT/RW
                        ...byQuestion.map(() => ({
                            wch: questionColWidth
                        })),
                        {
                            wch: 10
                        }, // JUMLAH
                        {
                            wch: 28
                        }, // KATEGORI
                    ];

                    // Tinggi baris header agar teks wrap nyaman
                    ws['!rows'] = [{
                        hpt: 120
                    }]; // ~120pt untuk header

                    // Style header: ungu, bold, center + wrap
                    const headerFill = {
                        fgColor: {
                            rgb: 'B4A7D6'
                        }
                    }; // light purple
                    const white = {
                        rgb: 'FFFFFF'
                    };
                    const headerCount = AOA[0].length;

                    for (let c = 0; c < headerCount; c++) {
                        const cell = XLSX.utils.encode_cell({
                            r: 0,
                            c
                        });
                        ws[cell] = ws[cell] || {};
                        ws[cell].v = ws[cell].v == null ? '' : ws[cell].v;
                        ws[cell].s = {
                            font: {
                                bold: true,
                                color: white
                            },
                            fill: headerFill,
                            alignment: {
                                horizontal: 'center',
                                vertical: 'top',
                                wrapText: true
                            },
                            border: {
                                top: {
                                    style: 'thin',
                                    color: white
                                },
                                bottom: {
                                    style: 'thin',
                                    color: white
                                },
                                left: {
                                    style: 'thin',
                                    color: white
                                },
                                right: {
                                    style: 'thin',
                                    color: white
                                },
                            }
                        };
                    }

                    // Format angka kolom skor & total jadi integer "0"
                    for (let r = 1; r < AOA.length; r++) {
                        // skor dimulai setelah 5 kolom identitas
                        let cStart = HEADERS_IDENT.length;
                        let cEnd = cStart + byQuestion.length; // eksklusif tail
                        for (let c = cStart; c < cEnd + 1; c++) { // +1 untuk kolom JUMLAH
                            const addr = XLSX.utils.encode_cell({
                                r,
                                c
                            });
                            if (ws[addr]) ws[addr].z = '0';
                        }
                    }
                    styleTable(ws, AOA, 'B4A7D6');
                    XLSX.utils.book_append_sheet(wb, ws, 'EPDS');
                    XLSX.writeFile(wb, fileName);
                }

                function exportDassXlsx(dassRows, skriningDass, answerDass, fileName = 'Export_DASS.xlsx') {
                    if (!Array.isArray(dassRows) || dassRows.length === 0) {
                        alert('Data DASS kosong.');
                        return;
                    }
                    if (!Array.isArray(skriningDass) || skriningDass.length === 0) {
                        alert('Master pertanyaan DASS kosong.');
                        return;
                    }
                    // answerDass boleh kosong—header tetap terbentuk dari skriningDass

                    // helpers
                    const groupBy = (arr, key) => arr.reduce((m, x) => ((m[x[key]] = m[x[key]] || []).push(x), m), {});
                    const safe = (v, d = '') => (v == null ? d : v);
                    const num = (v) => (typeof v === 'number' ? v : parseFloat(v) || 0);

                    // 1) Header per pertanyaan: ambil teks dari skrining_dass + (opsional) daftar opsi dari answer_dass
                    const ansG = groupBy(answerDass || [], 'dass_id'); // {dass_id: [opsi]}
                    const questionIds = [...skriningDass].map(q => q.id).sort((a, b) => a - b);

                    const byQuestion = questionIds.map(id => {
                        const q = (skriningDass.find(x => x.id === id) || {});
                        const opts = (ansG[id] || [])
                            .sort((a, b) => num(b.score) - num(a.score))
                            .map(o => `${o.score} : ${o.jawaban}`);
                        const header =
                            `${id}. ${safe(q.pertanyaan, 'Pertanyaan '+id)}\n` +
                            (opts.length ? `Jawaban :\n${opts.join('\n')}` : '');
                        // map id jawaban -> skor (kalau ada)
                        const scoreMap = (ansG[id] || []).reduce((m, a) => {
                            m[a.id] = a.score;
                            return m;
                        }, {});
                        return {
                            id,
                            header,
                            scoreMap
                        };
                    });

                    // 2) Group per sesi (session_token/batch/submitted_at)
                    const keyFor = (r) => r.batch_no || r.session_token || r.submitted_at || r.id || 'unknown';
                    const grouped = groupBy(dassRows, (r) => keyFor(r));

                    // mapping item → subskala (DASS-21 standar)
                    const DEP = new Set([3, 5, 10, 13, 16, 17, 21]);
                    const ANX = new Set([2, 4, 7, 9, 15, 19, 20]);
                    const STR = new Set([1, 6, 8, 11, 12, 14, 18]);

                    const sessions = Object.keys(grouped).map(k => {
                        const list = grouped[k];
                        const any = list[0] || {};
                        const {
                            ibu_nama,
                            kelurahan_nama,
                            kecamatan_nama,
                            rt_rw,
                            alamat_rumah
                        } = any || {};
                        const ident = {
                            nama: ibu_nama || '',
                            kelurahan: kelurahan_nama || '',
                            kecamatan: kecamatan_nama || '',
                            rtrw: rt_rw || extractRTRW(alamat_rumah) || ''
                        };
                        const answers = {};
                        let dep = any.total_depression ?? null;
                        let anx = any.total_anxiety ?? null;
                        let str = any.total_stress ?? null;

                        for (const row of list) {
                            const qid = row.dass_id;
                            const ansId = row.answers_dass_id;
                            // pakai score dari master opsi bila baris tidak punya kolom score
                            let score = row.score;
                            if (score == null && byQuestion.find(q => q.id === qid)?.scoreMap?.[ansId] != null) {
                                score = byQuestion.find(q => q.id === qid).scoreMap[ansId];
                            }
                            if (qid != null) answers[qid] = {
                                ansId,
                                score: num(score)
                            };

                            // simpan total dari baris ringkasan (answers_dass_id null) jika ada
                            if (row.answers_dass_id == null) {
                                if (row.total_depression != null) dep = row.total_depression;
                                if (row.total_anxiety != null) anx = row.total_anxiety;
                                if (row.total_stress != null) str = row.total_stress;
                            }
                        }

                        // fallback: hitung dari jawaban (×2, sesuai kategori yang kamu pakai di UI)
                        if (dep == null || anx == null || str == null) {
                            let sDep = 0,
                                sAnx = 0,
                                sStr = 0;
                            for (const qid of questionIds) {
                                const sc = num(answers[qid]?.score);
                                if (DEP.has(qid)) sDep += sc;
                                if (ANX.has(qid)) sAnx += sc;
                                if (STR.has(qid)) sStr += sc;
                            }
                            dep = dep ?? (sDep * 2);
                            anx = anx ?? (sAnx * 2);
                            str = str ?? (sStr * 2);
                        }

                        return {
                            tanggal: safe(any.screening_date || any.submitted_at || any.created_at, ''),
                            trimester: any.trimester || '',
                            ident,
                            answers,
                            totals: {
                                dep,
                                anx,
                                str
                            }
                        };
                    });

                    // 3) AOA
                    const HEADERS_IDENT = ['Tgl Skrining', 'Nama Lengkap', 'ALAMAT KELURAHAN', 'ALAMAT KECAMATAN', 'ALAMAT RT/RW'];
                    const HEADERS_Q = byQuestion.map(q => q.header);
                    const HEADERS_TAIL = ['DEP', 'ANX', 'STR', 'KAT DEP', 'KAT ANX', 'KAT STR'];

                    const AOA = [
                        [...HEADERS_IDENT, ...HEADERS_Q, ...HEADERS_TAIL]
                    ];

                    // label kategori (pakai ambang yang sama dengan UI kamu)
                    const dassDepLabel = (n) => (n <= 9 ? 'Normal' : n <= 13 ? 'Ringan' : n <= 20 ? 'Sedang' : n <= 27 ? 'Berat' : 'Sangat Berat');
                    const dassAnxLabel = (n) => (n <= 7 ? 'Normal' : n <= 9 ? 'Ringan' : n <= 14 ? 'Sedang' : n <= 19 ? 'Berat' : 'Sangat Berat');
                    const dassStrLabel = (n) => (n <= 14 ? 'Normal' : n <= 18 ? 'Ringan' : n <= 25 ? 'Sedang' : n <= 33 ? 'Berat' : 'Sangat Berat');

                    for (const s of sessions) {
                        const idt = s.ident || {};
                        const colsIdent = [
                            safe(s.tanggal, ''),
                            safe(idt.nama, ''),
                            safe(idt.kelurahan, ''),
                            safe(idt.kecamatan, ''),
                            safe(idt.rtrw, ''),
                        ];
                        const scores = questionIds.map(qid => num(s.answers?.[qid]?.score));
                        const dep = num(s.totals.dep);
                        const anx = num(s.totals.anx);
                        const str = num(s.totals.str);
                        AOA.push([...colsIdent, ...scores, dep, anx, str, dassDepLabel(dep), dassAnxLabel(anx), dassStrLabel(str)]);
                    }

                    // 4) Excel + styling (sama seperti EPDS)
                    const wb = XLSX.utils.book_new();
                    const ws = XLSX.utils.aoa_to_sheet(AOA);

                    const questionColWidth = 48;
                    ws['!cols'] = [{
                            wch: 18
                        }, {
                            wch: 28
                        }, {
                            wch: 22
                        }, {
                            wch: 20
                        }, {
                            wch: 12
                        },
                        ...byQuestion.map(() => ({
                            wch: questionColWidth
                        })),
                        {
                            wch: 8
                        }, {
                            wch: 8
                        }, {
                            wch: 8
                        }, {
                            wch: 12
                        }, {
                            wch: 12
                        }, {
                            wch: 12
                        }
                    ];
                    ws['!rows'] = [{
                        hpt: 120
                    }];

                    const headerFill = {
                        fgColor: {
                            rgb: 'B4A7D6'
                        }
                    };
                    const white = {
                        rgb: 'FFFFFF'
                    };
                    const headerCount = AOA[0].length;
                    for (let c = 0; c < headerCount; c++) {
                        const addr = XLSX.utils.encode_cell({
                            r: 0,
                            c
                        });
                        ws[addr] = ws[addr] || {};
                        ws[addr].s = {
                            font: {
                                bold: true,
                                color: white
                            },
                            fill: headerFill,
                            alignment: {
                                horizontal: 'center',
                                vertical: 'top',
                                wrapText: true
                            }
                        };
                    }

                    // format angka untuk kolom skor & totals
                    for (let r = 1; r < AOA.length; r++) {
                        let cStart = HEADERS_IDENT.length;
                        let cEnd = cStart + byQuestion.length + 3; // +DEP+ANX+STR
                        for (let c = cStart; c < cEnd; c++) {
                            const a = XLSX.utils.encode_cell({
                                r,
                                c
                            });
                            if (ws[a]) ws[a].z = '0';
                        }
                    }

                    styleTable(ws, AOA, 'B4A7D6');
                    XLSX.utils.book_append_sheet(wb, ws, 'DASS-21');
                    XLSX.writeFile(wb, fileName);
                }

                function extractRTRW(alamat) {
                    const text = String(alamat || '');

                    // 1) RT ... RW ... (urutan normal)
                    let m = text.match(/RT\D*(\d{1,3})\D*RW\D*(\d{1,3})/i);
                    if (m) {
                        const rt = String(m[1]).padStart(2, '0');
                        const rw = String(m[2]).padStart(2, '0');
                        return `${rt}/${rw}`;
                    }

                    // 2) RW ... RT ... (urutan terbalik)
                    m = text.match(/RW\D*(\d{1,3})\D*RT\D*(\d{1,3})/i);
                    if (m) {
                        const rt = String(m[2]).padStart(2, '0');
                        const rw = String(m[1]).padStart(2, '0');
                        return `${rt}/${rw}`;
                    }

                    // 3) Tangkap terpisah (termasuk "Rukun Tetangga"/"Rukun Warga")
                    const rt = (text.match(/R(?:T|ukun\s*Tetangga)\D*(\d{1,3})/i) || [])[1];
                    const rw = (text.match(/R(?:W|ukun\s*Warga)\D*(\d{1,3})/i) || [])[1];
                    if (rt || rw) {
                        const rt2 = rt ? String(rt).padStart(2, '0') : '';
                        const rw2 = rw ? String(rw).padStart(2, '0') : '';
                        return `${rt2}/${rw2}`.replace(/^\/|\/$/g, ''); // buang slash kalau salah satu kosong
                    }

                    // Tidak ditemukan
                    return '';
                }

                function currentTimestamp(tz = 'Asia/Jakarta') {
                    const pad = n => String(n).padStart(2, '0');
                    const now = new Date();
                    const jkt = new Date(now.toLocaleString('en-US', { timeZone: tz }));
                    const y = jkt.getFullYear();
                    const m = pad(jkt.getMonth() + 1);
                    const d = pad(jkt.getDate());
                    const hh = pad(jkt.getHours());
                    const mm = pad(jkt.getMinutes());
                    const ss = pad(jkt.getSeconds());
                    return `${y}${m}${d}_${hh}${mm}${ss}`; // contoh: 20250903_213045
                }

                function buildFilename(base, month) {
                    const stamp = currentTimestamp('Asia/Jakarta');
                    return `${base}${month ? ('_' + month) : ''}_${stamp}.xlsx`;
                }

                // === header ungu + border semua sel (butuh xlsx-js-style) ===
                function styleTable(ws, AOA, headerBg = 'B4A7D6') {
                    const borderThin = {
                        style: 'thin',
                        color: {
                            rgb: 'BDBDBD'
                        }
                    };
                    const ref = ws['!ref'] || `A1:${XLSX.utils.encode_cell({
                        r: (AOA.length ? AOA.length - 1 : 0),
                        c: (AOA[0]?.length ? AOA[0].length - 1 : 0)
                    })}`;
                    const range = XLSX.utils.decode_range(ref);

                    for (let R = range.s.r; R <= range.e.r; R++) {
                        for (let C = range.s.c; C <= range.e.c; C++) {
                            const addr = XLSX.utils.encode_cell({
                                r: R,
                                c: C
                            });
                            ws[addr] = ws[addr] || {};
                            ws[addr].s = ws[addr].s || {};

                            // border semua sel
                            ws[addr].s.border = {
                                top: borderThin,
                                right: borderThin,
                                bottom: borderThin,
                                left: borderThin
                            };

                            if (R === 0) {
                                // header
                                ws[addr].s.font = {
                                    bold: false,
                                    color: {
                                        rgb: 'black'
                                    }
                                };
                                ws[addr].s.fill = {
                                    patternType: 'solid',
                                    fgColor: {
                                        rgb: headerBg
                                    }
                                };
                                ws[addr].s.alignment = {
                                    vertical: 'top',
                                    wrapText: true
                                };
                            } else {
                                // body
                                ws[addr].s.alignment = Object.assign({
                                    vertical: 'top'
                                }, ws[addr].s.alignment || {});
                            }
                        }
                    }
                }


                // Helpers kategori
                const epdsSeverity = (total) => (Number(total ?? 0) >= 13) ? 'Risiko depresi (≥13)' : 'Tidak signifikan';
                const dassDepLabel = (s) => {
                    const n = Number(s ?? -1);
                    if (n < 0) return '—';
                    if (n <= 9) return 'Normal';
                    if (n <= 13) return 'Ringan';
                    if (n <= 20) return 'Sedang';
                    if (n <= 27) return 'Berat';
                    return 'Sangat Berat';
                };
                const dassAnxLabel = (s) => {
                    const n = Number(s ?? -1);
                    if (n < 0) return '—';
                    if (n <= 7) return 'Normal';
                    if (n <= 9) return 'Ringan';
                    if (n <= 14) return 'Sedang';
                    if (n <= 19) return 'Berat';
                    return 'Sangat Berat';
                };
                const dassStrLabel = (s) => {
                    const n = Number(s ?? -1);
                    if (n < 0) return '—';
                    if (n <= 14) return 'Normal';
                    if (n <= 18) return 'Ringan';
                    if (n <= 25) return 'Sedang';
                    if (n <= 33) return 'Berat';
                    return 'Sangat Berat';
                };
                const badgeColor = (label) => {
                    switch ((label || '').toLowerCase()) {
                        case 'normal':
                            return 'text-emerald-600';
                        case 'ringan':
                            return 'text-amber-600';
                        case 'sedang':
                            return 'text-orange-600';
                        case 'berat':
                            return 'text-red-600';
                        case 'sangat berat':
                            return 'text-rose-700';
                        default:
                            return 'text-gray-500';
                    }
                };

                // Populate opsi tahun
                const years = Array.from(new Set(state.raw.map(r => r.year).filter(Boolean))).sort((a, b) => b - a);
                if (yearSel) {
                    years.forEach(y => {
                        const opt = el('option');
                        opt.value = y;
                        opt.textContent = y;
                        yearSel.appendChild(opt);
                    });
                }

                // Listeners filter
                if (yearSel) {
                    yearSel.addEventListener('change', () => {
                        state.filters.year = yearSel.value;
                        render();
                    });
                }
                if (termSel) {
                    termSel.addEventListener('change', () => {
                        state.filters.term = termSel.value;
                        render();
                    });
                }
                if (kindSel) {
                    kindSel.addEventListener('change', () => {
                        state.filters.kind = kindSel.value;
                        render();
                    });
                }

                function filtered() {
                    return state.raw.filter(r => {
                        if (state.filters.year && String(r.year) !== String(state.filters.year)) return false;
                        if (state.filters.term && (r.trimester ?? '') !== state.filters.term) return false;
                        if (state.filters.kind && r.type !== state.filters.kind) return false;
                        return true;
                    });
                }

                function renderMobile(list) {
                    mobileWrap.innerHTML = '';
                    if (!list.length) {
                        mobileEmpty.classList.remove('hidden');
                        return;
                    }
                    mobileEmpty.classList.add('hidden');

                    list.forEach(row => {
                        const card = el('div', 'bg-white rounded-xl shadow p-4 flex flex-col gap-2');

                        let nama = "";
                        if (isAdminView) {
                            nama = el('div', 'flex items-start justify-start');
                            const badgeNama = el('span', 'px-2 py-0.5 rounded text-base font-senibold');
                            badgeNama.textContent = row.ibu;
                            nama.append(badgeNama);
                        }

                        const top = el('div', 'flex items-center justify-between');
                        const badge = el('span', 'px-2 py-0.5 rounded text-xs font-medium ' + (row.type === 'EPDS' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700'));
                        badge.textContent = row.type;
                        const date = el('span', 'text-sm text-gray-500');
                        date.textContent = row.date_human || '—';
                        top.append(badge, date);

                        const body = el('div', 'text-sm text-gray-700');
                        const tri = el('span', 'text-xs px-2 py-0.5 rounded bg-amber-50 text-amber-700 font-medium');
                        tri.textContent = [TRIMESTER_LABELS[row.trimester] ?? '—', row.usia_hamil?.keterangan ?? '—'].join(' | ');
                        body.appendChild(tri);

                        if (row.type === 'EPDS') {
                            const b = el('div', 'mt-2');
                            const t = el('div', 'font-semibold');
                            t.innerHTML = `Skor EPDS: <span>${row.scores?.epds_total ?? '—'}</span>`;
                            const s = el('div', 'text-xs mt-0.5 ' + ((row.scores?.epds_total ?? 0) >= 13 ? 'text-red-600' : 'text-emerald-600'));
                            s.textContent = epdsSeverity(row.scores?.epds_total);
                            b.append(t, s);
                            body.appendChild(b);
                        } else if (row.type === 'DASS-21') {
                            const b = el('div', 'mt-2 space-y-1');

                            const mk = (label, val, cat) => {
                                const r1 = el('div', 'flex items-center justify-between');
                                const l1 = el('span', 'text-gray-600');
                                l1.textContent = label;
                                const v1 = el('span', 'font-semibold');
                                v1.textContent = val ?? '—';
                                r1.append(l1, v1);
                                const r2 = el('div', 'text-xs ' + badgeColor(cat(val)));
                                r2.textContent = cat(val);
                                b.append(r1, r2);
                            };

                            mk('Depresi', row.scores?.dep, dassDepLabel);
                            mk('Kecemasan', row.scores?.anx, dassAnxLabel);
                            mk('Stres', row.scores?.stress, dassStrLabel);

                            body.appendChild(b);
                        }

                        card.append(nama, top, body);
                        mobileWrap.appendChild(card);
                    });
                }

                function renderDesktop(list) {
                    // clear rows (kecuali row "empty")
                    [...tbody.querySelectorAll('tr')].forEach(tr => {
                        if (tr.id !== 'desktop-empty') tr.remove();
                    });

                    if (!list.length) {
                        desktopEmpty.classList.remove('hidden');
                        return;
                    }
                    desktopEmpty.classList.add('hidden');

                    list.forEach(row => {
                        const tr = el('tr', 'hover:bg-gray-50');

                        const tdDate = el('td', 'px-4 py-3');
                        tdDate.textContent = row.date_human || '—';

                        // Nama Ibu (hanya admin/superadmin)
                        let tdIbu = null;
                        if (isAdminView) {
                            tdIbu = el('td', 'px-4 py-3');
                            tdIbu.textContent = row.ibu || '—';
                        }

                        const tdUsia = el('td', 'px-4 py-3 capitalize');
                        const us = el('span', 'text-xs px-2 py-0.5 rounded bg-amber-50 text-amber-700 font-medium');
                        us.textContent = row.usia_hamil?.keterangan || '—';
                        tdUsia.appendChild(us);

                        const tdType = el('td', 'px-4 py-3');
                        const tBadge = el('span', 'px-2 py-0.5 rounded text-xs font-medium ' + (row.type === 'EPDS' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700'));
                        tBadge.textContent = row.type;
                        tdType.appendChild(tBadge);

                        const tdTerm = el('td', 'px-4 py-3');
                        const tri = el('span', 'text-xs px-2 py-0.5 rounded bg-amber-50 text-amber-700 font-medium');
                        tri.textContent = TRIMESTER_LABELS[row.trimester] ?? '—';
                        tdTerm.appendChild(tri);

                        const tdSum = el('td', 'px-4 py-3');
                        if (row.type === 'EPDS') {
                            const wrap = el('div');
                            const score = row.scores?.epds_total ?? '—';
                            const cat = (row.scores?.epds_total ?? 0) >= 13 ? 'text-red-600' : 'text-emerald-600';
                            wrap.innerHTML = `<span class="font-semibold">EPDS:</span> <span>${score}</span> <span class="ml-2 text-xs ${cat}">${epdsSeverity(row.scores?.epds_total)}</span>`;
                            tdSum.appendChild(wrap);
                        } else {
                            const wrap = el('div', 'space-x-4');
                            const dep = row.scores?.dep,
                                anx = row.scores?.anx,
                                str = row.scores?.stress;
                            const part = (label, val, catFn) => {
                                const em = el('em', 'text-xs ' + badgeColor(catFn(val)));
                                em.textContent = catFn(val);
                                const span = el('span');
                                span.innerHTML = `<b>${label}:</b> <span>${val ?? '—'}</span> `;
                                span.appendChild(em);
                                return span;
                            };
                            wrap.append(part('DEP', dep, dassDepLabel), part('ANX', anx, dassAnxLabel), part('STR', str, dassStrLabel));
                            tdSum.appendChild(wrap);
                        }

                        // Urutan kolom harus match header:
                        tr.append(tdDate);
                        if (isAdminView) tr.append(tdIbu);
                        tr.append(tdUsia, tdType, tdTerm, tdSum);

                        tbody.appendChild(tr);
                    });

                }

                function render() {
                    const list = filtered();
                    renderMobile(list);
                    renderDesktop(list);
                }

                // Init pertama
                render();

            })();
        </script>
    </x-slot>
</x-app-layout>