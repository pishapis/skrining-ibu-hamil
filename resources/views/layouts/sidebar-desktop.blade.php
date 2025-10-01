{{-- Sidebar (desktop) --}}
<aside id="sidebar" class="hidden md:flex md:flex-col w-64 bg-white p-6 border-r border-gray-200 min-h-screen shadow-sm">
    <a href="{{ url('/') }}" class="p-4 mb-0 text-center cursor-pointer">
        <img src="{{ asset('/assets/logos/simkeswa.png') }}" alt="Logo" class="mx-auto mb-4 rounded-full">
    </a>
    <nav data-swup-preload-all class="space-y-2">
        <!-- Menu untuk semua pengguna -->
        <div @class([ 'px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600' , request()->routeIs('dashboard') ? 'bg-blue-100 text-blue-800' : ''])>
            <a href="{{ url('/') }}">
                <svg class="inline-block w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                </svg>
                Beranda
            </a>
        </div>

        <!-- Menu Superadmin -->
        @if(Auth::user()->role_id == 3)
        <div
            class="px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600"
            x-data="{ openMaster:false, openSkrining:false, openFaskes:false }"
            @keydown.escape.window="openMaster=false; openSkrining=false; openFaskes=false"
            @click.outside="openMaster=false; openSkrining=false; openFaskes=false">
            <!-- level 1 -->
            <button
                @click="openMaster = !openMaster; if(!openMaster){ openSkrining=false; openFaskes=false }"
                :aria-expanded="openMaster"
                class="w-full text-left flex items-center">
                <svg class="inline-block w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2V1a1 1 0 10-2 0v2H4zm5 0V1a1 1 0 10-2 0v2H7zm4 0V1a1 1 0 10-2 0v2H9zm2-2a2 2 0 012 2h2V1a1 1 0 10-2 0v2h-2zM5 5h10V18a2 2 0 01-2 2H7a2 2 0 01-2-2V5zm12 0h-1V3h1a2 2 0 012 2v13a2 2 0 01-2 2h-1V5z" clip-rule="evenodd" />
                </svg>
                Master
                <svg class="ml-auto w-5 h-5 transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor" :class="openMaster ? 'rotate-180' : ''" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 011.08 1.04l-4.25 4.25a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </button>

            <div x-show="openMaster" x-cloak x-transition.origin.top class="pl-5 space-y-2 mt-2">
                <!-- submenu: Skrining -->
                <button
                    @click.stop="openFaskes=false; openSkrining=!openSkrining"
                    :aria-expanded="openSkrining"
                    class="w-full text-left flex items-center ml-1 transition hover:text-sky-500 hidden">
                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M7.8 10a2.2 2.2 0 0 0 4.4 0 2.2 2.2 0 0 0-4.4 0z" />
                    </svg>
                    Skrining
                </button>
                <div x-show="openSkrining" x-cloak x-transition.origin.top.left class="pl-5">
                    <a href="#" @click="openMaster=false; openSkrining=false; openFaskes=false" class="block px-3 py-1 text-sm transition hover:text-purple-600">Pertanyaan EPDS & DASS</a>
                    <a href="#" @click="openMaster=false; openSkrining=false; openFaskes=false" class="block px-3 py-1 text-sm transition hover:text-purple-600">Pilihan Jawaban</a>
                    <a href="#" @click="openMaster=false; openSkrining=false; openFaskes=false" class="block px-3 py-1 text-sm transition hover:text-purple-600">Skala & Dimensi</a>
                </div>

                <!-- submenu: Faskes -->
                <a href="{{ route('manajemen.faskes', [], false) }}" class="w-full flex items-center ml-1 hover:text-sky-500">
                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M7.8 10a2.2 2.2 0 0 0 4.4 0 2.2 2.2 0 0 0-4.4 0z" />
                    </svg>
                    Faskes
                </a>

                <!-- link lain -->
                <a href="{{ route('manajemen.pengguna', [], false) }}" class="w-full flex items-center ml-1 hover:text-sky-500">
                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M7.8 10a2.2 2.2 0 0 0 4.4 0 2.2 2.2 0 0 0-4.4 0z" />
                    </svg>
                    Pengguna Akun
                </a>

                <a href="{{ route('manajemen.jabatan', [], false) }}" class="w-full flex items-center ml-1 hover:text-sky-500">
                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M7.8 10a2.2 2.2 0 0 0 4.4 0 2.2 2.2 0 0 0-4.4 0z" />
                    </svg>
                    Jabatan
                </a>
            </div>
        </div>


        <div @class([ 'px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600' , request()->routeIs('riwayat.skrining') ? 'bg-blue-100 text-blue-800' : ''])>
            <a href="{{ url('/riwayat-skrining') }}" class="w-full flex items-center">
                <svg class="inline-block mr-2 w-5 h-5 mb-0.5" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Riwayat">
                    <path d="M3 12a9 9 0 1 0 3-6.7" />
                    <path d="M3 4v4h4" />
                    <circle cx="12" cy="12" r="5" />
                    <path d="M12 9v3l2 1.2" />
                </svg>
                Riwayat Skrining
            </a>
        </div>

        <div @class([ 'px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600' , request()->routeIs('edukasi.create') ? 'bg-blue-100 text-blue-800' : ''])>
            <a href="{{ route('edukasi.create') }}" class="w-full flex items-center">
                <i class="fa-solid fa-share mr-3"></i>
                Post Edukasi
            </a>
        </div>
        @endif

        <!-- Menu Admin -->
        @if(Auth::user()->role_id == 2)

        <div
            class="px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600"
            x-data="{ openSkrining:false, openSkrining:false, openFaskes:false }"
            @keydown.escape.window="openSkrining=false; openSkrining=false; openFaskes=false"
            @click.outside="openSkrining=false; openSkrining=false; openFaskes=false">
            <!-- level 1 -->
            <button
                @click="openSkrining = !openSkrining; if(!openSkrining){ openSkrining=false; openFaskes=false }"
                :aria-expanded="openSkrining"
                class="w-full text-left flex items-center">
                <svg class="inline-block mr-3 w-5 h-5 mb-0.5" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Riwayat">
                    <path d="M3 12a9 9 0 1 0 3-6.7" />
                    <path d="M3 4v4h4" />
                    <circle cx="12" cy="12" r="5" />
                    <path d="M12 9v3l2 1.2" />
                </svg>
                Skrining
                <svg class="ml-auto w-5 h-5 transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor" :class="openSkrining ? 'rotate-180' : ''" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 011.08 1.04l-4.25 4.25a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </button>

            <div x-show="openSkrining" x-cloak x-transition.origin.top class="pl-5 space-y-2 mt-2">
                <!-- link lain -->
                <a href="{{ url('/riwayat-skrining') }}" class="w-full flex items-center ml-1 hover:text-sky-500">
                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M7.8 10a2.2 2.2 0 0 0 4.4 0 2.2 2.2 0 0 0-4.4 0z" />
                    </svg>
                    Riwayat
                </a>

                <a href="{{ route('rescreen.index') }}" class="w-full flex items-center ml-1 hover:text-sky-500">
                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M7.8 10a2.2 2.2 0 0 0 4.4 0 2.2 2.2 0 0 0-4.4 0z" />
                    </svg>
                    Skrining Ulang
                </a>

                <a href="{{ route('generator') }}" class="w-full flex items-center ml-1 hover:text-sky-500">
                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M7.8 10a2.2 2.2 0 0 0 4.4 0 2.2 2.2 0 0 0-4.4 0z" />
                    </svg>
                    Generate Link
                </a>
            </div>
        </div>

        <div @class([ 'px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600' , request()->routeIs('edukasi.index') ? 'bg-blue-100 text-blue-800' : ''])>
            <a href="{{ url('/edukasi') }}">
                <svg class="inline-block mr-2 w-5 h-5 mb-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM6 10a4 4 0 118 0 4 4 0 01-8 0z" />
                </svg>
                Edukasi
            </a>
        </div>

        <div @class([ 'px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600' , request()->routeIs('manajemen.pengguna') ? 'bg-blue-100 text-blue-800' : ''])>
            <a href="{{ route('manajemen.pengguna', [], false) }}">
                <i class="fa-solid fa-users mr-2"></i>
                Akun Pengguna
            </a>
        </div>
        @endif

        @if (Auth::user()->role_id == 1)
        <div @class([ 'px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600' , request()->routeIs('skrining.epds') ? 'bg-blue-100 text-blue-800' : ''])>
            <a href="{{ url('/skrining') }}">
                <svg class="inline-block mr-2 w-5 h-5 mb-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2V1a1 1 0 10-2 0v2H4zm5 0V1a1 1 0 10-2 0v2H7zm4 0V1a1 1 0 10-2 0v2H9zm2-2a2 2 0 012 2h2V1a1 1 0 10-2 0v2h-2zM5 5h10V18a2 2 0 01-2 2H7a2 2 0 01-2-2V5zm12 0h-1V3h1a2 2 0 012 2v13a2 2 0 01-2 2h-1V5z" clip-rule="evenodd" />
                </svg>
                Skrining
            </a>
        </div>
        <div @class([ 'px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600' , request()->routeIs('edukasi.index') ? 'bg-blue-100 text-blue-800' : ''])>
            <a href="{{ url('/edukasi') }}">
                <svg class="inline-block mr-2 w-5 h-5 mb-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM6 10a4 4 0 118 0 4 4 0 01-8 0z" />
                </svg>
                Edukasi
            </a>
        </div>
        <div @class([ 'px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600' , request()->routeIs('riwayat.skrining') ? 'bg-blue-100 text-blue-800' : ''])>
            <a href="{{ url('/riwayat-skrining') }}">
                <svg class="inline-block mr-2 w-5 h-5 mb-0.5" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Riwayat">
                    <path d="M3 12a9 9 0 1 0 3-6.7" />
                    <path d="M3 4v4h4" />
                    <circle cx="12" cy="12" r="5" />
                    <path d="M12 9v3l2 1.2" />
                </svg>
                Riwayat
            </a>
        </div>
        @endif

        <!-- Menu Umum -->
        <div @class([ 'px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600' , request()->routeIs('profile.edit') ? 'bg-blue-100 text-blue-800' : ''])>
            <a href="{{ route('profile.edit') }}">
                <svg class="inline-block w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
                </svg>
                Akun
            </a>
        </div>
    </nav>
</aside>