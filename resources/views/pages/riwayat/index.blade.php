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
                @php
                    $grid = "sm:grid-cols-4";
                    if($role === 'admin_facility'){
                        $grid = "sm:grid-cols-3";
                    }
                @endphp
                @if(($role ?? 'user') !== 'user')
                <form method="GET" class="grid grid-cols-1 {{ $grid }} gap-2 sm:gap-3 w-full sm:w-auto">
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

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Mode DASS</label>
                        <select name="mode" class="input-field w-full">
                            <option value="kehamilan" @selected(($filters['jenis'] ?? '') === 'kehamilan')>Kehamilan</option>
                            <option value="umum" @selected(($filters['jenis'] ?? '') === 'umum')>Umum</option>
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
                        <button type="submit" class="btn btn-primary w-full w-auto py-6">Terapkan</button>
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
                        <option value="pasca_hamil">Pasca Melahirkan</option>
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
    @if (Auth::user()->role_id !== 1)
    <div class="flex flex-wrap gap-2">
        <button type="button" id="btn-export-epds" class="btn btn-primary">Export EPDS</button>
        <button type="button" id="btn-export-dass" class="btn btn-secondary">Export DASS</button>
    </div>
    @endif

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
                            <th class="px-4 py-3 text-left">Mode</th>
                            <th class="px-4 py-3 text-left">Tahap</th>
                            <th class="px-4 py-3 text-left">Skor / Ringkasan</th>
                        </tr>
                    </thead>
                    <tbody id="table-body" class="divide-y">
                        <tr id="desktop-empty" class="hidden">
                            <td colspan="{{ (($role ?? 'user') !== 'user') ? 7 : 6 }}" class="px-4 py-6 text-center text-gray-500">
                                Tidak ada data.
                            </td>
                        </tr>
                        {{-- baris akan diisi via JS --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <x-slot name="scripts">
        <script type="application/json" id="answer-epds">
            {!!$answer_epds->toJson(JSON_UNESCAPED_UNICODE) !!}
        </script>
        <script type="application/json" id="answer-dass">
            {!!$answer_dass->toJson(JSON_UNESCAPED_UNICODE) !!}
        </script>
        <script type="application/json" id="bank-dass">
            {!!$skrining_dass->toJson(JSON_UNESCAPED_UNICODE) !!}
        </script>

        {{-- baru: data hasil untuk export --}}
        <script type="application/json" id="hasil-epds">
            {!!$epds_detail->toJson(JSON_UNESCAPED_UNICODE) !!}
        </script>
        <script type="application/json" id="hasil-dass">
            {!!$dass_detail->toJson(JSON_UNESCAPED_UNICODE) !!}
        </script>

        <script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.min.js"></script>

        <script>
            (function() {

                const TRIMESTER_LABELS = {
                    'trimester_1': 'Trimester I',
                    'trimester_2': 'Trimester II',
                    'trimester_3': 'Trimester III',
                    'pasca_hamil': 'Pasca Melahirkan'
                };

                const JENIS_LABELS = {
                    'kehamilan': 'Kehamilan',
                    'umum': 'Umum'
                };

                const JENIS_COLORS = {
                    'kehamilan': 'bg-pink-50 text-pink-700',
                    'umum': 'bg-blue-50 text-blue-700'
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

                const epdsRows = readSeed('hasil-epds');
                const dassRows = readSeed('hasil-dass');
                const dassBank = readSeed('bank-dass');
                const answerEpds = readSeed('answer-epds');
                const answerDass = readSeed('answer-dass');

                function filterForExport(rows) {
                    const year = (document.getElementById('filter-year')?.value || '').trim();
                    const term = (document.getElementById('filter-term')?.value || '').trim();
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
                    const name = buildFilename('Export_DASS', month);
                    exportDassXlsx(filterForExport(dassRows), dassBank, answerDass, name);
                });

                // Helper functions (unchanged from original)
                const by = (arr, key) => arr.reduce((m, x) => (m[x[key]] = x, m), {});
                const groupBy = (arr, key) => arr.reduce((m, x) => ((m[key(x)] = m[key(x)] || []).push(x), m), {});
                const safe = (v, d = '') => (v == null ? d : v);
                const num = (v) => (typeof v === 'number' ? v : parseFloat(v) || 0);

                // Category helpers
                const epdsSeverity = (total) => (Number(total ?? 0) >= 13) ? 'Gangguan suasana hati (≥13)' : 'Tidak signifikan';
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

                // Populate year options
                const years = Array.from(new Set(state.raw.map(r => r.year).filter(Boolean))).sort((a, b) => b - a);
                if (yearSel) {
                    years.forEach(y => {
                        const opt = el('option');
                        opt.value = y;
                        opt.textContent = y;
                        yearSel.appendChild(opt);
                    });
                }

                // Filter event listeners
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
                            const badgeNama = el('span', 'px-2 py-0.5 rounded text-base font-semibold');
                            badgeNama.textContent = row.ibu;
                            nama.append(badgeNama);
                        }

                        const top = el('div', 'flex items-center justify-between');
                        const badge = el('span', 'px-2 py-0.5 rounded text-xs font-medium ' + (row.type === 'EPDS' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700'));
                        badge.textContent = row.type;
                        const date = el('span', 'text-sm text-gray-500');
                        date.textContent = row.date_human || '—';
                        top.append(badge, date);

                        if (row.batch_no && row.batch_no > 1) {
                            const batchBadge = el('span', 'text-xs px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 font-medium ml-2');
                            batchBadge.textContent = `Ulang #${row.batch_no}`;
                            badge.parentNode.appendChild(batchBadge);
                        }

                        const body = el('div', 'text-sm text-gray-700');
                        
                        // Mode badge for DASS-21
                        if (row.type === 'DASS-21' && row.jenis) {
                            const modeBadge = el('span', 'text-xs px-2 py-0.5 rounded font-medium mr-2 ' + (JENIS_COLORS[row.jenis] || 'bg-gray-50 text-gray-700'));
                            modeBadge.textContent = JENIS_LABELS[row.jenis] || row.jenis;
                            body.appendChild(modeBadge);
                        }

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
                        tdDate.textContent = format_tanggal(row.date_iso) || '—';

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

                        if (row.batch_no && row.batch_no > 1) {
                            const batchSpan = el('span', 'text-xs px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 font-medium ml-2');
                            batchSpan.textContent = `Skrining ke-${row.batch_no}`;
                            tdType.appendChild(batchSpan);
                        }

                        const tdMode = el('td', 'px-4 py-3');
                        if (row.type === 'DASS-21') {
                            const jenisColor = row.jenis === 'kehamilan' ? 'bg-pink-50 text-pink-700' : 'bg-blue-50 text-blue-700';
                            const modeBadge = el('span', `px-2 py-0.5 rounded text-xs font-medium ${jenisColor}`);
                            modeBadge.textContent = row.jenis === 'kehamilan' ? 'Kehamilan' : 'Umum';
                            tdMode.appendChild(modeBadge);
                        } else {
                            const defaultBadge = el('span', 'px-2 py-0.5 rounded text-xs font-medium bg-pink-50 text-pink-700');
                            defaultBadge.textContent = 'Kehamilan';
                            tdMode.appendChild(defaultBadge);
                        }

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
                            const dep = row.scores?.dep, anx = row.scores?.anx, str = row.scores?.stress;
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

                        // Urutan kolom: Tanggal, [Nama], Usia, Jenis, Mode, Tahap, Skor
                        tr.append(tdDate);
                        if (isAdminView) tr.append(tdIbu);
                        tr.append(tdUsia, tdType, tdMode, tdTerm, tdSum);

                        tbody.appendChild(tr);
                    });
                }

                function render() {
                    const list = filtered();
                    renderMobile(list);
                    renderDesktop(list);
                }

                // Export functions (keeping original implementations)
                function kategoriFromTotal(total, q10Score) {
                    if (num(q10Score) > 0) return 'Waspada: Pikiran Menyakiti Diri';
                    if (total >= 13) return 'Gangguan suasana hati (Tinggi)';
                    if (total >= 10) return 'Gangguan suasana hati (Sedang)';
                    return 'Tidak Ada Risiko Gangguan';
                }

                function exportEpdsXlsx(epdsRows, answerEpds, fileName = 'Export_EPDS.xlsx') {
                    if (!Array.isArray(epdsRows) || epdsRows.length === 0) {
                        alert('Data EPDS kosong.');
                        return;
                    }
                    if (!Array.isArray(answerEpds) || answerEpds.length === 0) {
                        alert('Master jawaban EPDS kosong.');
                        return;
                    }

                    // Helper functions
                    const safe = (v, d = '') => (v == null ? d : v);
                    const num = (v) => (typeof v === 'number' ? v : parseFloat(v) || 0);
                    
                    const extractRTRW = (alamat = '') => {
                        if (!alamat) return '';
                        const re = /RT\s*0*(\d+)[^\dA-Za-z]+RW\s*0*(\d+)/i;
                        const m = alamat.match(re);
                        return m ? `${m[1].padStart(2, '0')}/${m[2].padStart(2, '0')}` : '';
                    };

                    const formatTanggal = (tgl) => {
                        if (!tgl) return '';
                        try {
                            const d = new Date(tgl);
                            return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
                        } catch {
                            return tgl;
                        }
                    };

                    const formatRiwayatPenyakit = (riwayat) => {
                        if (!riwayat) return '';
                        try {
                            const arr = typeof riwayat === 'string' ? JSON.parse(riwayat) : riwayat;
                            return Array.isArray(arr) ? arr.join(', ') : '';
                        } catch {
                            return String(riwayat || '');
                        }
                    };

                    // 1) Bangun bank pertanyaan dari master jawaban
                    const _groupAnsByQ = answerEpds.reduce((m, x) => {
                        (m[x.epds_id] = m[x.epds_id] || []).push(x);
                        return m;
                    }, {});
                    
                    const byQuestion = Object.keys(_groupAnsByQ)
                        .map(k => {
                            const list = _groupAnsByQ[k];
                            const first = list[0] || {};
                            const epdsId = num(first.epds_id);
                            const qText = safe(first?.epds?.pertanyaan, `Pertanyaan ${epdsId || ''}`);
                            const opts = [...list].sort((a, b) => num(b.score) - num(a.score))
                                .map(o => `${o.score} : ${o.jawaban}`);
                            const headerText =
                                `${epdsId}. ${qText}\n` +
                                `Jawaban :\n` +
                                opts.join('\n');

                            return {
                                epds_id: epdsId,
                                header: headerText,
                                scoreByAnswerId: list.reduce((m, x) => (m[x.id] = x, m), {}),
                            };
                        })
                        .sort((a, b) => (a.epds_id || 0) - (b.epds_id || 0));

                    const questionIds = byQuestion.map(q => q.epds_id);

                    // 2) Group by session
                    const keyFor = (r) => {
                        const tok = (r.session_token || '').trim();
                        if (tok) return `tok:${tok}`;
                        if (r.screening_id) return `sid:${r.screening_id}`;
                        if (r.id) return `id:${r.id}`;
                        if (r.ibu_id && (r.screening_date || r.submitted_at || r.created_at)) {
                            return `ibu:${r.ibu_id}|tgl:${r.screening_date || r.submitted_at || r.created_at}`;
                        }
                        return `fallback:${r.ibu_id || 'x'}|${r.screening_date || r.submitted_at || 'unknown'}`;
                    };

                    const groupBy = (arr, keyFn) => arr.reduce((m, x) => {
                        const k = keyFn(x);
                        (m[k] = m[k] || []).push(x);
                        return m;
                    }, {});

                    const grouped = groupBy(epdsRows, keyFor);
                    
                    const sessions = Object.keys(grouped).map(k => {
                        const list = grouped[k];
                        const any = list[0] || {};
                        const tanggal = safe(any.screening_date || any.submitted_at || any.created_at, '');
                        
                        // Data lengkap
                        const ident = {
                            // Ibu
                            nama: safe(any.ibu_nama || any.nama_lengkap || any.nama, ''),
                            nik: safe(any.nik, ''),
                            tempat_lahir: safe(any.tempat_lahir, ''),
                            tanggal_lahir: formatTanggal(any.tanggal_lahir),
                            pendidikan_terakhir: safe(any.pendidikan_terakhir, ''),
                            pekerjaan: safe(any.pekerjaan, ''),
                            agama: safe(any.agama, ''),
                            golongan_darah: safe(any.golongan_darah, ''),
                            alamat_rumah: safe(any.alamat_rumah, ''),
                            is_luar_wilayah: safe(any.is_luar_wilayah === 1 || any.is_luar_wilayah === '1' ? 'Ya' : 'Tidak', ''),
                            kelurahan: safe(any.kelurahan_nama, ''),
                            kecamatan: safe(any.kecamatan_nama, ''),
                            kota: safe(any.kota_nama, ''),
                            provinsi: safe(any.provinsi_nama, ''),
                            rtrw: extractRTRW(any.alamat_rumah || ''),
                            no_telp: safe(any.no_telp, ''),
                            no_jkn: safe(any.no_jkn, ''),
                            puskesmas: safe(any.puskesmas_nama, ''),
                            faskes_rujukan: safe(any.faskes_rujukan_nama, ''),
                            
                            // Suami
                            suami_nama: safe(any.suami_nama, ''),
                            suami_tempat_lahir: safe(any.suami_tempat_lahir, ''),
                            suami_tanggal_lahir: formatTanggal(any.suami_tanggal_lahir),
                            suami_pendidikan: safe(any.suami_pendidikan, ''),
                            suami_pekerjaan: safe(any.suami_pekerjaan, ''),
                            suami_agama: safe(any.suami_agama, ''),
                            suami_no_telp: safe(any.suami_no_telp, ''),
                            
                            // Anak
                            anak_nama: safe(any.anak_nama, ''),
                            anak_tanggal_lahir: formatTanggal(any.anak_tanggal_lahir),
                            anak_jenis_kelamin: safe(any.anak_jenis_kelamin, ''),
                            anak_no_jkn: safe(any.anak_no_jkn, ''),
                            anak_catatan: safe(any.anak_catatan, ''),
                            
                            // Riwayat Kesehatan
                            kehamilan_ke: safe(any.kehamilan_ke, ''),
                            jml_anak_lahir_hidup: safe(any.jml_anak_lahir_hidup, ''),
                            riwayat_keguguran: safe(any.riwayat_keguguran, ''),
                            riwayat_penyakit: formatRiwayatPenyakit(any.riwayat_penyakit),
                        };

                        const answers = {};
                        for (const row of list) {
                            if (row.epds_id == null) continue;
                            const qid = num(row.epds_id);
                            const ansId = row.answers_epds_id;
                            let score = row.score;
                            if (score == null) {
                                const qMeta = byQuestion.find(q => q.epds_id === qid);
                                const meta = qMeta?.scoreByAnswerId?.[ansId];
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

                    // 3) Susun AOA untuk SheetJS
                    const AOA = [];
                    const HEADERS = [
                        // Screening
                        'Tgl Skrining',
                        
                        // Data Ibu
                        'Nama Lengkap', 'NIK', 'Tempat Lahir', 'Tanggal Lahir',
                        'Pendidikan Terakhir', 'Pekerjaan', 'Agama', 'Golongan Darah',
                        'Alamat Rumah', 'Luar Wilayah', 'Kelurahan', 'Kecamatan', 
                        'Kota/Kabupaten', 'Provinsi', 'RT/RW', 
                        'No Telp', 'No JKN', 'Puskesmas', 'Faskes Rujukan',
                        
                        // Data Suami
                        'Nama Suami', 'Tempat Lahir Suami', 'Tanggal Lahir Suami',
                        'Pendidikan Suami', 'Pekerjaan Suami', 'Agama Suami', 'No Telp Suami',
                        
                        // Data Anak
                        'Nama Anak', 'Tanggal Lahir Anak', 'Jenis Kelamin Anak',
                        'No JKN Anak', 'Catatan Anak',
                        
                        // Riwayat Kesehatan
                        'Kehamilan Ke', 'Jumlah Anak Lahir Hidup', 
                        'Riwayat Keguguran', 'Riwayat Penyakit',
                        
                        // Pertanyaan EPDS
                        ...byQuestion.map(q => q.header),
                        
                        // Total
                        'JUMLAH', 'KATEGORI'
                    ];
                    
                    AOA.push(HEADERS);

                    for (const s of sessions) {
                        const row = [
                            // Screening
                            formatTanggal(s.tanggal),
                            
                            // Data Ibu
                            s.ident.nama,
                            s.ident.nik,
                            s.ident.tempat_lahir,
                            s.ident.tanggal_lahir,
                            s.ident.pendidikan_terakhir,
                            s.ident.pekerjaan,
                            s.ident.agama,
                            s.ident.golongan_darah,
                            s.ident.alamat_rumah,
                            s.ident.is_luar_wilayah,
                            s.ident.kelurahan,
                            s.ident.kecamatan,
                            s.ident.kota,
                            s.ident.provinsi,
                            s.ident.rtrw,
                            s.ident.no_telp,
                            s.ident.no_jkn,
                            s.ident.puskesmas,
                            s.ident.faskes_rujukan,
                            
                            // Data Suami
                            s.ident.suami_nama,
                            s.ident.suami_tempat_lahir,
                            s.ident.suami_tanggal_lahir,
                            s.ident.suami_pendidikan,
                            s.ident.suami_pekerjaan,
                            s.ident.suami_agama,
                            s.ident.suami_no_telp,
                            
                            // Data Anak
                            s.ident.anak_nama,
                            s.ident.anak_tanggal_lahir,
                            s.ident.anak_jenis_kelamin,
                            s.ident.anak_no_jkn,
                            s.ident.anak_catatan,
                            
                            // Riwayat Kesehatan
                            s.ident.kehamilan_ke,
                            s.ident.jml_anak_lahir_hidup,
                            s.ident.riwayat_keguguran,
                            s.ident.riwayat_penyakit,
                        ];

                        // Scores
                        const scores = questionIds.map(qid => num(s.answers?.[qid]?.score));
                        const total = scores.reduce((a, b) => a + num(b), 0);
                        const q10 = s.answers?.[10]?.score ?? s.answers?.[questionIds[9]]?.score;
                        const kategori = kategoriFromTotal(total, q10);

                        row.push(...scores, total, kategori);
                        AOA.push(row);
                    }

                    // 4) Create workbook
                    const wb = XLSX.utils.book_new();
                    const ws = XLSX.utils.aoa_to_sheet(AOA);

                    // Column widths
                    const questionColWidth = 48;
                    ws['!cols'] = [
                        { wch: 18 }, // tgl
                        // Ibu
                        { wch: 28 }, { wch: 20 }, { wch: 18 }, { wch: 15 },
                        { wch: 18 }, { wch: 18 }, { wch: 12 }, { wch: 12 },
                        { wch: 35 }, { wch: 12 }, { wch: 20 }, { wch: 18 },
                        { wch: 20 }, { wch: 18 }, { wch: 12 }, { wch: 15 },
                        { wch: 20 }, { wch: 25 }, { wch: 25 },
                        // Suami
                        { wch: 25 }, { wch: 18 }, { wch: 15 }, { wch: 18 },
                        { wch: 18 }, { wch: 12 }, { wch: 15 },
                        // Anak
                        { wch: 25 }, { wch: 15 }, { wch: 15 }, { wch: 20 }, { wch: 30 },
                        // Riwayat
                        { wch: 12 }, { wch: 20 }, { wch: 18 }, { wch: 40 },
                        // Questions
                        ...byQuestion.map(() => ({ wch: questionColWidth })),
                        { wch: 10 }, { wch: 28 },
                    ];

                    ws['!rows'] = [{ hpt: 120 }];

                    // Format numbers
                    for (let r = 1; r < AOA.length; r++) {
                        let cStart = 37; // Mulai dari kolom pertanyaan
                        let cEnd = cStart + byQuestion.length;
                        for (let c = cStart; c <= cEnd; c++) {
                            const addr = XLSX.utils.encode_cell({ r, c });
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

                    // Helper functions
                    const safe = (v, d = '') => (v == null ? d : v);
                    const num = (v) => (typeof v === 'number' ? v : parseFloat(v) || 0);
                    
                    const extractRTRW = (alamat = '') => {
                        if (!alamat) return '';
                        const re = /RT\s*0*(\d+)[^\dA-Za-z]+RW\s*0*(\d+)/i;
                        const m = alamat.match(re);
                        return m ? `${m[1].padStart(2, '0')}/${m[2].padStart(2, '0')}` : '';
                    };

                    const formatTanggal = (tgl) => {
                        if (!tgl) return '';
                        try {
                            const d = new Date(tgl);
                            return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
                        } catch {
                            return tgl;
                        }
                    };

                    const formatRiwayatPenyakit = (riwayat) => {
                        if (!riwayat) return '';
                        try {
                            const arr = typeof riwayat === 'string' ? JSON.parse(riwayat) : riwayat;
                            return Array.isArray(arr) ? arr.join(', ') : '';
                        } catch {
                            return String(riwayat || '');
                        }
                    };

                    const groupByFn = (arr, keyFn) => arr.reduce((m, x) => {
                        const k = keyFn(x);
                        (m[k] = m[k] || []).push(x);
                        return m;
                    }, {});
                    
                    // Score mapping
                    const answerScoreMap = {};
                    (answerDass || []).forEach(a => {
                        answerScoreMap[a.id] = num(a.score);
                    });

                    // 1) Build question headers
                    const ansG = (answerDass || []).reduce((m, a) => {
                        (m[a.dass_id] = m[a.dass_id] || []).push(a);
                        return m;
                    }, {});
                    
                    const questionIds = [...skriningDass].map(q => q.id).sort((a, b) => a - b);

                    const byQuestion = questionIds.map(id => {
                        const q = (skriningDass.find(x => x.id === id) || {});
                        const opts = (ansG[id] || [])
                            .sort((a, b) => num(b.score) - num(a.score))
                            .map(o => `${o.score} : ${o.jawaban}`);
                        const header =
                            `${id}. ${safe(q.pertanyaan, 'Pertanyaan ' + id)}\n` +
                            (opts.length ? `Jawaban :\n${opts.join('\n')}` : '');
                        const scoreMap = (ansG[id] || []).reduce((m, a) => {
                            m[a.id] = a.score;
                            return m;
                        }, {});
                        return { id, header, scoreMap };
                    });

                    // 2) Group by session
                    const keyFor = (r) => {
                        const tok = (r.session_token || '').trim();
                        if (tok) return `tok:${tok}`;
                        if (r.screening_id) return `sid:${r.screening_id}`;
                        if (r.ibu_id && (r.screening_date || r.submitted_at || r.created_at)) {
                            return `ibu:${r.ibu_id}|tgl:${r.screening_date || r.submitted_at || r.created_at}`;
                        }
                        return `nm:${r.ibu_nama || r.nama_lengkap || 'x'}|${r.screening_date || r.submitted_at || r.created_at || r.id || 'u'}`;
                    };

                    const grouped = groupByFn(dassRows, keyFor);

                    // DASS-21 subscales
                    const DEP = new Set([3, 5, 10, 13, 16, 17, 21]);
                    const ANX = new Set([2, 4, 7, 9, 15, 19, 20]);
                    const STR = new Set([1, 6, 8, 11, 12, 14, 18]);

                    const sessions = Object.entries(grouped).map(([k, list]) => {
                        const any = list[0] || {};
                        
                        // Data lengkap
                        const ident = {
                            // Ibu
                            nama: safe(any.ibu_nama || any.nama_lengkap || any.nama, ''),
                            nik: safe(any.nik, ''),
                            tempat_lahir: safe(any.tempat_lahir, ''),
                            tanggal_lahir: formatTanggal(any.tanggal_lahir),
                            pendidikan_terakhir: safe(any.pendidikan_terakhir, ''),
                            pekerjaan: safe(any.pekerjaan, ''),
                            agama: safe(any.agama, ''),
                            golongan_darah: safe(any.golongan_darah, ''),
                            alamat_rumah: safe(any.alamat_rumah, ''),
                            is_luar_wilayah: safe(any.is_luar_wilayah === 1 || any.is_luar_wilayah === '1' ? 'Ya' : 'Tidak', ''),
                            kelurahan: safe(any.kelurahan_nama, ''),
                            kecamatan: safe(any.kecamatan_nama, ''),
                            kota: safe(any.kota_nama, ''),
                            provinsi: safe(any.provinsi_nama, ''),
                            rtrw: extractRTRW(any.alamat_rumah || ''),
                            no_telp: safe(any.no_telp, ''),
                            no_jkn: safe(any.no_jkn, ''),
                            puskesmas: safe(any.puskesmas_nama, ''),
                            faskes_rujukan: safe(any.faskes_rujukan_nama, ''),
                            
                            // Suami
                            suami_nama: safe(any.suami_nama, ''),
                            suami_tempat_lahir: safe(any.suami_tempat_lahir, ''),
                            suami_tanggal_lahir: formatTanggal(any.suami_tanggal_lahir),
                            suami_pendidikan: safe(any.suami_pendidikan, ''),
                            suami_pekerjaan: safe(any.suami_pekerjaan, ''),
                            suami_agama: safe(any.suami_agama, ''),
                            suami_no_telp: safe(any.suami_no_telp, ''),
                            
                            // Anak
                            anak_nama: safe(any.anak_nama, ''),
                            anak_tanggal_lahir: formatTanggal(any.anak_tanggal_lahir),
                            anak_jenis_kelamin: safe(any.anak_jenis_kelamin, ''),
                            anak_no_jkn: safe(any.anak_no_jkn, ''),
                            anak_catatan: safe(any.anak_catatan, ''),
                            
                            // Riwayat Kesehatan
                            kehamilan_ke: safe(any.kehamilan_ke, ''),
                            jml_anak_lahir_hidup: safe(any.jml_anak_lahir_hidup, ''),
                            riwayat_keguguran: safe(any.riwayat_keguguran, ''),
                            riwayat_penyakit: formatRiwayatPenyakit(any.riwayat_penyakit),
                        };

                        const answers = {};
                        let dep = any.total_depression ?? null;
                        let anx = any.total_anxiety ?? null;
                        let str = any.total_stress ?? null;

                        for (const row of list) {
                            if (row.answers_dass_id == null) {
                                if (row.total_depression != null) dep = row.total_depression;
                                if (row.total_anxiety != null) anx = row.total_anxiety;
                                if (row.total_stress != null) str = row.total_stress;
                                continue;
                            }
                            if (row.dass_id == null) continue;

                            const qid = Number(row.dass_id);
                            const ansId = row.answers_dass_id;
                            
                            let score = row.score;
                            if (score == null && ansId != null) {
                                score = answerScoreMap[ansId] ?? 0;
                            }
                            
                            answers[qid] = { ansId, score: num(score) };
                        }

                        // Fallback: calculate from answers (×2)
                        if (dep == null || anx == null || str == null) {
                            let sDep = 0, sAnx = 0, sStr = 0;
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
                            totals: { dep: num(dep), anx: num(anx), str: num(str) },
                        };
                    });

                    // 3) Build AOA
                    const HEADERS = [
                        // Screening
                        'Tgl Skrining',
                        
                        // Data Ibu
                        'Nama Lengkap', 'NIK', 'Tempat Lahir', 'Tanggal Lahir',
                        'Pendidikan Terakhir', 'Pekerjaan', 'Agama', 'Golongan Darah',
                        'Alamat Rumah', 'Luar Wilayah', 'Kelurahan', 'Kecamatan',
                        'Kota/Kabupaten', 'Provinsi', 'RT/RW',
                        'No Telp', 'No JKN', 'Puskesmas', 'Faskes Rujukan',
                        
                        // Data Suami
                        'Nama Suami', 'Tempat Lahir Suami', 'Tanggal Lahir Suami',
                        'Pendidikan Suami', 'Pekerjaan Suami', 'Agama Suami', 'No Telp Suami',
                        
                        // Data Anak
                        'Nama Anak', 'Tanggal Lahir Anak', 'Jenis Kelamin Anak',
                        'No JKN Anak', 'Catatan Anak',
                        
                        // Riwayat Kesehatan
                        'Kehamilan Ke', 'Jumlah Anak Lahir Hidup',
                        'Riwayat Keguguran', 'Riwayat Penyakit',
                        
                        // Pertanyaan DASS
                        ...byQuestion.map(q => q.header),
                        
                        // Totals
                        'DEP', 'ANX', 'STR', 'KAT DEP', 'KAT ANX', 'KAT STR'
                    ];

                    const AOA = [HEADERS];

                    // Severity labels
                    const dassDepLabel = (n) => (n <= 9 ? 'Normal' : n <= 13 ? 'Ringan' : n <= 20 ? 'Sedang' : n <= 27 ? 'Berat' : 'Sangat Berat');
                    const dassAnxLabel = (n) => (n <= 7 ? 'Normal' : n <= 9 ? 'Ringan' : n <= 14 ? 'Sedang' : n <= 19 ? 'Berat' : 'Sangat Berat');
                    const dassStrLabel = (n) => (n <= 14 ? 'Normal' : n <= 18 ? 'Ringan' : n <= 25 ? 'Sedang' : n <= 33 ? 'Berat' : 'Sangat Berat');

                    for (const s of sessions) {
                        const row = [
                            // Screening
                            formatTanggal(s.tanggal),
                            
                            // Data Ibu
                            s.ident.nama,
                            s.ident.nik,
                            s.ident.tempat_lahir,
                            s.ident.tanggal_lahir,
                            s.ident.pendidikan_terakhir,
                            s.ident.pekerjaan,
                            s.ident.agama,
                            s.ident.golongan_darah,
                            s.ident.alamat_rumah,
                            s.ident.is_luar_wilayah,
                            s.ident.kelurahan,
                            s.ident.kecamatan,
                            s.ident.kota,
                            s.ident.provinsi,
                            s.ident.rtrw,
                            s.ident.no_telp,
                            s.ident.no_jkn,
                            s.ident.puskesmas,
                            s.ident.faskes_rujukan,
                            
                            // Data Suami
                            s.ident.suami_nama,
                            s.ident.suami_tempat_lahir,
                            s.ident.suami_tanggal_lahir,
                            s.ident.suami_pendidikan,
                            s.ident.suami_pekerjaan,
                            s.ident.suami_agama,
                            s.ident.suami_no_telp,
                            
                            // Data Anak
                            s.ident.anak_nama,
                            s.ident.anak_tanggal_lahir,
                            s.ident.anak_jenis_kelamin,
                            s.ident.anak_no_jkn,
                            s.ident.anak_catatan,
                            
                            // Riwayat Kesehatan
                            s.ident.kehamilan_ke,
                            s.ident.jml_anak_lahir_hidup,
                            s.ident.riwayat_keguguran,
                            s.ident.riwayat_penyakit,
                        ];

                        // Scores
                        const scores = questionIds.map(qid => num(s.answers?.[qid]?.score));
                        const dep = num(s.totals.dep);
                        const anx = num(s.totals.anx);
                        const str = num(s.totals.str);

                        row.push(
                            ...scores,
                            dep, anx, str,
                            dassDepLabel(dep),
                            dassAnxLabel(anx),
                            dassStrLabel(str)
                        );

                        AOA.push(row);
                    }

                    // 4) Create workbook
                    const wb = XLSX.utils.book_new();
                    const ws = XLSX.utils.aoa_to_sheet(AOA);

                    const questionColWidth = 48;
                    ws['!cols'] = [
                        { wch: 18 }, // tgl
                        // Ibu
                        { wch: 28 }, { wch: 20 }, { wch: 18 }, { wch: 15 },
                        { wch: 18 }, { wch: 18 }, { wch: 12 }, { wch: 12 },
                        { wch: 35 }, { wch: 12 }, { wch: 20 }, { wch: 18 },
                        { wch: 20 }, { wch: 18 }, { wch: 12 }, { wch: 15 },
                        { wch: 20 }, { wch: 25 }, { wch: 25 },
                        // Suami
                        { wch: 25 }, { wch: 18 }, { wch: 15 }, { wch: 18 },
                        { wch: 18 }, { wch: 12 }, { wch: 15 },
                        // Anak
                        { wch: 25 }, { wch: 15 }, { wch: 15 }, { wch: 20 }, { wch: 30 },
                        // Riwayat
                        { wch: 12 }, { wch: 20 }, { wch: 18 }, { wch: 40 },
                        // Questions
                        ...byQuestion.map(() => ({ wch: questionColWidth })),
                        { wch: 8 }, { wch: 8 }, { wch: 8 },
                        { wch: 12 }, { wch: 12 }, { wch: 12 },
                    ];

                    ws['!rows'] = [{ hpt: 120 }];

                    // Format numbers
                    for (let r = 1; r < AOA.length; r++) {
                        const cStart = 37; // Mulai dari kolom pertanyaan
                        const cEndScores = cStart + byQuestion.length;
                        for (let c = cStart; c <= cEndScores; c++) {
                            const addr = XLSX.utils.encode_cell({ r, c });
                            if (ws[addr]) ws[addr].z = '0';
                        }
                        for (let c = cEndScores + 1; c <= cEndScores + 3; c++) {
                            const addr = XLSX.utils.encode_cell({ r, c });
                            if (ws[addr]) ws[addr].z = '0';
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
                    const jkt = new Date(now.toLocaleString('en-US', {
                        timeZone: tz
                    }));
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

                // jalan segera jika DOM sudah siap, kalau belum tunggu
                document.addEventListener('turbo:load', render);

            })();
        </script>
    </x-slot>
</x-app-layout>