<x-app-layout>
    @section('page_title', 'Riwayat Skrining')
    <x-slot name="title">{{ $title ?? 'Riwayat Skrining' }}</x-slot>

    <x-header-back>Riwayat</x-header-back>

    {{-- ROOT: data items dikirim lewat data-attribute agar bisa diambil JS --}}
    <div id="riwayat-root" class="max-w-7xl mx-auto p-4 sm:p-6" data-usia='@json($usia_hamil)' data-items='@json($items)'>

        {{-- Header + Filter --}}
        <div class="bg-white rounded-2xl shadow p-4 sm:p-5 mb-4 sm:mb-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-semibold text-gray-800">Riwayat Skrining</h1>
                    <p class="text-sm text-gray-500">Hasil EPDS & DASS-21, bisa disaring per tahun & tahap kehamilan.</p>
                </div>

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
                            <th class="px-4 py-3 text-left">Usia Kehamilahn</th>
                            <th class="px-4 py-3 text-left">Jenis</th>
                            <th class="px-4 py-3 text-left">Tahap</th>
                            <th class="px-4 py-3 text-left">Skor / Ringkasan</th>
                        </tr>
                    </thead>
                    <tbody id="table-body" class="divide-y">
                        <tr id="desktop-empty" class="hidden">
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">Tidak ada data.</td>
                        </tr>
                        {{-- baris akan diisi via JS --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- JS murni, dieksekusi ulang oleh @swup/scripts-plugin --}}
    <x-slot name="scripts">
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
                const yearSel = $('#filter-year', root);
                const termSel = $('#filter-term', root);
                const kindSel = $('#filter-kind', root);
                const mobileWrap = $('#cards-mobile', root);
                const mobileEmpty = $('#mobile-empty', root);
                const tbody = $('#table-body', root);
                const desktopEmpty = $('#desktop-empty', root);

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
                years.forEach(y => {
                    const opt = el('option');
                    opt.value = y;
                    opt.textContent = y;
                    yearSel.appendChild(opt);
                });

                // Listeners filter
                yearSel.addEventListener('change', () => {
                    state.filters.year = yearSel.value;
                    render();
                });
                termSel.addEventListener('change', () => {
                    state.filters.term = termSel.value;
                    render();
                });
                kindSel.addEventListener('change', () => {
                    state.filters.kind = kindSel.value;
                    render();
                });

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

                        const top = el('div', 'flex items-center justify-between');
                        const badge = el('span', 'px-2 py-0.5 rounded text-xs font-medium ' + (row.type === 'EPDS' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700'));
                        badge.textContent = row.type;
                        const date = el('span', 'text-sm text-gray-500');
                        date.textContent = row.date_human || '—';
                        top.append(badge, date);

                        const body = el('div', 'text-sm text-gray-700');
                        const tri = el('span', 'text-xs px-2 py-0.5 rounded bg-amber-50 text-amber-700 font-medium');
                        tri.textContent = TRIMESTER_LABELS[row.trimester] + " | " + row.usia_hamil?.keterangan ?? '—';
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

                        card.append(top, body);
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

                        const tdUsia = el('td', 'px-4 py-3 capitalize');
                        const us = el('span', 'text-xs px-2 py-0.5 rounded bg-amber-50 text-amber-700 font-medium');
                        us.textContent = row.usia_hamil?.keterangan || '—';
                        tdUsia.appendChild(us);

                        const tdDate = el('td', 'px-4 py-3');
                        tdDate.textContent = row.date_human || '—';
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

                            wrap.append(
                                part('DEP', dep, dassDepLabel),
                                part('ANX', anx, dassAnxLabel),
                                part('STR', str, dassStrLabel),
                            );
                            tdSum.appendChild(wrap);
                        }

                        tr.append(tdDate, tdUsia, tdType, tdTerm, tdSum);
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