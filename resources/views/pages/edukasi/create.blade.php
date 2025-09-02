<x-app-layout>
    @section('page_title','Buat Konten')
    <x-slot name="title">Buat Konten Edukasi</x-slot>
    <x-header-back>Post Edukasi</x-header-back>

    <a href="{{ route('edukasi.index') }}" class="btn-primary w-1/2 hidden md:block md:w-1/6">lihat Konten Edukasi</a>
    <form id="edu-form" action="{{ route('edukasi.store') }}" method="post" enctype="multipart/form-data" class="space-y-5">
        @csrf

        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Judul</label>
            <input name="title" class="mt-1 w-full rounded-lg border px-3 py-2" required>
            <p class="mt-1 text-xs text-gray-500">Slug dibuat otomatis.</p>
        </div>

        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Ringkasan (opsional)</label>
            <textarea name="summary" class="mt-1 w-full rounded-lg border px-3 py-2" rows="3" maxlength="500"></textarea>
        </div>

        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Konten (Markdown/HTML ringan)</label>
            <textarea name="body" class="mt-1 w-full rounded-lg border px-3 py-2" rows="10"></textarea>
        </div>

        {{-- MEDIA --}}
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Galeri Gambar</label>
            <input type="file" name="images[]" multiple accept="image/*" class="mt-2 block w-full">
            <p class="mt-1 text-xs text-gray-500">Bisa banyak. Gambar pertama jadi cover.</p>
        </div>

        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Video (Upload)</label>
            <input type="file" name="videos[]" multiple accept="video/mp4,video/webm,video/quicktime" class="mt-2 block w-full">
            <p class="mt-1 text-xs text-gray-500">MP4/WEBM/MOV, maks ~50MB per file.</p>
        </div>

        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Video URL (YouTube/Vimeo)</label>
            <textarea name="video_urls" rows="3" class="mt-2 w-full rounded-lg border px-3 py-2" placeholder="Satu URL per baris"></textarea>
        </div>

        {{-- TAGS & VISIBILITY --}}
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Tags (pisahkan dengan koma)</label>
            <input name="tags" placeholder="postpartum, kecemasan, tidur" class="mt-1 w-full rounded-lg border px-3 py-2">
        </div>

        <div class="rounded-2xl border bg-white p-4 shadow-sm grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="text-sm font-medium">Visibility</label>
                <select id="visibility" name="visibility" class="mt-1 w-full rounded-lg border px-3 py-2" required>
                    <option value="public">Public</option>
                    <option value="facility">Facility (berdasarkan Puskesmas)</option>
                    <option value="private">Private</option>
                </select>
            </div>
            <div id="puskesmas-field" class="hidden">
                <label class="text-sm font-medium">Puskesmas ID</label>
                <input name="puskesmas_id" type="number" class="mt-1 w-full rounded-lg border px-3 py-2" placeholder="cth: 123">
            </div>
            <div>
                <label class="text-sm font-medium">Status</label>
                <select name="status" class="mt-1 w-full rounded-lg border px-3 py-2" required>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium">Tanggal Publish (opsional)</label>
                <input name="published_at" type="datetime-local" class="mt-1 w-full rounded-lg border px-3 py-2">
            </div>
        </div>

        {{-- RULES targeting (PURE JS) --}}
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <label class="text-sm font-semibold">Targeting Berdasarkan Hasil Skrining</label>
                <button id="add-rule-btn" type="button" class="px-3 py-1.5 rounded-lg bg-gray-900 text-white text-sm">+ Tambah Rule</button>
            </div>
            <div id="rules-list"></div>
            <p class="mt-2 text-xs text-gray-500">Jika tidak ada rule, konten dianggap umum.</p>
        </div>

        <div class="pb-24">
            <button class="w-full md:w-auto px-4 py-2 rounded-xl bg-teal-600 text-white text-sm font-medium hover:bg-teal-700">
                Simpan Konten
            </button>
        </div>
    </form>

    {{-- Template item rule --}}
    <template id="rule-template">
        <div class="rule-item mt-3 grid grid-cols-1 sm:grid-cols-5 gap-2 border rounded-xl p-3">
            <div>
                <label class="text-xs text-gray-600">Jenis</label>
                <select class="field-type w-full rounded-lg border px-2 py-1.5">
                    <option value="epds">EPDS</option>
                    <option value="dass">DASS-21</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-600">Dimensi</label>
                <select class="field-dimension w-full rounded-lg border px-2 py-1.5"></select>
            </div>
            <div>
                <label class="text-xs text-gray-600">Min</label>
                <input type="number" class="field-min w-full rounded-lg border px-2 py-1.5">
            </div>
            <div>
                <label class="text-xs text-gray-600">Max</label>
                <input type="number" class="field-max w-full rounded-lg border px-2 py-1.5">
            </div>
            <div>
                <label class="text-xs text-gray-600">Trimester</label>
                <select class="field-tri w-full rounded-lg border px-2 py-1.5">
                    <option value="">(Semua)</option>
                    <option value="trimester_1">Trimester I</option>
                    <option value="trimester_2">Trimester II</option>
                    <option value="trimester_3">Trimester III</option>
                    <option value="pasca_hamil">Pasca Hamil</option>
                </select>
            </div>
            <div class="sm:col-span-5 flex justify-end">
                <button type="button" class="btn-remove-rule text-sm text-rose-600">Hapus</button>
            </div>
        </div>
    </template>

    {{-- JS pure (Swup aware) --}}
    <x-slot name="scripts">
        <script data-swup-reload-script>
            (function() {
                const $ = (s, c = document) => c.querySelector(s);
                const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));

                const visSel = $('#visibility');
                const pusk = $('#puskesmas-field');
                const list = $('#rules-list');
                const tpl = $('#rule-template');
                const addBtn = $('#add-rule-btn');

                const DIM = {
                    epds: [{
                        v: 'epds_total',
                        t: 'EPDS Total'
                    }],
                    dass: [{
                        v: 'dass_dep',
                        t: 'Depresi'
                    }, {
                        v: 'dass_anx',
                        t: 'Kecemasan'
                    }, {
                        v: 'dass_str',
                        t: 'Stres'
                    }]
                };

                function togglePusk() {
                    const show = visSel.value === 'facility';
                    pusk.classList.toggle('hidden', !show);
                    if (!show) {
                        const i = $('input[name="puskesmas_id"]', pusk);
                        if (i) i.value = '';
                    }
                }
                visSel.addEventListener('change', togglePusk);
                togglePusk();

                function setDim(el, type, current = null) {
                    const defs = type === 'dass' ? DIM.dass : DIM.epds;
                    el.innerHTML = '';
                    defs.forEach(d => {
                        const o = document.createElement('option');
                        o.value = d.v;
                        o.textContent = d.t;
                        el.appendChild(o);
                    });
                    el.value = (current && defs.find(x => x.v === current)) ? current : defs[0].v;
                }

                function renum() {
                    $$('.rule-item', list).forEach((it, i) => {
                        const t = $('.field-type', it),
                            d = $('.field-dimension', it),
                            mn = $('.field-min', it),
                            mx = $('.field-max', it),
                            tr = $('.field-tri', it);
                        setDim(d, t.value, d.value);
                        t.name = `rules[${i}][screening_type]`;
                        d.name = `rules[${i}][dimension]`;
                        mn.name = `rules[${i}][min_score]`;
                        mx.name = `rules[${i}][max_score]`;
                        tr.name = `rules[${i}][trimester]`;
                    });
                }

                function add(def) {
                    const node = tpl.content.cloneNode(true);
                    const it = $('.rule-item', node);
                    const t = $('.field-type', it);
                    const d = $('.field-dimension', it);
                    const mn = $('.field-min', it);
                    const mx = $('.field-max', it);
                    const tr = $('.field-tri', it);

                    const df = Object.assign({
                        screening_type: 'epds',
                        dimension: 'epds_total',
                        min_score: '',
                        max_score: '',
                        trimester: ''
                    }, def || {});
                    t.value = df.screening_type;
                    setDim(d, df.screening_type, df.dimension);
                    mn.value = df.min_score;
                    mx.value = df.max_score;
                    tr.value = df.trimester;

                    list.appendChild(node);
                    renum();
                }

                addBtn.addEventListener('click', () => add());
                list.addEventListener('change', (e) => {
                    const it = e.target.closest('.rule-item');
                    if (!it) return;
                    if (e.target.classList.contains('field-type')) {
                        setDim($('.field-dimension', it), e.target.value, null);
                        renum();
                    }
                });
                list.addEventListener('click', (e) => {
                    if (e.target.classList.contains('btn-remove-rule')) {
                        const it = e.target.closest('.rule-item');
                        if (it) it.remove();
                        renum();
                    }
                });

                // add default rule (opsional)
                // add();
            })();
        </script>
    </x-slot>
</x-app-layout>