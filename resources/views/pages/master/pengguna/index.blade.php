{{-- resources/views/pages/master/pengguna/index.blade.php --}}

<x-app-layout title="Manajemen Pengguna | Skrining Ibu Hamil">
    @section('page_title', $title)

    {{-- ===================== HEADER + TAB ===================== --}}
    <div x-data="{ tab: '{{ request('tab','ibu') }}' }"
         class="grid grid-cols-1 mb-8 p-6 rounded-xl border border-gray-200 bg-gray-50 shadow-md space-y-5 mx-auto">

        <div class="grid grid-cols-6 gap-3 items-center">
            <div class="col-span-2 mt-2 text-lg font-bold text-teal-400">Manajemen Pengguna</div>

            <div class="col-span-3 flex justify-between gap-3">
                <button type="button" @click="tab='ibu'"
                        :class="tab==='ibu' ? 'bg-teal-600 text-white' : 'bg-white text-gray-700'"
                        class="px-4 py-2 rounded-lg border hover:shadow transition w-full" id="btnIbu">
                    Data Ibu
                </button>

                <button type="button" @click="tab='puskesmas'"
                        :class="tab==='puskesmas' ? 'bg-teal-600 text-white' : 'bg-white text-gray-700'"
                        class="px-4 py-2 rounded-lg border hover:shadow transition w-full" id="btnPuskesmas">
                    Data Puskesmas
                </button>
            </div>

            <button type="button" class="btn-primary col-span-1 justify-self-end">Buat Akun Baru</button>
        </div>

        {{-- ===================== TABEL: DATA IBU ===================== --}}
        <div x-show="tab==='ibu'" x-cloak class="bg-white p-6 rounded-xl shadow-md">
            <div class="relative overflow-x-auto">
                <table id="tableIbu" class="min-w-full table-fixed divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase rounded-tl-lg">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIK</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Lahir</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No JKN</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No Telp</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alamat</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Puskesmas</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Faskes</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase rounded-tr-lg">Action</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($dataIbu as $index => $item)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-left text-xs font-medium text-gray-700">
                                {{ $dataIbu->firstItem() + $index }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->nama }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->nik }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ formatTanggal($item->tanggal_lahir) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->no_jkn }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->no_telp }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->alamat_rumah }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->puskesmas?->nama }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700">{{ $item->faskes?->nama }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs">
                                <a href="#"
                                   @click.prevent="openEdit('ibu', {
                                       id: {{ $item->id }},
                                       user_id: {{ $item->user_id }},
                                       nama: @js($item->nama),
                                       nik: @js($item->nik),
                                       no_telp: @js($item->no_telp),
                                       alamat_rumah: @js($item->alamat_rumah),
                                       puskesmas_id: @js($item->puskesmas_id),
                                       faskes_rujukan_id: @js($item->faskes_rujukan_id),
                                       no_jkn: @js($item->no_jkn),
                                       tempat_lahir: @js($item->tempat_lahir),
                                       tanggal_lahir: @js(optional($item->tanggal_lahir)->format('Y-m-d')),
                                       pendidikan: @js($item->pendidikan_terakhir),
                                       pekerjaan: @js($item->pekerjaan),
                                       agama: @js($item->agama),
                                       gol_darah: @js($item->golongan_darah),
                                       prov_id: @js($item->kode_prov),
                                       kota_id: @js($item->kode_kab),
                                       kec_id: @js($item->kode_kec),
                                       kelurahan_id: @js($item->kode_des),
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
                            <td colspan="10" class="px-6 py-4 text-xs font-medium text-gray-500 text-center">Tidak ada data</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $dataIbu->withQueryString()->appends(['tab' => 'ibu'])->links() }}
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIK</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jabatan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Puskesmas</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No Telp</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase rounded-tr-lg">Action</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($data_puskesmas as $index => $item)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-xs font-medium text-gray-700">
                                {{ $data_puskesmas->firstItem() + $index }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-700">{{ $item->nama }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-700">{{ $item->nik }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-700">
                                {{ $item->user?->jabatan?->nama ?? $item->user?->jabatan_id ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-700">{{ $item->puskesmas?->nama ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-700">{{ $item->no_telp ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs">
                                <a href="#"
                                   @click.prevent="openEdit('puskesmas', {
                                       id: {{ $item->id }},
                                       user_id: {{ $item->user_id }},
                                       nama: @js($item->nama),
                                       nik: @js($item->nik),
                                       no_telp: @js($item->no_telp),
                                       alamat_rumah: @js($item->alamat_rumah),
                                       puskesmas_id: @js($item->puskesmas_id),
                                       jabatan_id: @js($item->user?->jabatan_id),
                                       prov_id: @js($item->kode_prov),
                                       kota_id: @js($item->kode_kab),
                                       kec_id: @js($item->kode_kec),
                                       kelurahan_id: @js($item->kode_des),
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
                            <td colspan="7" class="px-6 py-4 text-xs font-medium text-gray-500 text-center">Tidak ada data</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $data_puskesmas->withQueryString()->appends(['tab' => 'puskesmas'])->links() }}
            </div>
        </div>
    </div>

    {{-- ===================== MODAL (JETSTREAM) ===================== --}}
    <x-modal name="datasiapa?" focusable>
        <div id="editModal" class="p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold"
                    x-text="$store.editUser.type==='ibu' ? 'Edit Data Ibu' : 'Edit Data Puskesmas'"></h3>
                <button type="button" class="text-gray-500 hover:text-gray-700" @click="closeEdit()">✕</button>
            </div>

            {{-- ========== FORM IBU ========== --}}
            <form x-show="$store.editUser.type==='ibu'"
                  method="POST"
                  :action="`{{ route('pengguna.ibu.update', '_ID_') }}`.replace('_ID_', $store.editUser.form.id ?? '')"
                  class="space-y-4">
                @csrf @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Nama Lengkap</label>
                        <input name="name" type="text" class="input-field" x-model="$store.editUser.form.nama" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">NIK</label>
                        <input name="nik" type="text" class="input-field" x-model="$store.editUser.form.nik" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Tempat Lahir</label>
                        <input name="tempat_lahir" type="text" class="input-field" x-model="$store.editUser.form.tempat_lahir">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Tanggal Lahir</label>
                        <input name="tanggal_lahir" type="date" class="input-field" x-model="$store.editUser.form.tanggal_lahir">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Pendidikan Terakhir</label>
                        <select name="pendidikan" class="input-field" x-model="$store.editUser.form.pendidikan">
                            <option value="">Pilih pendidikan</option>
                            <option value="sd">SD</option><option value="smp">SMP</option><option value="sma">SMA/SMK</option>
                            <option value="d3">D3</option><option value="s1">S1</option><option value="s2">S2</option><option value="s3">S3</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Pekerjaan</label>
                        <select name="pekerjaan" class="input-field" x-model="$store.editUser.form.pekerjaan">
                            <option value="">Pilih pekerjaan</option>
                            <option value="dokter">Dokter</option><option value="perawat">Perawat</option><option value="bidan">Bidan</option>
                            <option value="karyawan">Karyawan</option><option value="ibu_rt">Ibu Rumah Tangga</option><option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Agama</label>
                        <select name="agama" class="input-field" x-model="$store.editUser.form.agama">
                            <option value="">Pilih agama</option>
                            <option value="islam">Islam</option><option value="protestan">Protestan</option><option value="katolik">Katolik</option>
                            <option value="hindu">Hindu</option><option value="buddha">Buddha</option><option value="konghucu">Konghucu</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Golongan Darah</label>
                        <select name="gol_darah" class="input-field" x-model="$store.editUser.form.gol_darah">
                            <option value="">Pilih golongan darah</option>
                            <option value="a">A</option><option value="b">B</option><option value="ab">AB</option><option value="o">O</option>
                        </select>
                    </div>

                    {{-- HIRARKI WILAYAH --}}
                    <div>
                        <label class="block text-sm font-medium">Provinsi</label>
                        <select name="prov_id" class="input-field"
                                x-model="$store.editUser.form.prov_id"
                                @change="filterKota($store.editUser.form.prov_id)">
                            <option value="" disabled>Pilih Provinsi</option>
                            @foreach ($provinsis as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Kab/Kota</label>
                        <select name="kota_id" class="input-field"
                                x-model="$store.editUser.form.kota_id"
                                @change="filterKec($store.editUser.form.kota_id)">
                            <option value="" disabled>Pilih Kab/Kota</option>
                            @foreach ($kota as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Kecamatan</label>
                        <select name="kec_id" class="input-field"
                                x-model="$store.editUser.form.kec_id"
                                @change="filterKel($store.editUser.form.kec_id)">
                            <option value="" disabled>Pilih Kecamatan</option>
                            @foreach ($kec as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Kelurahan</label>
                        <select name="kelurahan_id" class="input-field"
                                x-model="$store.editUser.form.kelurahan_id">
                            <option value="" disabled>Pilih Kelurahan</option>
                            @foreach ($desa as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">Alamat Rumah</label>
                        <textarea name="alamat_rumah" rows="2" class="input-field"
                                  x-model="$store.editUser.form.alamat_rumah"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">No Telp/HP</label>
                        <input name="no_telp" type="text" class="input-field" x-model="$store.editUser.form.no_telp">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Puskesmas</label>
                        <select name="puskesmas_id" class="input-field"
                                x-model="$store.editUser.form.puskesmas_id"
                                @change="filterFaskesRujukan($store.editUser.form.kota_id)">
                            <option value="">Pilih Puskesmas</option>
                            @foreach ($puskesmas as $p)
                                <option value="{{ $p->id }}">{{ $p->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Faskes Rujukan</label>
                        <select name="faskes_rujukan_id" class="input-field"
                                x-model="$store.editUser.form.faskes_rujukan_id">
                            <option value="">Pilih Rujukan</option>
                            @foreach ($rujukan as $r)
                                <option value="{{ $r->id }}">{{ $r->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">No JKN</label>
                        <input name="no_jkn" type="text" class="input-field" x-model="$store.editUser.form.no_jkn">
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" class="btn-secondary" @click="closeEdit()">Batal</button>
                    <button type="submit" class="btn-primary">Simpan</button>
                </div>
            </form>

            {{-- ========== FORM PUSKESMAS ========== --}}
            <form x-show="$store.editUser.type==='puskesmas'"
                  method="POST"
                  :action="`{{ route('pengguna.puskesmas.update', '_ID_') }}`.replace('_ID_', $store.editUser.form.id ?? '')"
                  class="space-y-4">
                @csrf @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Nama Lengkap</label>
                        <input name="name" type="text" class="input-field" x-model="$store.editUser.form.nama" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">NIK</label>
                        <input name="nik" type="text" class="input-field" x-model="$store.editUser.form.nik" required>
                    </div>

                    {{-- Tidak ada: tpt/tgl lahir, pendidikan, pekerjaan, agama, gol_darah, no_jkn, faskes_rujukan_id --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">Alamat Rumah</label>
                        <textarea name="alamat_rumah" rows="2" class="input-field"
                                  x-model="$store.editUser.form.alamat_rumah"></textarea>
                    </div>

                    {{-- (Opsional) domisili staf --}}
                    <div>
                        <label class="block text-sm font-medium">Provinsi</label>
                        <select name="prov_id" class="input-field"
                                x-model="$store.editUser.form.prov_id"
                                @change="filterKota($store.editUser.form.prov_id)">
                            <option value="" disabled>Pilih Provinsi</option>
                            @foreach ($provinsis as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Kab/Kota</label>
                        <select name="kota_id" class="input-field"
                                x-model="$store.editUser.form.kota_id"
                                @change="filterKec($store.editUser.form.kota_id)">
                            <option value="" disabled>Pilih Kab/Kota</option>
                            @foreach ($kota as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Kecamatan</label>
                        <select name="kec_id" class="input-field"
                                x-model="$store.editUser.form.kec_id"
                                @change="filterKel($store.editUser.form.kec_id)">
                            <option value="" disabled>Pilih Kecamatan</option>
                            @foreach ($kec as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Kelurahan</label>
                        <select name="kelurahan_id" class="input-field"
                                x-model="$store.editUser.form.kelurahan_id">
                            <option value="" disabled>Pilih Kelurahan</option>
                            @foreach ($desa as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">No Telp</label>
                        <input name="no_telp" type="text" class="input-field" x-model="$store.editUser.form.no_telp">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Puskesmas</label>
                        <select name="puskesmas_id" class="input-field" x-model="$store.editUser.form.puskesmas_id">
                            <option value="">Pilih Puskesmas</option>
                            @foreach ($puskesmas as $p)
                                <option value="{{ $p->id }}">{{ $p->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Jabatan</label>
                        <select name="jabatan_id" class="input-field" x-model="$store.editUser.form.jabatan_id" required>
                            <option value="">Pilih Jabatan</option>
                            @foreach ($daftarJabatan as $j)
                                <option value="{{ $j->id }}">{{ $j->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" class="btn-secondary" @click="closeEdit()">Batal</button>
                    <button type="submit" class="btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </x-modal>

    {{-- ===================== SCRIPTS ===================== --}}
    <x-slot name="scripts">
        <script>
            // ========== Alpine Global Store + Open/Close Modal ==========
            document.addEventListener('alpine:init', () => {
                const defaults = () => ({
                    id: null, user_id: null,
                    nama: '', nik: '', no_telp: '', alamat_rumah: '',
                    prov_id: '', kota_id: '', kec_id: '', kelurahan_id: '',
                    puskesmas_id: '', faskes_rujukan_id: '',
                    // khusus ibu
                    no_jkn: '', tempat_lahir: '', tanggal_lahir: '',
                    pendidikan: '', pekerjaan: '', agama: '', gol_darah: '',
                    // khusus puskesmas
                    jabatan_id: ''
                });

                Alpine.store('editUser', {
                    type: 'ibu',
                    form: defaults(),
                    set(type, item = {}) {
                        this.type = type;
                        this.form = Object.assign(defaults(), item);
                    },
                    reset() { this.set('ibu', {}); }
                });

                window.openEdit = function (type, item) {
                    Alpine.store('editUser').set(type, item);
                    // Prefill dependent selects (load options, keep selected)
                    setTimeout(() => {
                        const f = Alpine.store('editUser').form;
                        if (f.prov_id) filterKota(f.prov_id, f.kota_id);
                        if (f.kota_id) filterKec(f.kota_id, f.kec_id);
                        if (f.kec_id)  filterKel(f.kec_id, f.kelurahan_id);
                        if (f.kota_id) filterFaskesRujukan(f.kota_id, f.faskes_rujukan_id);
                    }, 0);
                    // Jetstream Modal open
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'datasiapa?' }));
                };

                window.closeEdit = function () {
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'datasiapa?' }));
                };
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
                return document.getElementById('editModal') || document;
            }

            // ========== AJAX: Wilayah & Faskes ==========
            async function filterKota(provId, selectedValue = null) {
                try {
                    const routeUrl = "{{ route('kota.filter') }}";
                    const req = new Fetch(routeUrl);
                    req.method = 'GET';
                    req.bodyObject = { provId };
                    const res = await req.run();

                    if (res.ack === 'ok') {
                        const root = getModalRoot();
                        const el = root.querySelector('select[name="kota_id"]');
                        fillSelect(el, res.data, 'Pilih Kab/Kota');
                        if (selectedValue) {
                            el.value = selectedValue;
                            // trigger next level if needed
                            await filterKec(selectedValue, Alpine.store('editUser').form.kec_id);
                        }
                    } else {
                        ALERT(res.message, res.ack);
                    }
                } catch (e) { console.log(e); }
            }

            async function filterKec(kotaId, selectedValue = null) {
                try {
                    const routeUrl = "{{ route('kecamatan.filter') }}";
                    const req = new Fetch(routeUrl);
                    req.method = 'GET';
                    req.bodyObject = { kotaId };
                    const res = await req.run();

                    if (res.ack === 'ok') {
                        const root = getModalRoot();
                        const el = root.querySelector('select[name="kec_id"]');
                        fillSelect(el, res.data, 'Pilih Kecamatan');
                        if (selectedValue) {
                            el.value = selectedValue;
                            await filterKel(selectedValue, Alpine.store('editUser').form.kelurahan_id);
                        }
                    } else {
                        ALERT(res.message, res.ack);
                    }
                } catch (e) { console.log(e); }
            }

            async function filterKel(kecId, selectedValue = null) {
                try {
                    const routeUrl = "{{ route('desa.filter') }}";
                    const req = new Fetch(routeUrl);
                    req.method = 'GET';
                    req.bodyObject = { kecId };
                    const res = await req.run();

                    if (res.ack === 'ok') {
                        const root = getModalRoot();

                        // Kelurahan
                        const kelSelect = root.querySelector('select[name="kelurahan_id"]');
                        const kelList = res.data?.kelurahan || res.data || [];
                        fillSelect(kelSelect, kelList, 'Pilih Kelurahan');
                        if (selectedValue) kelSelect.value = selectedValue;

                        // Puskesmas (opsional)
                        const puskList = res.data?.puskesmas || [];
                        const puskSelect = root.querySelector('select[name="puskesmas_id"]');
                        if (puskSelect && Array.isArray(puskList)) {
                            // puskesmas list: valueKey=id, labelKey=nama
                            fillSelect(puskSelect, puskList, 'Pilih Puskesmas', 'id', 'nama');
                            const current = Alpine.store('editUser').form.puskesmas_id;
                            if (current) puskSelect.value = current;
                        }
                    } else {
                        ALERT(res.message, res.ack);
                    }
                } catch (e) { console.log(e); }
            }

            async function filterFaskesRujukan(kotaId, selectedValue = null) {
                try {
                    const routeUrl = "{{ route('faskes.filter') }}";
                    const req = new Fetch(routeUrl);
                    req.method = 'GET';
                    req.bodyObject = { kota_id: kotaId };
                    const res = await req.run();

                    if (res.ack === 'ok') {
                        const root = getModalRoot();
                        const faskesSelect = root.querySelector('select[name="faskes_rujukan_id"]');
                        fillSelect(faskesSelect, res.data, 'Pilih Rujukan', 'id', 'nama');
                        if (selectedValue) faskesSelect.value = selectedValue;
                    } else {
                        ALERT(res.message, res.ack);
                    }
                } catch (e) { console.log(e); }
            }
        </script>

        <style>[x-cloak]{display:none !important;}</style>
    </x-slot>
</x-app-layout>
