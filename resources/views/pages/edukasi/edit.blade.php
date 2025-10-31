<x-app-layout>
    @section('page_title','Edit Konten')
    <x-slot name="title">Edit: {{ $content->title }}</x-slot>
    <x-header-back>Edit Post Edukasi</x-header-back>

    <form action="{{ route('edukasi.update',$content->slug) }}" method="post" enctype="multipart/form-data" class="space-y-5">
        @csrf @method('PUT')

        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Judul</label>
            <input name="title" value="{{ $content->title }}" class="mt-1 w-full rounded-lg border px-3 py-2" required>
        </div>

        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Ringkasan</label>
            <textarea name="summary" class="mt-1 w-full rounded-lg border px-3 py-2" rows="3" maxlength="500">{{ $content->summary }}</textarea>
        </div>

        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Konten</label>
            <textarea name="body" class="mt-1 w-full rounded-lg border px-3 py-2" rows="10">{{ $content->body }}</textarea>
        </div>

        {{-- MEDIA EXISTING: drag to reorder, checkbox to remove --}}
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-semibold">Media Saat Ini</label>
                <span class="text-xs text-gray-500">Tarik untuk mengurutkan</span>
            </div>
            <ul id="media-list" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach($content->media as $m)
                <li class="rounded-xl border bg-white overflow-hidden" draggable="true" data-id="{{ $m->id }}">
                    <div class="aspect-[16/9] bg-gray-100">
                        @if($m->is_image)
                        <img src="{{ $m->url }}" class="w-full h-full object-cover">
                        @elseif($m->is_video)
                        <video src="{{ $m->url }}" class="w-full h-full object-cover" muted></video>
                        @else
                        <img src="{{ $m->poster_url }}" class="w-full h-full object-cover">
                        @endif
                    </div>
                    <div class="p-2 text-xs flex items-center justify-between">
                        <span>{{ strtoupper($m->media_type) }}</span>
                        <label class="inline-flex items-center gap-1">
                            <input type="checkbox" name="remove_media[]" value="{{ $m->id }}">
                            <span>Hapus</span>
                        </label>
                    </div>
                </li>
                @endforeach
            </ul>
            <input type="hidden" id="existing_order" name="existing_order">
        </div>

        {{-- MEDIA BARU --}}
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Tambah Gambar Baru</label>
            <input type="file" name="images[]" multiple accept="image/*" class="mt-2 block w-full">
        </div>
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Tambah Video Baru (Upload)</label>
            <input type="file" name="videos[]" multiple accept="video/mp4,video/webm,video/quicktime" class="mt-2 block w-full">
        </div>
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Tambah Video URL (YouTube/Vimeo)</label>
            <textarea name="video_urls" rows="3" class="mt-2 w-full rounded-lg border px-3 py-2" placeholder="Satu URL per baris"></textarea>
        </div>

        <div class="rounded-2xl border bg-white p-4 shadow-sm grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="text-sm font-medium">Visibility</label>
                <select id="visibility" name="visibility" class="mt-1 w-full rounded-lg border px-3 py-2" required>
                    <option value="public" @selected($content->visibility==='public')>Public</option>
                    <option value="facility" @selected($content->visibility==='facility')>Facility</option>
                    <option value="private" @selected($content->visibility==='private')>Private</option>
                </select>
            </div>
            <div id="puskesmas-field" class="{{ $content->visibility==='facility' ? '' : 'hidden' }}">
                <label class="text-sm font-medium">Puskesmas ID</label>
                <input name="puskesmas_id" type="number" value="{{ $content->puskesmas_id }}" class="mt-1 w-full rounded-lg border px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-medium">Status</label>
                <select name="status" class="mt-1 w-full rounded-lg border px-3 py-2" required>
                    <option value="draft" @selected($content->status==='draft')>Draft</option>
                    <option value="published" @selected($content->status==='published')>Published</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium">Tanggal Publish</label>
                <input name="published_at" type="datetime-local" value="{{ optional($content->published_at)->format('Y-m-d\TH:i') }}" class="mt-1 w-full rounded-lg border px-3 py-2">
            </div>
        </div>

        {{-- RULES: render ulang dari $content->rules --}}
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <label class="text-sm font-semibold">Targeting Berdasarkan Hasil Skrining</label>
                <button id="add-rule-btn" type="button" class="px-3 py-1.5 rounded-lg bg-gray-900 text-white text-sm">+ Tambah Rule</button>
            </div>
            <div id="rules-list">
                @foreach($content->rules as $i => $r)
                <div class="rule-item mt-3 grid grid-cols-1 sm:grid-cols-5 gap-2 border rounded-xl p-3" data-initial="1">
                    <div>
                        <label class="text-xs text-gray-600">Jenis</label>
                        <select class="field-type w-full rounded-lg border px-2 py-1.5" name="rules[{{ $i }}][screening_type]">
                            <option value="epds" @selected($r->screening_type==='epds')>EPDS</option>
                            <option value="dass" @selected($r->screening_type==='dass')>DASS-21</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Dimensi</label>
                        <select class="field-dimension w-full rounded-lg border px-2 py-1.5" name="rules[{{ $i }}][dimension]">
                            <option value="epds_total" @selected($r->dimension==='epds_total')>EPDS Total</option>
                            <option value="dass_dep" @selected($r->dimension==='dass_dep')>Depresi</option>
                            <option value="dass_anx" @selected($r->dimension==='dass_anx')>Kecemasan</option>
                            <option value="dass_str" @selected($r->dimension==='dass_str')>Stres</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Min</label>
                        <input type="number" class="field-min w-full rounded-lg border px-2 py-1.5" name="rules[{{ $i }}][min_score]" value="{{ $r->min_score }}">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Max</label>
                        <input type="number" class="field-max w-full rounded-lg border px-2 py-1.5" name="rules[{{ $i }}][max_score]" value="{{ $r->max_score }}">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Trimester</label>
                        <select class="field-tri w-full rounded-lg border px-2 py-1.5" name="rules[{{ $i }}][trimester]">
                            <option value="" @selected($r->trimester==='')>(Semua)</option>
                            <option value="trimester_1" @selected($r->trimester==='trimester_1')>Trimester I</option>
                            <option value="trimester_2" @selected($r->trimester==='trimester_2')>Trimester II</option>
                            <option value="trimester_3" @selected($r->trimester==='trimester_3')>Trimester III</option>
                            <option value="pasca_hamil" @selected($r->trimester==='pasca_hamil')>Pasca Melahirkan</option>
                        </select>
                    </div>
                    <div class="sm:col-span-5 flex justify-end">
                        <button type="button" class="btn-remove-rule text-sm text-rose-600">Hapus</button>
                    </div>
                </div>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-gray-500">Jika tidak ada rule, konten dianggap umum.</p>
        </div>

        <div class="pb-24">
            <button class="w-full md:w-auto px-4 py-2 rounded-xl bg-teal-600 text-white text-sm font-medium hover:bg-teal-700">Simpan Perubahan</button>
        </div>
    </form>

    {{-- Template rule (untuk tambahan baru) --}}
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
                    <option value="pasca_hamil">Pasca Melahirkan</option>
                </select>
            </div>
            <div class="sm:col-span-5 flex justify-end">
                <button type="button" class="btn-remove-rule text-sm text-rose-600">Hapus</button>
            </div>
        </div>
    </template>

    {{-- JS: visibility toggle + drag-sort media + rules (pure JS) --}}
    <x-slot name="scripts">
        <script>
            (function() {
                const $ = (s, c = document) => c.querySelector(s);
                const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));

                // toggle puskesmas
                const visSel = $('#visibility'),
                    pusk = $('#puskesmas-field');

                function toggle() {
                    const show = visSel.value === 'facility';
                    pusk.classList.toggle('hidden', !show);
                    if (!show) {
                        const i = $('input[name="puskesmas_id"]', pusk);
                        if (i) i.value = '';
                    }
                }
                visSel.addEventListener('change', toggle);
                toggle();

                // drag-sort media
                const list = $('#media-list'),
                    hidden = $('#existing_order');
                let dragEl = null;
                list.addEventListener('dragstart', e => {
                    dragEl = e.target.closest('li[draggable="true"]');
                    e.dataTransfer.effectAllowed = 'move';
                });
                list.addEventListener('dragover', e => {
                    e.preventDefault();
                    const li = e.target.closest('li[draggable="true"]');
                    if (!li || li === dragEl) return;
                    const rect = li.getBoundingClientRect();
                    const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > .5;
                    list.insertBefore(dragEl, next ? li.nextSibling : li);
                });
                list.addEventListener('dragend', () => {
                    const ids = $$('#media-list li').map(li => li.dataset.id);
                    hidden.value = ids.join(',');
                });
                // init order
                hidden.value = $$('#media-list li').map(li => li.dataset.id).join(',');

                // rules (add/remove)
                const tpl = $('#rule-template'),
                    rlist = $('#rules-list'),
                    addBtn = $('#add-rule-btn');
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

                function setDim(el, type, current) {
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
                    $$('.rule-item', rlist).forEach((it, i) => {
                        if (it.dataset.initial === '1') return; // sudah punya name dari server
                        $('.field-type', it).name = `rules[${i}][screening_type]`;
                        $('.field-dimension', it).name = `rules[${i}][dimension]`;
                        $('.field-min', it).name = `rules[${i}][min_score]`;
                        $('.field-max', it).name = `rules[${i}][max_score]`;
                        $('.field-tri', it).name = `rules[${i}][trimester]`;
                    });
                }

                function add(def) {
                    const node = tpl.content.cloneNode(true);
                    const it = $('.rule-item', node);
                    const t = $('.field-type', it),
                        d = $('.field-dimension', it),
                        mn = $('.field-min', it),
                        mx = $('.field-max', it),
                        tr = $('.field-tri', it);
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
                    rlist.appendChild(node);
                    renum();
                }
                addBtn.addEventListener('click', () => add());
                rlist.addEventListener('change', (e) => {
                    const it = e.target.closest('.rule-item');
                    if (!it) return;
                    if (e.target.classList.contains('field-type')) {
                        setDim($('.field-dimension', it), e.target.value, null);
                        renum();
                    }
                });
                rlist.addEventListener('click', (e) => {
                    if (e.target.classList.contains('btn-remove-rule')) {
                        const it = e.target.closest('.rule-item');
                        if (it) {
                            it.remove();
                            renum();
                        }
                    }
                });

            })();
        </script>
    </x-slot>
</x-app-layout>