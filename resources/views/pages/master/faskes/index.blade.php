{{-- resources/views/pages/master/pengguna/index.blade.php --}}

<x-app-layout title="Manajemen Faskes | Skrining Ibu Hamil">
    @section('page_title', $title)

    {{-- ===================== HEADER + TAB ===================== --}}

    <div x-data="{ tab: '{{ request('tab','rujukan') }}' }"
        class="grid grid-cols-1 mb-8 p-6 rounded-xl border border-gray-200 bg-gray-50 shadow-md space-y-5 mx-auto">

        <div class="grid grid-cols-6 gap-3 items-center">
            <div class="col-span-2 mt-2 text-lg font-bold text-teal-400">{{ $title }}</div>

            <div class="col-span-3 flex justify-between gap-3">
                <button type="button" @click="tab='rujukan'"
                    :class="tab==='rujukan' ? 'bg-teal-600 text-white' : 'bg-white text-gray-700'"
                    class="px-4 py-2 rounded-lg border hover:shadow transition w-full" id="btnIbu">
                    Data Rujukan
                </button>

                <button type="button" @click="tab='puskesmas'"
                    :class="tab==='puskesmas' ? 'bg-teal-600 text-white' : 'bg-white text-gray-700'"
                    class="px-4 py-2 rounded-lg border hover:shadow transition w-full" id="btnPuskesmas">
                    Data Puskesmas
                </button>
            </div>

            <button type="button"
                @click.prevent="$dispatch('open-modal', tab === 'rujukan' ? 'modal-register-rujukan' : 'modal-register-puskesmas')"
                class="btn-primary col-span-1 justify-self-end">
                Buat Baru
            </button>
        </div>

        {{-- ===================== TABEL: DATA Rujukan ===================== --}}
        <div x-show="tab==='rujukan'" x-cloak class="bg-white p-6 rounded-xl shadow-md">
            <div class="relative overflow-x-auto">
                <table id="tableRujukan" class="min-w-full table-fixed divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase rounded-tl-lg">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alamat</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Provinsi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kota</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kecamatan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No Telp</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase rounded-tr-lg">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($rujukans as $index => $item)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-left text-xs font-medium text-gray-700">
                                {{ $rujukans->firstItem() + $index }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->nama }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->alamat }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->prov }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->kota }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->kec }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->no_telp }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs">
                                <a href="#"
                                    @click.prevent="openEditFaskes('rujukan', {
                                       id: {{ $item->id }},
                                       nama: @js($item->nama),
                                       no_telp: @js($item->no_telp),
                                       alamat: @js($item->alamat),
                                       prov_id: @js($item->kode_prov),
                                       kota_id: @js($item->kode_kota),
                                       kec_id: @js($item->kode_kec),
                                   })"
                                    class="text-blue-600 hover:text-blue-900">Edit</a>
                                <form action="#" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-xs font-medium text-gray-500 text-center">Tidak ada data</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $rujukans->withQueryString()->appends(['tab' => 'rujukan'])->links() }}
            </div>
        </div>

        {{-- ===================== TABEL: DATA PUSKESMAS ===================== --}}
        <div x-show="tab==='puskesmas'" x-cloak class="bg-white p-6 rounded-xl shadow-md">
            <div class="relative overflow-x-auto">
                <table id="tablePuskesmas" class="min-w-full table-fixed divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase rounded-tl-lg">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alamat</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Provinsi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kota</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kecamatan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rujukan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase rounded-tr-lg">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($puskesmas as $index => $item)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-left text-xs font-medium text-gray-700">
                                {{ $puskesmas->firstItem() + $index }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->nama }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->alamat }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->prov }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->kota }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->kec }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->faskes?->nama }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs">
                                <a href="#"
                                    @click.prevent="openEditFaskes('puskesmas', {
                                       id: {{ $item->id }},
                                       nama: @js($item->nama),
                                       no_telp: @js($item->no_telp),
                                       alamat: @js($item->alamat),
                                       prov_id: @js($item->kode_prov),
                                       kota_id: @js($item->kode_kota),
                                       kec_id: @js($item->kode_kec),
                                       rujukan_id: @js($item->faskes_rujukan_id),
                                   })"
                                    class="text-blue-600 hover:text-blue-900">Edit</a>
                                <form action="#" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-xs font-medium text-gray-500 text-center">Tidak ada data</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $puskesmas->withQueryString()->appends(['tab' => 'puskesmas'])->links() }}
            </div>
        </div>
    </div>

    {{-- ===================== MODAL (JETSTREAM) ===================== --}}
    <x-modal name="data-faskes" focusable>
        <div id="editModalFaskes" class="p-4"
            x-data="{ get S(){ return $store.editFaskes ?? { type:'rujukan', form:{} } } }">

            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold"
                    x-text="S.type === 'rujukan' ? 'Edit Data Rujukan' : 'Edit Data Puskesmas'"></h3>
                <button type="button" class="text-gray-500 hover:text-gray-700" x-on:click="show = false">✕</button>
            </div>

            {{-- ========== FORM IBU ========== --}}
            <form x-show="S.type === 'rujukan'" x-cloak data-swup-form
                :action="`{{ route('faskes.rujukan.update', '_ID_') }}`.replace('_ID_', S.form?.id ?? '' )"
                method="POST"
                class="space-y-4">
                @method('POST')
                @csrf
                <input type="hidden" name="id" x-model="S.form.id" />
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Nama</label>
                        <input name="nama" type="text" class="input-field" x-model="S.form.nama">
                        <x-input-error class="mt-2" :messages="$errors->get('nama')" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Alamat</label>
                        <input name="alamat" type="text" class="input-field" x-model="S.form.alamat">
                    </div>

                    {{-- HIRARKI WILAYAH --}}
                    <div>
                        <label class="block text-sm font-medium">Provinsi</label>
                        <select name="prov_id" class="input-field"
                            x-model="S.form.prov_id"
                            @change="window.filterKotas(S.form.prov_id)">
                            <option value="" disabled>Pilih Provinsi</option>
                            @foreach ($provinsis as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Kab/Kota</label>
                        <select name="kota_id" class="input-field"
                            x-model="S.form.kota_id"
                            @change="window.filterKecs(S.form.kota_id)">
                            <option value="" disabled>Pilih Kab/Kota</option>
                            @foreach ($kota as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Kecamatan</label>
                        <select name="kec_id" class="input-field"
                            x-model="S.form.kec_id">
                            <option value="" disabled>Pilih Kecamatan</option>
                            @foreach ($kec as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">No Telp/HP</label>
                        <input name="no_telp" type="text" class="input-field" x-model="S.form.no_telp">
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" class="btn-secondary" x-on:click="show = false">Batal</button>
                    <button type="submit" class="btn-primary" :disabled="!(S.form?.id)">Simpan</button>
                </div>
            </form>

            {{-- ========== FORM PUSKESMAS ========== --}}
            <form x-show="S.type === 'puskesmas'" x-cloak data-swup-form
                :action="`{{ route('faskes.puskesmas.update', '_ID_') }}`.replace('_ID_', S.form?.id ?? '' )"
                method="POST"
                class="space-y-4">
                @method('POST')
                @csrf

                <input type="hidden" name="id" x-model="S.form.id" />
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Nama</label>
                        <input name="nama" type="text" class="input-field" x-model="S.form.nama">
                        <x-input-error class="mt-2" :messages="$errors->get('nama')" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Alamat</label>
                        <input name="alamat" type="text" class="input-field" x-model="S.form.alamat">
                    </div>

                    {{-- HIRARKI WILAYAH --}}
                    <div>
                        <label class="block text-sm font-medium">Provinsi</label>
                        <select name="prov_id" class="input-field"
                            x-model="S.form.prov_id"
                            @change="window.filterKotas(S.form.prov_id)">
                            <option value="" disabled>Pilih Provinsi</option>
                            @foreach ($provinsis as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Kab/Kota</label>
                        <select name="kota_id_pus" class="input-field"
                            x-model="S.form.kota_id"
                            @change="window.filterKecs(S.form.kota_id)">
                            <option value="" disabled>Pilih Kab/Kota</option>
                            @foreach ($kota as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Kecamatan</label>
                        <select name="kec_id_pus" class="input-field"
                            x-model="S.form.kec_id">
                            <option value="" disabled>Pilih Kecamatan</option>
                            @foreach ($kec as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Faskes Rujukan</label>
                        <select name="faskes_rujukan_id" class="input-field"
                            x-model="S.form.faskes_rujukan_id">
                            <option value="">Pilih Rujukan</option>
                            @foreach ($rs as $r)
                            <option value="{{ $r->id }}">{{ $r->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" class="btn-secondary" x-on:click="show = false">Batal</button>
                    <button type="submit" class="btn-primary" :disabled="!(S.form?.id)">Simpan</button>
                </div>
            </form>
        </div>
    </x-modal>

    <x-modal name="modal-register-rujukan" focusable>
        <div class="p-8">
            <h2 class="text-lg text-center font-semibold my-3">Create Data Rujukan</h2>
            @include('includes.create-rujukan')
        </div>
    </x-modal>

    <x-modal name="modal-register-puskesmas" focusable>
        <div class="p-8">
            <h2 class="text-lg text-center font-semibold my-3">Create Data Puskesmas</h2>
            @include('includes.create-puskesmas')
        </div>
    </x-modal>

    {{-- ===================== SCRIPTS ===================== --}}
    <x-slot name="scripts">
        <script data-swup-reload-script>
            // ========== Alpine Global Store + Open/Close Modal ==========
            (function() {
                const defaults = () => ({
                    id: null,
                    user_id: null,
                    nama: '',
                    nik: '',
                    no_telp: '',
                    alamat_rumah: '',
                    prov_id: '',
                    kota_id: '',
                    kec_id: '',
                    kelurahan_id: '',
                    puskesmas_id: '',
                    faskes_rujukan_id: '',
                    no_jkn: '',
                    tempat_lahir: '',
                    tanggal_lahir: '',
                    pendidikan: '',
                    pekerjaan: '',
                    agama: '',
                    gol_darah: '',
                    jabatan_id: ''
                });

                function installStore() {
                    if (Alpine.store('editFaskes')) return;
                    Alpine.store('editFaskes', {
                        type: 'rujukan',
                        form: defaults(),
                        set(type, item = {}) {
                            this.type = type;
                            this.form = Object.assign(defaults(), item);
                        },
                        reset() {
                            this.set('rujukan', {});
                        }
                    });

                    window.openEditFaskes = function(type, item) {
                        Alpine.store('editFaskes').set(type, item);
                        setTimeout(() => {
                            const f = Alpine.store('editFaskes').form;
                            if (f.prov_id) window.filterKotas(f.prov_id, f.kota_id);
                            if (f.kota_id) window.filterKecs(f.kota_id, f.kec_id);
                        }, 0);
                        window.dispatchEvent(new CustomEvent('open-modal', {
                            detail: 'data-faskes'
                        }));
                    };
                    window.closeEdit = () => window.dispatchEvent(new CustomEvent('close-modal', {
                        detail: 'data-faskes'
                    }));
                }

                if (window.Alpine) installStore();
                else document.addEventListener('alpine:init', installStore, {
                    once: true
                });

                // ========== Helper to fill <select> options ==========
                function fillSelect(selectEl, items, placeholderText, valueKey = 'code', labelKey = 'name') {
                    if (!selectEl) return;
                    selectEl.innerHTML = '';
                    const def = new Option(placeholderText, '', true, false);
                    def.disabled = true;
                    selectEl.add(def);
                    (items || []).forEach(it => {
                        const val = it[valueKey];
                        const label = it[labelKey];
                        if (val && label) selectEl.add(new Option(label, val));
                    });
                }

                function getModalRoot() {
                    return document.getElementById('editModalFaskes') || document;
                }

                // ========== AJAX: Wilayah & Faskes ==========
                async function filterKotas(provId, selectedValue = null) {
                    try {
                        const routeUrl = "{{ route('kota.filter') }}";
                        const req = new Fetch(routeUrl);
                        req.method = 'GET';
                        req.bodyObject = {
                            provId
                        };
                        const res = await req.run();

                        if (res.ack === 'ok') {
                            const root = getModalRoot();
                            const el = root.querySelector('select[name="kota_id"]');
                            const pus = root.querySelector('select[name="kota_id_pus"]');
                            fillSelect(el, res.data, 'Pilih Kab/Kota');
                            fillSelect(pus, res.data, 'Pilih Kab/Kota');
                            if (selectedValue) {
                                el.value = selectedValue;
                                pus.value = selectedValue;
                                // trigger next level if needed
                                await filterKecs(selectedValue, Alpine.store('editFaskes').form.kec_id);
                            }
                        } else {
                            ALERT(res.message, res.ack);
                        }
                    } catch (e) {
                        console.log(e);
                    }
                }

                async function filterKecs(kotaId, selectedValue = null) {
                    try {
                        const routeUrl = "{{ route('kecamatan.filter') }}";
                        const req = new Fetch(routeUrl);
                        req.method = 'GET';
                        req.bodyObject = {
                            kotaId
                        };
                        const res = await req.run();

                        if (res.ack === 'ok') {
                            const root = getModalRoot();
                            const el = root.querySelector('select[name="kec_id"]');
                            const pus = root.querySelector('select[name="kec_id_pus"]');
                            fillSelect(el, res.data, 'Pilih Kecamatan');
                            fillSelect(pus, res.data, 'Pilih Kecamatan');
                            if (selectedValue) {
                                el.value = selectedValue;
                                pus.value = selectedValue;
                            }
                        } else {
                            ALERT(res.message, res.ack);
                        }
                    } catch (e) {
                        console.log(e);
                    }
                }

                window.filterKotas = filterKotas;
                window.filterKecs = filterKecs;
            })();
        </script>

        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>
    </x-slot>
</x-app-layout>