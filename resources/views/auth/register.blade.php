<x-guest-layout title="Daftar | Skrining Ibu Hamil">
    @php
    // Otomatis lompat ke step yang error (opsional, aman diabaikan kalau tak perlu)
    $errorStep = 0;
    if ($errors->any()) {
    $first = array_key_first($errors->toArray());
    $step2Fields = ['name','nik','tempat_lahir','tanggal_lahir','pendidikan','pekerjaan','agama','gol_darah','prov_id','kota_id','kec_id','kelurahan_id','alamat_rumah','no_telp','no_jkn','puskesmas_id','faskes_rujukan_id'];
    $step3Fields = ['kehamilan_ke','jumlah_anak_lahir_hidup','riwayat_keguguran','penyakit_ids','penyakit_lainnya','riwayat_penyakit_text'];
    if (Str::contains($first, $step2Fields)) $errorStep = 1;
    elseif (Str::contains($first, $step3Fields)) $errorStep = 2;
    elseif (Str::contains($first, ['suami','anak'])) $errorStep = 3;
    }
    @endphp
    <div class="mb-8 p-6 md:p-2 rounded-xl border border-gray-200 bg-gray-50 shadow-md"
        x-data="Object.assign(registerWizard({ initialStep: {{ $errorStep }} }), { isSubmitting:false })">

        <h3 class="text-xl font-semibold mb-4 text-center text-gray-700">Form Pendaftaran</h3>

        <div class="bg-white p-6 rounded-xl shadow-md">
            <h4 class="text-2xl font-bold text-center text-gray-800 mb-6">Daftar Akun Baru</h4>

            {{-- FLASH / ERROR SUMMARY --}}
            @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-300 bg-green-50 text-green-800 p-3">
                {{ session('success') }}
            </div>
            @endif

            @if (session('danger'))
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 text-red-800 p-3">
                {{ session('danger') }}
            </div>
            @endif

            @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 text-red-800 p-3">
                <div class="font-semibold mb-1">Ada beberapa kesalahan pada isian kamu:</div>
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $msg)
                    <li>{{ $msg }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Progress -->
            <div class="mb-6">
                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-2 bg-teal-400 transition-all"
                        :style="{ width: ((step+1)/steps.length*100)+'%' }"></div>
                </div>
                <div class="mt-2 text-center text-sm text-gray-600">
                    Langkah <span x-text="step+1"></span> dari <span x-text="steps.length"></span>:
                    <span class="font-medium" x-text="steps[step]"></span>
                </div>
            </div>

            <form method="POST" action="{{ route('register') }}" x-on:submit="isSubmitting = true">
                @csrf

                <!-- ===== STEP 1: AKUN ===== -->
                <section x-show="step===0" x-ref="s0" x-cloak>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Username</label>
                            <input name="username" type="text" class="input-field" x-bind:required="step===0"
                                value="{{ old('username') }}" autocomplete="username">
                            <x-input-error :messages="$errors->get('username')" class="mt-2" />
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                            <input name="email" type="email" class="input-field" x-bind:required="step===0"
                                value="{{ old('email') }}" autocomplete="email">
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div x-data="{ show: false }">
                            <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password</label>

                            <div class="relative">
                                <input
                                    id="password"
                                    name="password"
                                    :type="show ? 'text' : 'password'"
                                    class="input-field pr-10"
                                    required
                                    autocomplete="current-password">

                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                    @click="show = !show"
                                    :aria-label="show ? 'Sembunyikan password' : 'Lihat password'"
                                    :title="show ? 'Sembunyikan' : 'Lihat'">
                                    <!-- eye -->
                                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.036 12.322a1.012 1.012 0 010-.644C3.423 7.51 7.454 5 12 5s8.577 2.51 9.964 6.678c.07.214.07.43 0 .644C20.577 16.49 16.546 19 12 19s-8.577-2.51-9.964-6.678z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <!-- eye-off -->
                                    <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3.98 8.223A10.477 10.477 0 001.934 12C3.29 15.64 7.3 18 12 18c1.66 0 3.23-.32 4.64-.9M9.88 9.88A3 3 0 0114.12 14.12M6.1 6.1L17.9 17.9M9.88 9.88L6.1 6.1m8.24 8.24L17.9 17.9" />
                                    </svg>
                                </button>
                            </div>

                            @error('password')
                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div x-data="{ show: false }">
                            <label for="password_confirmation" class="block text-gray-700 text-sm font-medium mb-2">Konfirmasi Password</label>

                            <div class="relative">
                                <input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    :type="show ? 'text' : 'password'"
                                    class="input-field pr-10"
                                    required
                                    autocomplete="current-password">

                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                    @click="show = !show"
                                    :aria-label="show ? 'Sembunyikan password' : 'Lihat password'"
                                    :title="show ? 'Sembunyikan' : 'Lihat'">
                                    <!-- eye -->
                                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.036 12.322a1.012 1.012 0 010-.644C3.423 7.51 7.454 5 12 5s8.577 2.51 9.964 6.678c.07.214.07.43 0 .644C20.577 16.49 16.546 19 12 19s-8.577-2.51-9.964-6.678z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <!-- eye-off -->
                                    <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3.98 8.223A10.477 10.477 0 001.934 12C3.29 15.64 7.3 18 12 18c1.66 0 3.23-.32 4.64-.9M9.88 9.88A3 3 0 0114.12 14.12M6.1 6.1L17.9 17.9M9.88 9.88L6.1 6.1m8.24 8.24L17.9 17.9" />
                                    </svg>
                                </button>
                            </div>

                            @error('password_confirmation')
                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </section>

                <!-- ===== STEP 2: DATA IBU ===== -->
                <section x-show="step===1" x-ref="s1" x-cloak>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Nama Lengkap</label>
                            <input name="name" type="text" class="input-field" x-bind:required="step===1" value="{{ old('name') }}">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">NIK</label>
                            <input name="nik" type="text" class="input-field" x-bind:required="step===1" maxlength="16" value="{{ old('nik') }}">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Tempat Lahir</label>
                            <input name="tempat_lahir" type="text" class="input-field" x-bind:required="step===1" value="{{ old('tempat_lahir') }}">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Tanggal Lahir</label>
                            <input x-model="ibu.tanggal_lahir" name="tanggal_lahir" type="date" class="input-field" x-bind:required="step===1" value="{{ old('tanggal_lahir') }}">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Pendidikan Terakhir</label>
                            <select name="pendidikan" class="input-field" x-bind:required="step===1">
                                <option value="" disabled selected>Pilih pendidikan</option>
                                <option value="sd">SD</option>
                                <option value="smp">SMP</option>
                                <option value="sma">SMA/SMK</option>
                                <option value="d3">D3</option>
                                <option value="s1">S1</option>
                                <option value="s2">S2</option>
                                <option value="s3">S3</option>
                                <option value="lainnya">LAINNYA</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Pekerjaan</label>
                            <select name="pekerjaan" class="input-field" x-bind:required="step===1">
                                <option value="" selected disabled> Pilih Pekerjaan</option>
                                <option value="dokter">Dokter</option>
                                <option value="perawat">Perawat</option>
                                <option value="bidan">Bidan</option>
                                <option value="dosen">Dosen</option>
                                <option value="mahasiswa">Mahasiswa</option>
                                <option value="karyawan">Karyawan</option>
                                <option value="ibu_rt">Ibu Rumah Tangga</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Agama</label>
                            <select name="agama" class="input-field" x-bind:required="step===1">
                                <option value="" disabled selected>Pilih agama</option>
                                <option value="islam">Islam</option>
                                <option value="protestan">Protestan</option>
                                <option value="katolik">Katolik</option>
                                <option value="hindu">Hindu</option>
                                <option value="buddha">Buddha</option>
                                <option value="konghucu">Konghucu</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Golongan Darah</label>
                            <select name="gol_darah" class="input-field" x-bind:required="step===1">
                                <option value="" selected disabled>Pilih Golongan Darah</option>
                                <option value="a">A</option>
                                <option value="b">B</option>
                                <option value="ab">AB</option>
                                <option value="o">O</option>
                            </select>
                        </div>

                        <div> 
                            <label class="block text-gray-700 text-sm font-medium mb-2">Provinsi</label>
                            <select name="prov_id" class="input-field" x-bind:required="step===1" onchange="filterKota(this.value)">
                                <option value="" selected disabled>Pilih Provinsi</option>
                                @foreach ($provinsis as $item)
                                <option value="{{ $item->code }}" {{ old('prov_id') == $item->id ? 'selected' : '' }}>
                                    {{ $item->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Kabupaten/Kota</label>
                            <select name="kota_id" class="input-field" x-bind:required="step===1" onchange="filterKec(this.value)">
                                <option value="" selected disabled>Pilih Kabupaten/Kota</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Kecamatan</label>
                            <select name="kec_id" class="input-field" x-bind:required="step===1" onchange="filterKel(this.value)">
                                <option value="" selected disabled>Pilih Kecamatan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Kelurahan</label>
                            <select name="kelurahan_id" class="input-field" x-bind:required="step===1">
                                <option value="" selected disabled>Pilih Kelurahan</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-gray-700 text-sm font-medium mb-2">Alamat Rumah</label>
                            <textarea name="alamat_rumah" class="input-field" oninput="oninputKeterangan(this, event)" rows="2" x-bind:required="step===1">{{ old('alamat_rumah') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">No Telp/HP</label>
                            <input name="no_telp" type="text" inputmode="tel" class="input-field" value="{{ old('no_telp') }}">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">No JKN</label>
                            <input name="no_jkn" type="text" inputmode="numeric" class="input-field" maxlength="13" value="{{ old('no_jkn') }}">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Fasilitas Kesehatan Tk1</label>
                            <select name="puskesmas_id" class="input-field" onchange="filterFaskesRujukan(this.value)">
                                <option value="" selected disabled> Pilih Puskesmas</option>

                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Fasilitas Kesehatan Rujukan</label>
                            <select name="faskes_rujukan_id" class="input-field">
                                <option value="" selected disabled> Pilih Rujukan</option>
                            </select>
                        </div>
                    </div>
                </section>

                <!-- ===== STEP 3: RIWAYAT ===== -->
                <section x-show="step===2" x-ref="s2" x-cloak>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Kehamilan Ke</label>
                            <input name="kehamilan_ke" type="number" min="1" class="input-field" x-bind:required="step===2" value="{{ old('kehamilan_ke',1) }}">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Jumlah Anak Lahir Hidup</label>
                            <input name="jumlah_anak_lahir_hidup" type="number" min="0" class="input-field" x-model.number="jumlahAnak" x-bind:required="step===2">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Riwayat Keguguran</label>
                            <input name="riwayat_keguguran" type="number" min="0" class="input-field" value="{{ old('riwayat_keguguran',0) }}">
                        </div>

                        @php
                        // opsi penyakit
                        $penyakitOptions = [
                        1 => 'Diabetes Melitus',
                        2 => 'Hipertensi',
                        3 => 'Asma',
                        4 => 'Penyakit Jantung',
                        5 => 'Lainnya',
                        ];

                        // nilai terpilih (untuk old input / edit form)
                        $selected = old('penyakit_ids', $selectedPenyakitIds ?? []);
                        @endphp

                        <div x-data="{ showLainnya: {{ in_array(5, $selected) ? 'true' : 'false' }} }">
                            <label class="block text-gray-700 text-sm font-medium mb-2">Penyakit</label>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                @foreach ($penyakitOptions as $val => $label)
                                <label class="inline-flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        name="penyakit_ids[]"
                                        value="{{ $val }}"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        @checked(in_array($val, $selected))
                                        @if($val===5) x-on:change="showLainnya = $event.target.checked" @endif>
                                    <span>{{ $label }}</span>
                                </label>
                                @endforeach
                            </div>

                            {{-- Field tambahan saat "Lainnya" dicentang --}}
                            <div class="mt-3" x-show="showLainnya" x-cloak>
                                <label class="block text-gray-700 text-sm font-medium mb-2">Sebutkan penyakit lainnya</label>
                                <input
                                    type="text"
                                    name="penyakit_lainnya"
                                    class="input-field"
                                    value="{{ old('penyakit_lainnya') }}"
                                    placeholder="Contoh: GERD">
                            </div>

                            {{-- error message --}}
                            <x-input-error :messages="$errors->get('penyakit_ids')" class="mt-2" />
                            <x-input-error :messages="$errors->get('penyakit_lainnya')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-gray-700 text-sm font-medium mb-2">Catatan Riwayat Penyakit (opsional)</label>
                            <textarea name="riwayat_penyakit_text" oninput="oninputKeterangan(this, event)" class="input-field" rows="2">{{ old('riwayat_penyakit_text') }}</textarea>
                        </div>
                    </div>
                </section>

                <!-- ===== STEP 4: SUAMI & ANAK ===== -->
                <section x-show="step===3" x-ref="s3" x-cloak>
                    <h5 class="font-semibold text-gray-700 mb-3">Data Suami</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Nama Suami</label>
                            <input name="suami[nama]" type="text" class="input-field" x-bind:required="step===3">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">NIK Suami</label>
                            <input name="suami[nik]" type="text" class="input-field" maxlength="16">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Tempat Lahir</label>
                            <input name="suami[tempat_lahir]" type="text" class="input-field">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Tanggal Lahir</label>
                            <input name="suami[tanggal_lahir]" type="date" class="input-field">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Pendidikan</label>
                            <select name="suami[pendidikan]" class="input-field">
                                <option value="" selected disabled> Pilih (opsional)</option>
                                <option value="sd">SD</option>
                                <option value="smp">SMP</option>
                                <option value="sma">SMA/SMK</option>
                                <option value="d3">D3</option>
                                <option value="s1">S1</option>
                                <option value="s2">S2</option>
                                <option value="s3">S3</option>
                                <option value="lainnya">LAINNYA</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Pekerjaan</label>
                            <select name="suami[pekerjaan]" class="input-field">
                                <option value="" selected disabled> Pilih (opsional)</option>
                                <option value="dokter">Dokter</option>
                                <option value="perawat">Perawat</option>
                                <option value="petani">Petani</option>
                                <option value="dosen">Dosen</option>
                                <option value="karyawan">Karyawan</option>
                                <option value="wiraswasta">Wiraswasta</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Agama</label>
                            <select name="suami[agama]" class="input-field">
                                <option value="" selected disabled> Pilih Agama</option>
                                <option value="islam">Islam</option>
                                <option value="protestan">Protestan</option>
                                <option value="katolik">Katolik</option>
                                <option value="hindu">Hindu</option>
                                <option value="buddha">Buddha</option>
                                <option value="konghucu">Konghucu</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">No Telp</label>
                            <input name="suami[no_telp]" type="tel" class="input-field">
                        </div>
                    </div>

                    <template x-if="jumlahAnak > 0">
                        <div>
                            <h5 class="font-semibold text-gray-700 mb-3">Data Anak</h5>
                            <div class="space-y-4">
                                <template x-for="(a,i) in anak" :key="i">
                                    <div class="rounded-lg border border-gray-200 p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="font-medium text-gray-700">Anak <span x-text="i+1"></span></div>
                                            <button type="button" class="text-red-600 text-sm" @click="removeAnak(i)" x-show="anak.length>1">Hapus</button>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-gray-700 text-sm font-medium mb-2">Nama</label>
                                                <input class="input-field" type="text" :name="`anak[${i}][nama]`">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 text-sm font-medium mb-2">NIK</label>
                                                <input class="input-field" type="text" maxlength="16" :name="`anak[${i}][nik]`">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 text-sm font-medium mb-2">Tanggal Lahir</label>
                                                <input class="input-field" type="date" :name="`anak[${i}][tanggal_lahir]`">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 text-sm font-medium mb-2">Jenis Kelamin</label>
                                                <select class="input-field" :name="`anak[${i}][jenis_kelamin]`">
                                                    <option value="" selected disabled> Pilih</option>
                                                    <option value="L">Laki-laki</option>
                                                    <option value="P">Perempuan</option>
                                                </select>
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-gray-700 text-sm font-medium mb-2">No JKN (opsional)</label>
                                                <input class="input-field" type="text" maxlength="13" :name="`anak[${i}][no_jkn]`">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-3">
                                <button type="button" class="btn-secondary" @click="addAnak()">+ Tambah Anak</button>
                            </div>
                        </div>
                    </template>
                </section>

                <!-- NAV -->
                <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between mt-8 mb-3 md:mb-0">
                    <button type="button" class="btn-secondary w-full sm:w-auto"
                        @click="prev()" x-show="step>0"
                        :disabled="isSubmitting" :class="isSubmitting ? 'opacity-60 cursor-not-allowed' : ''">
                        Kembali
                    </button>
                    <div class="flex gap-3 w-full sm:w-auto sm:ml-auto">
                        <button type="button" class="btn-primary w-full sm:w-auto"
                            @click="next()" x-show="step < steps.length-1"
                            :disabled="isSubmitting" :class="isSubmitting ? 'opacity-60 cursor-not-allowed' : ''">
                            Lanjut
                        </button>

                        <button type="submit" class="btn-primary w-full sm:w-auto relative"
                            x-show="step === steps.length-1"
                            :disabled="isSubmitting" :class="isSubmitting ? 'opacity-60 cursor-not-allowed' : ''">
                            <span x-show="!isSubmitting">Buat Akun</span>
                            <span x-show="isSubmitting" class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
                                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" class="opacity-75" />
                                </svg>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </div>

            </form>
            <p class="text-center text-gray-600 text-sm mt-4">Sudah punya akun?
                <a href="{{ route('login', [], false) }}" data-swup-preload class="text-blue-600 font-medium hover:underline">Masuk</a>
            </p>
        </div>
    </div>

    @section('scripts')
    <script>
        async function filterKota(provId) {
            try {
                const requestData = {
                    provId: provId
                };
                const routeUrl = "{{ route('kota.filter') }}";
                const fetchKota = new Fetch(routeUrl);
                fetchKota.method = 'GET';
                fetchKota.bodyObject = requestData;
                const hasil = await fetchKota.run();
                if (hasil.ack === "ok") {

                    const kotaSelect = document.querySelector('select[name="kota_id"]');
                    kotaSelect.innerHTML = '<option value="" selected disabled>Pilih Kabupaten/Kota</option>';
                    hasil.data.forEach(kota => {
                        kotaSelect.innerHTML += `<option value="${kota.code}">${kota.name}</option>`;
                    });
                } else {
                    ALERT(hasil.message, hasil.ack);
                }
            } catch (error) {
                console.log("🚀 ~ filterKota ~ error:", error);
            }
        }

        async function filterKec(kotaId) {
            try {
                const requestData = {
                    kotaId: kotaId
                };
                const routeUrl = "{{ route('kecamatan.filter') }}";
                const fetchKec = new Fetch(routeUrl);
                fetchKec.method = 'GET';
                fetchKec.bodyObject = requestData;
                const hasil = await fetchKec.run();
                if (hasil.ack === "ok") {

                    const kecSelect = document.querySelector('select[name="kec_id"]');
                    kecSelect.innerHTML = '<option value="" selected disabled>Pilih Kecamatan</option>';
                    hasil.data.forEach(kec => {
                        kecSelect.innerHTML += `<option value="${kec.code}">${kec.name}</option>`;
                    });

                } else {
                    ALERT(hasil.message, hasil.ack);
                }
            } catch (error) {
                console.log("🚀 ~ filterKec ~ error:", error);
            }
        }

        async function filterKel(kecId) {
            try {
                const requestData = {
                    kecId: kecId
                };
                const routeUrl = "{{ route('desa.filter') }}";
                const fetchKel = new Fetch(routeUrl);
                fetchKel.method = 'GET';
                fetchKel.bodyObject = requestData;
                const hasil = await fetchKel.run();
                if (hasil.ack === "ok") {
                    const kelurahan = hasil.data.kelurahan;
                    const puskesmas = hasil.data.puskesmas;

                    const kelSelect = document.querySelector('select[name="kelurahan_id"]');
                    kelSelect.innerHTML = '<option value="" selected disabled>Pilih Kelurahan</option>';
                    kelurahan.forEach(kel => {
                        kelSelect.innerHTML += `<option value="${kel.code}">${kel.name}</option>`;
                    });

                    const puskesmasSelect = document.querySelector('select[name="puskesmas_id"]');
                    puskesmasSelect.innerHTML = '<option value="" selected disabled>Pilih Puskesmas</option>';
                    puskesmas.forEach(puskesmas => {
                        puskesmasSelect.innerHTML += `<option value="${puskesmas.id}">${puskesmas.nama}</option>`;
                    });
                } else {
                    ALERT(hasil.message, hasil.ack);
                }
            } catch (error) {
                console.log("🚀 ~ filterKel ~ error:", error);
            }
        }

        async function filterFaskesRujukan() {
            try {
                const kota = document.querySelector('select[name="kota_id"]').value;
                const requestData = {
                    kota_id: kota
                };

                const routeUrl = "{{ route('faskes.filter') }}";
                const fetchKel = new Fetch(routeUrl);
                fetchKel.method = 'GET';
                fetchKel.bodyObject = requestData;

                const hasil = await fetchKel.run();

                if (hasil.ack === "ok") {
                    const faskesSelect = document.querySelector('select[name="faskes_rujukan_id"]');

                    // reset isi select
                    faskesSelect.innerHTML = '';
                    const defaultOpt = new Option('Pilih Rujukan', '', true, false);
                    defaultOpt.disabled = true;
                    faskesSelect.add(defaultOpt);

                    const seen = new Set(); // untuk mendeteksi duplikat
                    const frag = document.createDocumentFragment();

                    (hasil.data || []).forEach(item => {
                        const f = item;
                        if (!f?.id || !f?.nama) return;

                        // kunci deduplikasi: id (paling aman)
                        const key = String(f.id);
                        if (seen.has(key)) return;

                        seen.add(key);
                        frag.appendChild(new Option(f.nama, f.id));
                    });

                    faskesSelect.appendChild(frag);
                } else {
                    ALERT(hasil.message, hasil.ack);
                }
            } catch (error) {
                console.log("🚀 ~ filterFaskesRujukan ~ error:", error);
            }
        }
    </script>
    @endsection
</x-guest-layout>