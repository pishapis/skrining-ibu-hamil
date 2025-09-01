{{-- Sidebar (desktop) --}}
<aside class="hidden md:flex md:flex-col w-64 bg-white p-6 border-r border-gray-200 min-h-screen shadow-sm">
    <div class="p-4 mb-8 text-center">
        <img src="https://placehold.co/100x100/A7EDEC/ffffff?text=Logo" alt="Logo" class="mx-auto mb-4 rounded-full">
        <h3 class="text-lg font-semibold text-gray-800">Skrining Mental Ibu</h3>
    </div>
    <nav data-swup-preload-all class="space-y-2">
        <!-- Menu untuk semua pengguna -->
        <div @class([ 'px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600' , request()->routeIs('dashboard') ? 'bg-blue-100 text-blue-800' : ''])>
            <a href="{{ route('dashboard') }}">
                <svg class="inline-block w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                </svg>
                Beranda
            </a>
        </div>

        <!-- Menu Superadmin -->
        @if(Auth::user()->role_id == 3)
        <div class="px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600" x-data="{ openDropdown: false }">
            <button @click="openDropdown = !openDropdown" class="w-full text-left flex items-center">
                <svg class="inline-block w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2V1a1 1 0 10-2 0v2H4zm5 0V1a1 1 0 10-2 0v2H7zm4 0V1a1 1 0 10-2 0v2H9zm2-2a2 2 0 012 2h2V1a1 1 0 10-2 0v2h-2zM5 5h10V18a2 2 0 01-2 2H7a2 2 0 01-2-2V5zm12 0h-1V3h1a2 2 0 012 2v13a2 2 0 01-2 2h-1V5z" clip-rule="evenodd" />
                </svg>
                Data Skrining
                <svg class="ml-auto w-5 h-5">
                    <path fill="currentColor" fill-rule="evenodd" d="M7.293 9.293a1 1 0 011.414 0L10 11.586l1.293-2.293a1 1 0 011.414 1.414L10 14.414l-3.707-3.707a1 1 0 011.414-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
            <div x-show="openDropdown" x-cloak class="pl-5 space-y-2">
                <a href="#" class="block px-3 py-2 text-sm hover:text-cyan-700">
                    Pertanyaan EPDS & DASS
                </a>
                <a href="#" class="block px-3 py-2 text-sm hover:text-cyan-700">
                    Pilihan Jawaban
                </a>
                <a href="#" class="block px-3 py-2 text-sm hover:text-cyan-700">
                    Skala & Dimensi
                </a>
            </div>
        </div>

        <div class="px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600">
            <a href="#" class="w-full flex items-center">
                <svg class="inline-block w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.707 3.293a1 1 0 00-1.414-1.414L9 8.586 7.707 7.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Analisis Data
            </a>
        </div>

        <!-- Manajemen Pengguna (Superadmin) -->
        <div class="px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600" x-data="{ openDropdown: false }">
            <button @click="openDropdown = !openDropdown" class="w-full text-left flex items-center">
                <i class="fa-solid fa-users mr-2"></i>
                Pengguna Akun
                <svg class="ml-auto w-5 h-5">
                    <path fill="currentColor" fill-rule="evenodd" d="M7.293 9.293a1 1 0 011.414 0L10 11.586l1.293-2.293a1 1 0 011.414 1.414L10 14.414l-3.707-3.707a1 1 0 011.414-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
            <div x-show="openDropdown" x-cloak class="pl-5 space-y-2">
                <a href="{{ route('manajemen.pengguna', [], false) }}" data-swup-preload class="block px-3 py-2 text-sm hover:text-cyan-700">
                    Kelola Pengguna
                </a>
                <a href="#" class="block px-3 py-2 text-sm hover:text-cyan-700">
                    Kelola Peran & Akses
                </a>
            </div>
        </div>
        @endif

        <!-- Menu Admin -->
        @if(Auth::user()->role_id == 2)
        <div class="px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600" x-data="{ openDropdown: false }">
            <button @click="openDropdown = !openDropdown" class="w-full text-left flex items-center">
                <svg class="inline-block w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM6 10a4 4 0 118 0 4 4 0 01-8 0z" />
                </svg>
                Manajemen Data
                <svg class="ml-auto w-5 h-5">
                    <path fill="currentColor" fill-rule="evenodd" d="M7.293 9.293a1 1 0 011.414 0L10 11.586l1.293-2.293a1 1 0 011.414 1.414L10 14.414l-3.707-3.707a1 1 0 011.414-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
            <div x-show="openDropdown" x-cloak class="pl-5 space-y-2">
                <a href="#" class="block px-3 py-2 text-sm hover:bg-teal-50 hover:text-teal-600">
                    Data Ibu Hamil
                </a>
            </div>
        </div>

        <div class="px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600">
            <a href="#" class="w-full flex items-center">
                <svg class="inline-block w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2V1a1 1 0 10-2 0v2H4zm5 0V1a1 1 0 10-2 0v2H7zm4 0V1a1 1 0 10-2 0v2H9zm2-2a2 2 0 012 2h2V1a1 1 0 10-2 0v2h-2zM5 5h10V18a2 2 0 01-2 2H7a2 2 0 01-2-2V5zm12 0h-1V3h1a2 2 0 012 2v13a2 2 0 01-2 2h-1V5z" clip-rule="evenodd" />
                </svg>
                Data Skrining
            </a>
        </div>
        @endif

        @if (Auth::user()->role_id == 1)
        <div class="px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600">
            <a href="{{ route('skrining.epds') }}">
                <svg class="inline-block mr-2 w-5 h-5 mb-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2V1a1 1 0 10-2 0v2H4zm5 0V1a1 1 0 10-2 0v2H7zm4 0V1a1 1 0 10-2 0v2H9zm2-2a2 2 0 012 2h2V1a1 1 0 10-2 0v2h-2zM5 5h10V18a2 2 0 01-2 2H7a2 2 0 01-2-2V5zm12 0h-1V3h1a2 2 0 012 2v13a2 2 0 01-2 2h-1V5z" clip-rule="evenodd" />
                </svg>
                Skrining
            </a>
        </div>
        <div class="px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600">
            <a href="#">
                <svg class="inline-block mr-2 w-5 h-5 mb-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM6 10a4 4 0 118 0 4 4 0 01-8 0z" />
                </svg>
                Edukasi
            </a>
        </div>
        @endif

        <!-- Menu Umum -->
        <div class="px-3 py-2 rounded-lg font-medium text-slate-600 transition hover:bg-teal-50 hover:text-teal-600">
            <a href="#">
                <svg class="inline-block w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
                </svg>
                Akun
            </a>
        </div>
    </nav>
</aside>