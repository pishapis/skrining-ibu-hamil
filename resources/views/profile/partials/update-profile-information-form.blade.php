<section>

    @php
    $errorStep = 0;
    if ($errors->any()) {
    $first = array_key_first($errors->toArray());
    $step2Fields = ['name','tempat_lahir','tanggal_lahir','pendidikan','pekerjaan','agama','gol_darah','prov_id','kota_id','kec_id','kelurahan_id','alamat_rumah','no_telp','puskesmas_id','faskes_rujukan_id'];
    if (Str::contains($first, $step2Fields)) $errorStep = 1;
    elseif (Str::contains($first, $step3Fields)) $errorStep = 2;
    }
    @endphp
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <div x-data="Object.assign(updateWizard({ initialStep: {{ $errorStep }} }), { isSubmitting:false })">
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

        <form method="post" action="{{ route('profile.update') }}" x-on:submit="isSubmitting = true">
            @csrf

            <!-- ===== STEP 1: AKUN ===== -->
            <section x-show="step===0" x-ref="s0" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Username</label>
                        <input name="username" type="text" class="input-field" x-bind:required="step===0"
                            value="{{ $user->username }}" autocomplete="username" readonly>
                        <x-input-error :messages="$errors->get('username')" class="mt-2" />
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                        <input name="email" type="email" class="input-field" x-bind:required="step===0"
                            value="{{ $user->email }}" autocomplete="email">
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                </div>
            </section>

            <!-- ===== STEP 2: DATA IBU ===== -->
            <section x-show="step===1" x-ref="s1" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Nama Lengkap</label>
                        <input name="name" type="text" class="input-field" x-bind:required="step===1" value="{{ $user->name }}">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Tempat Lahir</label>
                        <input name="tempat_lahir" type="text" class="input-field" x-bind:required="step===1" value="{{ $data_diri->tempat_lahir }}">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Tanggal Lahir</label>
                        <input name="tanggal_lahir" type="date" class="input-field" x-bind:required="step===1" value="{{ date($data_diri->tanggal_lahir) }}">
                    </div>

                    <div>
                        @php
                        $current = old('pendidikan', $data_diri->pendidikan_terakhir ?? null);
                        $options = [
                        'sd' => 'SD', 'smp' => 'SMP', 'sma' => 'SMA/SMK',
                        'd3' => 'D3', 's1' => 'S1', 's2' => 'S2', 's3' => 'S3',
                        'lainnya' => 'LAINNYA',
                        ];
                        @endphp
                        <label class="block text-gray-700 text-sm font-medium mb-2">Pendidikan Terakhir</label>
                        <select name="pendidikan" class="input-field" x-bind:required="step===1">
                            <option value="" disabled @selected($current===null)>Pilih pendidikan</option>
                            @foreach($options as $val => $label)
                            <option value="{{ $val }}" @selected($current===$val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- ==================== PEKERJAAN ==================== --}}
                    @php
                    $pekerjaanNow = old('pekerjaan', $data_diri->pekerjaan ?? null);
                    $pekerjaanOpts = [
                    'dokter' => 'Dokter',
                    'perawat' => 'Perawat',
                    'bidan' => 'Bidan',
                    'dosen' => 'Dosen',
                    'mahasiswa' => 'Mahasiswa',
                    'karyawan' => 'Karyawan',
                    'ibu_rt' => 'Ibu Rumah Tangga',
                    'lainnya' => 'Lainnya',
                    ];
                    @endphp

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Pekerjaan</label>
                        <select name="pekerjaan" class="input-field" x-bind:required="step===1">
                            <option value="" disabled @selected($pekerjaanNow===null)>Pilih Pekerjaan</option>
                            @foreach($pekerjaanOpts as $val => $label)
                            <option value="{{ $val }}" @selected($pekerjaanNow===$val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ==================== AGAMA ==================== --}}
                    @php
                    $agamaNow = old('agama', $data_diri->agama ?? null);
                    $agamaOpts = [
                    'islam' => 'Islam',
                    'protestan' => 'Protestan',
                    'katolik' => 'Katolik',
                    'hindu' => 'Hindu',
                    'buddha' => 'Buddha',
                    'konghucu' => 'Konghucu',
                    'lainnya' => 'Lainnya',
                    ];
                    @endphp

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Agama</label>
                        <select name="agama" class="input-field" x-bind:required="step===1">
                            <option value="" disabled @selected($agamaNow===null)>Pilih agama</option>
                            @foreach($agamaOpts as $val => $label)
                            <option value="{{ $val }}" @selected($agamaNow===$val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ==================== GOLONGAN DARAH ==================== --}}
                    @php
                    $golNow = old('gol_darah', $data_diri->golongan_darah ?? null);
                    $golOpts = [
                    'a' => 'A',
                    'b' => 'B',
                    'ab' => 'AB',
                    'o' => 'O',
                    ];
                    @endphp

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Golongan Darah</label>
                        <select name="gol_darah" class="input-field" x-bind:required="step===1">
                            <option value="" disabled @selected($golNow===null)>Pilih Golongan Darah</option>
                            @foreach($golOpts as $val => $label)
                            <option value="{{ $val }}" @selected($golNow===$val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        @php
                            $currentProv = old('prov_id', $data_diri->kode_prov ?? null);
                        @endphp
                        <label class="block text-gray-700 text-sm font-medium mb-2">Provinsi</label>
                        <select name="prov_id" class="input-field" x-bind:required="step===1" onchange="filterKota(this.value)">
                            <option value="" selected disabled>Pilih Provinsi</option>
                            @foreach ($provinsis as $item)
                                <option value="{{ $item->code }}" @selected((string)$currentProv === (string)$item->code)>
                                    {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        @php
                            $currentCity = old('kota_id', $data_diri->kode_kab ?? null);
                        @endphp
                        <label class="block text-gray-700 text-sm font-medium mb-2">Kabupaten/Kota</label>
                        <select name="kota_id" class="input-field" x-bind:required="step===1" onchange="filterKec(this.value)">
                            <option value="" selected disabled>Pilih Kabupaten/Kota</option>
                             @foreach ($kota as $item)
                                <option value="{{ $item->code }}" @selected((string)$currentCity === (string)$item->code)>
                                    {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        @php
                            $currentKec = old('kec_id', $data_diri->kode_kec ?? null);
                        @endphp
                        <label class="block text-gray-700 text-sm font-medium mb-2">Kecamatan</label>
                        <select name="kec_id" class="input-field" x-bind:required="step===1" onchange="filterKel(this.value)">
                            <option value="" selected disabled>Pilih Kecamatan</option>
                            @foreach ($kec as $item)
                                <option value="{{ $item->code }}" @selected((string)$currentKec === (string)$item->code)>
                                    {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        @php
                            $currentDes = old('kelurahan_id', $data_diri->kode_des ?? null);
                        @endphp
                        <label class="block text-gray-700 text-sm font-medium mb-2">Kelurahan</label>
                        <select name="kelurahan_id" class="input-field" x-bind:required="step===1">
                            <option value="" selected disabled>Pilih Kelurahan</option>
                            @foreach ($desa as $item)
                                <option value="{{ $item->code }}" @selected((string)$currentDes === (string)$item->code)>
                                    {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div> 
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-medium mb-2">Alamat Rumah</label>
                        <textarea name="alamat_rumah" class="input-field" oninput="oninputKeterangan(this, event)" rows="2" x-bind:required="step===1">{{ $data_diri->alamat_rumah }}</textarea>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">No Telp/HP</label>
                        <input name="no_telp" type="text" inputmode="tel" class="input-field" value="{{ $data_diri->no_telp }}">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Fasilitas Kesehatan Tk1</label>
                        <select name="puskesmas_id" class="input-field" onchange="filterFaskesRujukan(this.value)">
                            <option value="" selected disabled> Pilih Puskesmas</option>
                            @foreach ($puskesmas as $item)
                                <option value="{{ $item->id }}" {{ $data_diri->puskesmas_id == $item->id ? 'selected' : '' }}>
                                    {{ $item->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Fasilitas Kesehatan Rujukan</label>
                        <select name="faskes_rujukan_id" class="input-field">
                            <option value="" selected disabled> Pilih Rujukan</option>
                            @foreach ($rujukan as $item)
                                <option value="{{ $item->id }}" {{ $data_diri->faskes_rujukan_id == $item->id ? 'selected' : '' }}>
                                    {{ $item->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
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
                        <span x-show="!isSubmitting">Simpan</span>
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
    </div>
</section>