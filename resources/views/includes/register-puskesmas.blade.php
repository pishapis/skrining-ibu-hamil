@php
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
@php
$role = request('role', 'ibu'); // 'ibu' (default) atau 'puskesmas'
$isPuskesmas = $role === 'puskesmas';

// Definisikan langkah wizard per role
$wizardSteps = $isPuskesmas
? ['Akun', 'Data Puskesmas']
: ['Akun', 'Data Ibu', 'Riwayat', 'Suami & Anak'];
@endphp

<div class="mb-8 p-6 md:p-2 rounded-xl border border-gray-200 bg-gray-50 shadow-md"
    x-data="Object.assign(registerWizardPuskesmas({ initialStep: {{ $errorStep }} }), { isSubmitting:false })">

    <h3 class="text-xl font-semibold mb-4 text-center text-gray-700">Form Pendaftaran</h3>

    <div class="bg-white p-6 rounded-xl shadow-md">
        <h4 class="text-2xl font-bold text-center text-gray-800 mb-6">
            {{ $isPuskesmas ? 'Tambah Akun Puskesmas' : 'Daftar Akun Baru' }}
        </h4>

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

        <form method="POST" action="{{ route('pengguna.puskesmas.create') }}" x-on:submit="isSubmitting = true">
            @method('POST')
            @csrf
            <!-- ===== STEP 1: AKUN ===== -->
            <section x-show="step===0" x-ref="s0" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Username</label>
                        <input name="username_pus_create" type="text" class="input-field" x-bind:required="step===0"
                            value="{{ old('username_pus_create') }}" autocomplete="username_pus_create">
                        <x-input-error :messages="$errors->get('username_pus_create')" class="mt-2" />
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                        <input name="email_pus_create" type="email" class="input-field" x-bind:required="step===0"
                            value="{{ old('email_pus_create') }}" autocomplete="email">
                        <x-input-error :messages="$errors->get('email_pus_create')" class="mt-2" />
                    </div>
                    <div x-data="{ show: false }">
                        <label for="password_pus_create" class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                        <div class="relative">
                            <input
                                id="password_pus_create"
                                name="password_pus_create"
                                :type="show ? 'text' : 'password'"
                                class="input-field pr-10"
                                required
                                autocomplete="current-password_pus_create">

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

                        @error('password_pus_create')
                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div x-data="{ show: false }">
                        <label for="password_confirmation_pus_create" class="block text-gray-700 text-sm font-medium mb-2">Konfirmasi Password</label>

                        <div class="relative">
                            <input
                                id="password_confirmation_pus_create"
                                name="password_confirmation_pus_create"
                                :type="show ? 'text' : 'password'"
                                class="input-field pr-10"
                                required
                                autocomplete="current-password_confirmation_pus_create">

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

                        @error('password_confirmation_pus_create')
                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </section>

            <section x-show="step===1" x-ref="s1" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Nama Lengkap</label>
                        <input name="name_pus_create" type="text" class="input-field" value="{{ old('name_pus_create') }}" x-bind:required="step===1">
                        <x-input-error :messages="$errors->get('name_pus_create')" class="mt-2" />
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">NIK</label>
                        <input name="nik_pus_create" type="text" maxlength="16" class="input-field" value="{{ old('nik_pus_create') }}" x-bind:required="step===1">
                        <x-input-error :messages="$errors->get('nik_pus_create')" class="mt-2" />

                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-medium mb-2">Alamat Rumah</label>
                        <textarea name="alamat_rumah_pus_create" class="input-field" rows="2">{{ old('alamat_rumah_pus_create') }}</textarea>
                        <x-input-error :messages="$errors->get('alamat_rumah_pus_create')" class="mt-2" />
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Provinsi</label>
                        <select name="prov_id_pus_create" class="input-field" onchange="filterKota(this.value)" x-bind:required="step===1">
                            <option value="" selected disabled>Pilih Provinsi</option>
                            @foreach ($provinsis as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('prov_id_pus_create')" class="mt-2" />
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Kabupaten/Kota</label>
                        <select name="kota_id_pus_create" class="input-field" onchange="filterKec(this.value)" x-bind:required="step===1">
                            <option value="" selected disabled>Pilih Kabupaten/Kota</option>
                        </select>
                        <x-input-error :messages="$errors->get('kota_id_pus_create')" class="mt-2" />
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Kecamatan</label>
                        <select name="kec_id_pus_create" class="input-field" onchange="filterKel(this.value)" x-bind:required="step===1">
                            <option value="" selected disabled>Pilih Kecamatan</option>
                        </select>
                        <x-input-error :messages="$errors->get('kec_id_pus_create')" class="mt-2" />
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Kelurahan</label>
                        <select name="kelurahan_id_pus_create" class="input-field" x-bind:required="step===1">
                            <option value="" selected disabled>Pilih Kelurahan</option>
                        </select>
                        <x-input-error :messages="$errors->get('kelurahan_id_pus_create')" class="mt-2" />
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">No Telp</label>
                        <input name="no_telp_pus_create" type="tel" class="input-field" value="{{ old('no_telp') }}" x-bind:required="step===1">
                        <x-input-error :messages="$errors->get('no_telp_pus_create')" class="mt-2" />
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Puskesmas</label>
                        <select name="puskesmas_id_pus_create" class="input-field" x-bind:required="step===1">
                            <option value="" selected disabled>Pilih Puskesmas</option>
                            @foreach ($puskesmas as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('puskesmas_id_pus_create')" class="mt-2" />
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Jabatan</label>
                        <select name="jabatan_id_pus_create" class="input-field" x-bind:required="step===1">
                            <option value="" selected disabled>Pilih Jabatan</option>
                            @foreach ($daftarJabatan as $j)
                            <option value="{{ $j->id }}">{{ $j->nama }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('jabatan_id_pus_create')" class="mt-2" />
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
        @if (Auth::check() == false)
        <p class="text-center text-gray-600 text-sm mt-4">Ingin Membatalkan?
            <a href="{{ route('manajemen.pengguna', [], false) }}" data-swup-preload class="text-blue-600 font-medium hover:underline">Kembali</a>
        </p>
        @endif
    </div>
</div>