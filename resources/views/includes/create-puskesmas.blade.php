<form x-cloak
    {{-- :action="`{{ route('pengguna.ibu.update', '_ID_') }}`.replace('_ID_', S.form?.id ?? '' )" --}}
    action="#"
    method="POST"
    class="space-y-4">
    @method('POST')
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium">Nama</label>
            <input name="nama_puskesmas" type="text" class="input-field" value="">
            <x-input-error class="mt-2" :messages="$errors->get('nama_puskesmas')" />
        </div>
 
        <div>
            <label class="block text-sm font-medium">Alamat</label>
            <input name="alamat_puskesmas" type="text" class="input-field" value="">
            <x-input-error class="mt-2" :messages="$errors->get('alamat_puskesmas')" />
        </div>

        {{-- HIRARKI WILAYAH --}}
        <div>
            <label class="block text-sm font-medium">Provinsi</label>
            <select name="prov_id_puskesmas" class="input-field"
                onchange="window.filterKota(this.value)">
                <option value="" selected disabled>Pilih Provinsi</option>
                @foreach ($provinsis as $code => $name)
                <option value="{{ $code }}">{{ $name }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('prov_id_puskesmas')" />
        </div>
        <div>
            <label class="block text-sm font-medium">Kab/Kota</label>
            <select name="kota_id_puskesmas" class="input-field"
                onchange="window.filterKec(this.value)">
                <option value="" selected disabled>Pilih Kab/Kota</option>
                @foreach ($kota as $code => $name)
                <option value="{{ $code }}">{{ $name }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('kota_id_puskesmas')" />
        </div>
        <div>
            <label class="block text-sm font-medium">Kecamatan</label>
            <select name="kec_id_puskesmas" class="input-field">
                <option value="" selected disabled>Pilih Kecamatan</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('kec_id_puskesmas')" />
        </div>

        <div>
            <label class="block text-sm font-medium">Faskes Rujukan</label>
            <select name="faskes_rujukan_id" class="input-field">
                <option value="">Pilih Rujukan</option>
                @foreach ($rs as $r)
                <option value="{{ $r->id }}">{{ $r->nama }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="flex justify-end gap-3 pt-4">
        <button type="button" class="btn-secondary" x-on:click="show = false">Batal</button>
        <button type="submit" class="btn-primary">Simpan</button>
    </div>
</form>