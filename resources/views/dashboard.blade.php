<x-app-layout>
    @section('page_title','Beranda')
    <x-slot name="title">Dashboard | Skrining Ibu Hamil</x-slot>

    @php
    $displayName = Auth::user()->name ?? 'Ibu';
    $trimesterLabel = [
    'trimester_1' => 'Trimester I',
    'trimester_2' => 'Trimester II',
    'trimester_3' => 'Trimester III',
    'pasca_hamil' => 'Pasca Melahirkan',
    ][$usia['trimester'] ?? ''] ?? \Illuminate\Support\Str::headline($usia['trimester'] ?? '-');
    @endphp

    <!-- Header -->
    <div class="bg-white p-6 md:p-8 rounded-xl shadow-sm mb-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Halo, {{ $displayName }} 👋</h2>
                <p class="text-gray-600 mt-1">Selamat datang di Aplikasi Simkeswa.</p>
            </div>
        </div>

        {{-- Global Filter Panel - Tampil untuk semua role --}}
        <div class="mt-6 bg-gradient-to-r from-teal-50 to-blue-50 border border-teal-200 rounded-xl p-5">
            <form method="GET" action="{{ route('dashboard') }}" id="dashboard-filter-form" class="space-y-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filter Dashboard
                    </h3>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-teal-600 text-white text-sm rounded-lg hover:bg-teal-700">
                            Terapkan Filter
                        </button>
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200">
                            Reset
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- Jenis Skrining --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Skrining</label>
                        <select name="screening_type" class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach($filterOptions['screeningTypes'] as $val => $label)
                                <option value="{{ $val }}" {{ $filters['screening_type'] == $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- BARU: Mode EPDS --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mode EPDS</label>
                        <select name="epds_mode" class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach($filterOptions['epdsModes'] as $val => $label)
                                <option value="{{ $val }}" {{ $filters['epds_mode'] == $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Mode DASS --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mode DASS</label>
                        <select name="dass_mode" class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach($filterOptions['dassModes'] as $val => $label)
                                <option value="{{ $val }}" {{ $filters['dass_mode'] == $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Nama Ibu (untuk admin/superadmin) --}}
                    @if($role === 'admin_clinician' || $role === 'superadmin')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                            <input type="text" 
                                name="ibu_name" 
                                value="{{ $filters['ibu_name'] ?? '' }}" 
                                placeholder="Cari nama..."
                                list="ibu-names-list"
                                class="w-full rounded-lg border-gray-300 text-sm">
                            <datalist id="ibu-names-list">
                                @foreach($filterOptions['ibuNames'] as $name)
                                    <option value="{{ $name }}">
                                @endforeach
                            </datalist>
                        </div>
                    @endif

                    {{-- Rentang Waktu --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rentang Waktu</label>
                        <select name="date_range" id="date_range_select" class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach($filterOptions['dateRanges'] as $val => $label)
                                <option value="{{ $val }}" {{ $filters['date_range'] == $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter lainnya... --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                        <select name="year" class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach($filterOptions['years'] as $val => $label)
                                <option value="{{ $val }}" {{ $filters['year'] == $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                        <select name="month" class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach($filterOptions['months'] as $val => $label)
                                <option value="{{ $val }}" {{ $filters['month'] == $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fase Kehamilan</label>
                        <select name="trimester" class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach($filterOptions['trimesters'] as $val => $label)
                                <option value="{{ $val }}" {{ $filters['trimester'] == $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Periode DASS Umum</label>
                        <select name="periode" class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach($filterOptions['periodes'] as $val => $label)
                                <option value="{{ $val }}" {{ $filters['periode'] == $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Custom Date Range (tampil jika pilih custom) --}}
                <div id="custom_date_range" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" 
                               class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" 
                               class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                </div>

                {{-- Active Filters Display --}}
                @if(collect($filters)->filter(fn($v, $k) => !in_array($v, ['all', Carbon\Carbon::now()->year, Carbon\Carbon::now()->month, '30']) && $v !== null)->count() > 0)
                    <div class="flex flex-wrap gap-2 pt-2 border-t border-teal-200">
                        <span class="text-sm text-gray-600">Filter aktif:</span>
                        
                        @if($filters['screening_type'] !== 'all')
                            <span class="px-2 py-1 bg-teal-100 text-teal-700 rounded-full text-xs">
                                {{ $filterOptions['screeningTypes'][$filters['screening_type']] }}
                            </span>
                        @endif
                        
                        @if($filters['epds_mode'] !== 'all')
                            <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs">
                                EPDS: {{ $filterOptions['epdsModes'][$filters['epds_mode']] }}
                            </span>
                        @endif
                        
                        @if($filters['dass_mode'] !== 'all')
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">
                                DASS: {{ $filterOptions['dassModes'][$filters['dass_mode']] }}
                            </span>
                        @endif
                        
                        @if(!empty($filters['ibu_name']))
                            <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs flex items-center gap-1">
                                Ibu: {{ Str::limit($filters['ibu_name'], 20) }}
                                <a href="{{ route('dashboard', array_merge(request()->except('ibu_name'))) }}" 
                                class="hover:text-amber-900">×</a>
                            </span>
                        @endif
                        
                        @if($filters['trimester'] !== 'all')
                            <span class="px-2 py-1 bg-pink-100 text-pink-700 rounded-full text-xs">
                                {{ $filterOptions['trimesters'][$filters['trimester']] }}
                            </span>
                        @endif
                        
                        @if($filters['month'] !== 'all')
                            <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs">
                                {{ $filterOptions['months'][$filters['month']] }}
                            </span>
                        @endif
                    </div>
                @endif
            </form>
        </div>

        {{-- Alerts --}}
        @if(!empty($alerts))
        <div class="space-y-3 mt-6">
            @foreach($alerts as $al)
            <div class="flex items-start gap-3 p-4 rounded-xl border
              {{ $al['type']==='warning' ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-sky-50 border-sky-200 text-sky-900' }}">
                <svg class="w-5 h-5 mt-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l6.518 11.593c.75 1.335-.213 2.995-1.742 2.995H3.481c-1.53 0-2.492-1.66-1.743-2.995L8.257 3.1zM11 14a1 1 0 10-2 0 1 1 0 002 0zm-1-2a1 1 0 01-1-1V7a1 1 0 112 0v4a1 1 0 01-1 1z" />
                </svg>
                <div class="text-sm">{{ $al['text'] }}</div>
            </div>
            @endforeach
        </div>
        @endif

        @if (Auth::user()->role_id == 1)
        <!-- KPI Cards untuk User Ibu -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mt-6">
            {{-- Usia Kehamilan --}}
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                <p class="text-emerald-700 text-sm font-semibold mb-1">Usia Kehamilan</p>
                <div class="text-gray-900 text-xl font-bold">{{ $usia['keterangan'] ?? '—' }}</div>
                <div class="text-sm text-gray-600 mt-1">{{ $trimesterLabel }}</div>
                @if(!empty($usia))
                <div class="text-xs text-emerald-800 mt-3">
                    HPHT: {{ \Carbon\Carbon::parse($usia['hpht'])->translatedFormat('d M Y') }} •
                    HPL: {{ \Carbon\Carbon::parse($usia['hpl'])->translatedFormat('d M Y') }}
                </div>
                @else
                <div class="text-xs text-emerald-800 mt-3">Lengkapi HPHT untuk kalkulasi usia kehamilan.</div>
                @endif
            </div>

            {{-- Jadwal Skrining Berikutnya --}}
            <div class="rounded-xl border border-sky-200 bg-sky-50 p-5">
                <p class="text-sky-700 text-sm font-semibold mb-1">Jadwal Skrining Berikutnya</p>
                @if($nextSchedule)
                <div class="text-gray-900 text-lg font-bold">{{ $nextSchedule['phase'] }}</div>
                <div class="text-sm text-gray-600 mt-1">
                    Tanggal: {{ $nextSchedule['date_human'] }} {{ $nextSchedule['is_now'] ? '(tersedia sekarang)' : '' }}
                </div>
                <div class="mt-3">
                    <a href="{{ route('skrining.epds') }}"
                        class="inline-block text-xs px-3 py-1 rounded-lg bg-sky-600 text-white hover:bg-sky-700">Mulai / Lanjutkan</a>
                </div>
                @else
                <div class="text-gray-900 text-lg font-bold">—</div>
                <div class="text-sm text-gray-600 mt-1">Semua fase telah diselesaikan.</div>
                @endif
            </div>

            {{-- EPDS terakhir --}}
            <div class="rounded-xl border border-fuchsia-200 bg-fuchsia-50 p-5">
                <p class="text-fuchsia-700 text-sm font-semibold mb-1">EPDS (Filter: {{ $epdsCount }})</p>
                <div class="text-gray-900 text-xl font-bold">{{ optional($latestEpds)->total_score ?? '—' }}</div>
                <div class="text-sm text-gray-600 mt-1">
                    {{ $latestEpds ? \Carbon\Carbon::parse($latestEpds->screening_date)->translatedFormat('d M Y') : 'Belum ada' }}
                </div>
                <div class="mt-3">
                    <a href="{{ route('skrining.epds') }}"
                        class="inline-block text-xs px-3 py-1 rounded-lg bg-fuchsia-600 text-white hover:bg-fuchsia-700">Skrining EPDS</a>
                </div>
            </div>

            {{-- DASS terakhir --}}
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-5">
                <p class="text-rose-700 text-sm font-semibold mb-1">DASS-21 (Filter: {{ $dassCount }})</p>
                @if($latestDass)
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs px-2 py-0.5 rounded-full 
                            {{ $latestDass->is_kehamilan ? 'bg-pink-50 text-pink-600' : 'bg-blue-50 text-blue-600' }} font-medium">
                            {{ $latestDass->jenis_label }}
                        </span>
                        
                        @if($latestDass->is_kehamilan && $latestDass->trimester)
                            <span class="text-xs text-gray-600">
                                {{ [
                                    'trimester_1' => 'T1',
                                    'trimester_2' => 'T2', 
                                    'trimester_3' => 'T3',
                                    'pasca_hamil' => 'Pasca'
                                ][$latestDass->trimester] ?? $latestDass->trimester }}
                            </span>
                        @elseif($latestDass->is_umum && $latestDass->periode)
                            <span class="text-xs text-gray-600">
                                {{ \Carbon\Carbon::parse($latestDass->periode)->translatedFormat('M Y') }}
                            </span>
                        @endif
                    </div>
                    
                    <div class="text-gray-900 font-bold">
                        Dep {{ $latestDass->total_depression ?? '—' }} •
                        Anx {{ $latestDass->total_anxiety ?? '—' }} •
                        Str {{ $latestDass->total_stress ?? '—' }}
                    </div>
                    <div class="text-sm text-gray-600 mt-1">
                        {{ \Carbon\Carbon::parse($latestDass->screening_date)->translatedFormat('d M Y') }}
                    </div>
                @else
                    <div class="text-gray-900 text-xl font-bold">—</div>
                    <div class="text-sm text-gray-600 mt-1">Belum ada</div>
                @endif
                
                <div class="mt-3">
                    <a href="{{ route('skrining.epds') }}" 
                    class="inline-block text-xs px-3 py-1 rounded-lg bg-rose-600 text-white hover:bg-rose-700">
                        Skrining DASS-21
                    </a>
                </div>
            </div>
        </div>

        {{-- Grafik Tren untuk User Ibu --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            {{-- Tren EPDS --}}
            <div class="rounded-xl border bg-white p-5">
                <h4 class="font-semibold text-gray-900 mb-3">Tren Skor EPDS</h4>
                <div id="chart-epds-trend" style="height:280px"></div>
            </div>

            {{-- Tren DASS --}}
            <div class="rounded-xl border bg-white p-5">
                <h4 class="font-semibold text-gray-900 mb-3">Tren Skor DASS-21</h4>
                <div id="chart-dass-trend" style="height:280px"></div>
            </div>
        </div>

        {{-- Aksi Cepat --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mt-6">
            <a href="{{ route('skrining.epds') }}" class="rounded-xl border p-4 text-center hover:shadow-sm">
                <div class="text-sm font-semibold">Mulai Skrining</div>
                <div class="text-xs text-gray-500 mt-1">EPDS / DASS-21</div>
            </a>
            <a href="{{ route('riwayat.skrining') }}" class="rounded-xl border p-4 text-center hover:shadow-sm">
                <div class="text-sm font-semibold">Riwayat</div>
                <div class="text-xs text-gray-500 mt-1">Hasil & Unduh</div>
            </a>
            <a href="#" class="rounded-xl border p-4 text-center hover:shadow-sm">
                <div class="text-sm font-semibold">Edukasi</div>
                <div class="text-xs text-gray-500 mt-1">Materi & Tips</div>
            </a>
            <a href="{{ route('profile.edit') }}" class="rounded-xl border p-4 text-center hover:shadow-sm">
                <div class="text-sm font-semibold">Profil</div>
                <div class="text-xs text-gray-500 mt-1">Data Diri</div>
            </a>
        </div>
        @endif

        {{-- Konten untuk Admin/Superadmin --}}
        @if($role === 'admin_clinician' || $role === 'superadmin')
        <section class="mt-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        Ikhtisar {{ $role === 'superadmin' ? 'Semua Fasilitas' : 'Fasilitas' }}
                    </h3>
                    <p class="text-xs text-gray-500">Data berdasarkan filter yang aktif</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('riwayat.skrining') }}"
                        class="px-3 py-2 rounded-lg border text-sm hover:bg-gray-50">
                        Laporan Skrining
                    </a>
                </div>
            </div>

            {{-- KPI Cards dengan Filter --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="rounded-xl border p-4 bg-white">
                    <div class="text-xs text-gray-500">Total Ibu Terdaftar</div>
                    <div class="text-2xl font-bold">{{ number_format($facilityStats['total_ibu'] ?? 0) }}</div>
                    <div class="text-xs text-gray-500 mt-1">Data aktif</div>
                </div>
                <div class="rounded-xl border p-4 bg-white">
                    <div class="text-xs text-gray-500">EPDS (Filter)</div>
                    <div class="text-2xl font-bold">{{ $kpi['epds_count'] ?? 0 }}</div>
                    <div class="text-xs text-gray-500 mt-1">Sesi unik</div>
                </div>
                <div class="rounded-xl border p-4 bg-white">
                    <div class="text-xs text-gray-500">DASS Kehamilan</div>
                    <div class="text-2xl font-bold">{{ $kpi['dass_kehamilan_count'] ?? 0 }}</div>
                    <div class="text-xs text-gray-500 mt-1">Filter aktif</div>
                </div>
                <div class="rounded-xl border p-4 bg-white">
                    <div class="text-xs text-gray-500">DASS Umum</div>
                    <div class="text-2xl font-bold">{{ $kpi['dass_umum_count'] ?? 0 }}</div>
                    <div class="text-xs text-gray-500 mt-1">Filter aktif</div>
                </div>
                <div class="rounded-xl border p-4 bg-white">
                    <div class="text-xs text-gray-500">Total DASS</div>
                    <div class="text-2xl font-bold">{{ $kpi['dass_total_count'] ?? 0 }}</div>
                    <div class="text-xs text-gray-500 mt-1">Kehamilan + Umum</div>
                </div>
            </div>

            {{-- Charts + Activity --}}
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mt-6">
                {{-- Grafik Tren EPDS --}}
                <div class="lg:col-span-2 rounded-xl border bg-white p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-900">Tren Skor EPDS</h4>
                        <div class="text-xs text-gray-500">Berdasarkan filter</div>
                    </div>
                    <div id="chart-admin-epds-trend" style="height:320px"></div>
                </div>

                {{-- Grafik Tren DASS --}}
                <div class="lg:col-span-2 rounded-xl border bg-white p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-900">Tren Skor DASS-21</h4>
                        <div class="text-xs text-gray-500">Depression, Anxiety, Stress</div>
                    </div>
                    <div id="chart-admin-dass-trend" style="height:320px"></div>
                </div>

                {{-- Distribusi Trimester --}}
                <div class="lg:col-span-2 rounded-xl border bg-white p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-900">Distribusi Trimester</h4>
                        <div class="text-xs text-gray-500">Terbaru per ibu</div>
                    </div>
                    <div id="chart-trimester" style="height:280px"></div>
                </div>

                {{-- Aktivitas Terbaru --}}
                <div class="lg:col-span-2 rounded-xl border bg-white p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-900">Aktivitas Terbaru</h4>
                        <a href="{{ route('riwayat.skrining') }}" class="text-sm text-teal-700 hover:underline">Lihat semua</a>
                    </div>
                    @if(!empty($latestScreenings))
                        <ul class="divide-y max-h-96 overflow-y-auto">
                            @foreach($latestScreenings as $it)
                            @php
                            $isEPDS = $it['type'] === 'EPDS';
                            $isDASS = $it['type'] === 'DASS-21';
                            $dassType = $isDASS ? ($it['jenis'] ?? 'umum') : null;
                            $epdsMode = $isEPDS ? ($it['mode'] ?? 'kehamilan') : null;
                            $isKehamilanMode = ($isEPDS && $epdsMode === 'kehamilan') || ($isDASS && $dassType === 'kehamilan');
                            
                            $badge = $isEPDS
                                ? ((int)($it['scores']['total'] ?? 0) >= 13 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700')
                                : (
                                    ((int)($it['scores']['dep'] ?? 0) >= 15 || (int)($it['scores']['anx'] ?? 0) >= 12 || (int)($it['scores']['stress'] ?? 0) >= 20)
                                    ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'
                                );
                            @endphp
                            <li class="py-3 flex items-start gap-3">
                                <div class="h-9 w-9 grid place-content-center rounded-full bg-gray-100 text-xs font-semibold shrink-0">
                                    {{ $isEPDS ? 'EP' : 'DA' }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="font-medium text-gray-900 truncate">{{ $it['ibu'] ?? 'Ibu' }}</div>
                                        <div class="text-xs text-gray-500 shrink-0">{{ $it['label'] }}</div>
                                    </div>
                                    <div class="mt-0.5 text-sm text-gray-700">
                                        @if($isEPDS)
                                            <span class="text-xs px-2 py-0.5 rounded-full {{ $epdsMode === 'kehamilan' ? 'bg-pink-50 text-pink-600' : 'bg-blue-50 text-blue-600' }} mr-2">
                                                {{ $epdsMode === 'kehamilan' ? 'Kehamilan' : 'Umum' }}
                                            </span>
                                            
                                            {{-- BARU: Tampilkan usia kehamilan jika mode kehamilan --}}
                                            @if($isKehamilanMode && !empty($it['usia_kehamilan']))
                                                <span class="text-xs text-gray-600 mr-2">
                                                    ({{ $it['usia_kehamilan']['keterangan'] ?? '' }})
                                                </span>
                                            @endif
                                            
                                            Skor EPDS: <span class="font-semibold">{{ (int)($it['scores']['total'] ?? 0) }}</span>
                                        @elseif($isDASS)
                                            <span class="text-xs px-2 py-0.5 rounded-full {{ $dassType === 'kehamilan' ? 'bg-pink-50 text-pink-600' : 'bg-blue-50 text-blue-600' }} mr-2">
                                                {{ $dassType === 'kehamilan' ? 'Kehamilan' : 'Umum' }}
                                            </span>
                                            
                                            {{-- BARU: Tampilkan usia kehamilan jika mode kehamilan --}}
                                            @if($isKehamilanMode && !empty($it['usia_kehamilan']))
                                                <span class="text-xs text-gray-600 mr-2">
                                                    ({{ $it['usia_kehamilan']['keterangan'] ?? '' }})
                                                </span>
                                            @endif
                                            
                                            @if(isset($it['trimester']) && $dassType === 'kehamilan')
                                                <span class="text-xs text-gray-500 mr-2">
                                                    {{ [
                                                        'trimester_1' => 'T1',
                                                        'trimester_2' => 'T2', 
                                                        'trimester_3' => 'T3',
                                                        'pasca_hamil' => 'Pasca'
                                                    ][$it['trimester']] ?? '' }}
                                                </span>
                                            @endif
                                            Dep <span class="font-semibold">{{ (int)($it['scores']['dep'] ?? 0) }}</span> •
                                            Anx <span class="font-semibold">{{ (int)($it['scores']['anx'] ?? 0) }}</span> •
                                            Str <span class="font-semibold">{{ (int)($it['scores']['stress'] ?? 0) }}</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[11px] {{ $badge }} shrink-0">
                                    {{ $isEPDS
                                        ? ((int)($it['scores']['total'] ?? 0) >= 13 ? 'Perlu perhatian' : 'Wajar')
                                        : (((int)($it['scores']['dep'] ?? 0) >= 15 || (int)($it['scores']['anx'] ?? 0) >= 12 || (int)($it['scores']['stress'] ?? 0) >= 20) ? 'Perlu perhatian' : 'Wajar') }}
                                </span>
                            </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-sm text-gray-500 text-center py-8">Belum ada aktivitas.</div>
                    @endif
                </div>
            </div>
        </section>
        @endif

        {{-- Rekomendasi Edukasi (User Ibu) --}}
        @if($role === 'user' && !empty($eduRecs))
        <section class="mt-10">
            <div class="flex items-end justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Edukasi untukmu</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Dipilih dari riwayat skrining & trimester.</p>
                </div>
                <a href="{{ route('edukasi.index') }}" class="text-sm text-teal-700 hover:underline">Lihat semua</a>
            </div>

            <div class="mt-4 relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 w-10 hidden sm:block"></div>
                <div class="pointer-events-none absolute inset-y-0 right-0 w-10 hidden sm:block"></div>

                <div id="edu-scroller"
                    class="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-3 scroll-smooth"
                    style="scroll-padding-inline:1rem;">
                    @foreach($eduRecs as $c)
                    <a href="{{ $c['url'] }}"
                        class="group snap-start shrink-0 w-80 md:w-96
                            bg-gray-100 border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition">
                        <div class="relative aspect-[16/9] w-full overflow-hidden rounded-t-2xl bg-gray-100">
                            @if($c['cover'])
                            <img src="{{ $c['cover'] }}" alt="{{ $c['title'] }}"
                                loading="lazy" decoding="async"
                                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-[1.02]">
                            @else
                            <div class="w-full h-full grid place-content-center text-gray-500 text-sm">Konten Edukasi</div>
                            @endif

                            @if($c['badge'])
                            <span class="absolute top-2 left-2 rounded-full backdrop-blur px-2 py-0.5 text-[11px]
                                    bg-black/60 text-white">{{ $c['badge'] }}</span>
                            @endif
                        </div>

                        <div class="p-3">
                            @if(!empty($c['tags']))
                            <div class="flex flex-wrap gap-1.5 mb-2">
                                @foreach($c['tags'] as $t)
                                <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 text-[11px]">{{ $t }}</span>
                                @endforeach
                            </div>
                            @endif
                            <div class="font-semibold text-gray-900 line-clamp-2">{{ $c['title'] }}</div>
                            @if(!empty($c['summary']))
                            <p class="text-sm text-gray-600 line-clamp-2 mt-1">{{ $c['summary'] }}</p>
                            @endif
                            <div class="mt-3">
                                <span class="inline-flex items-center gap-1 text-teal-700 text-sm">
                                    Baca selengkapnya
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>

                <div class="hidden sm:flex gap-2 absolute -top-10 right-0">
                    <button type="button" class="edu-nav px-3 py-1.5 rounded-lg border text-sm disabled:opacity-40" data-dir="-1">‹</button>
                    <button type="button" class="edu-nav px-3 py-1.5 rounded-lg border text-sm disabled:opacity-40" data-dir="1">›</button>
                </div>

                <div id="edu-dots" class="mt-3 flex items-center gap-1.5 justify-center sm:justify-start"></div>
            </div>
        </section>
        @endif

        @if (Auth::user()->role_id == 1)
        <div class="md:hidden sticky bottom-4 inset-x-0 px-4 mt-6">
            <a href="{{ route('skrining.epds') }}"
                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-teal-600 text-white shadow-lg">
                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" />
                </svg>
                Mulai Skrining 
            </a>
        </div>
        @endif

        <x-slot name="scripts">
            {{-- Custom Date Range Toggle --}}
            <script data-swup-reload-script>
                document.getElementById('date_range_select')?.addEventListener('change', function() {
                    const customDiv = document.getElementById('custom_date_range');
                    if (this.value === 'custom') {
                        customDiv?.classList.remove('hidden');
                    } else {
                        customDiv?.classList.add('hidden');
                    }
                });
                
                // Init on page load
                if (document.getElementById('date_range_select')?.value === 'custom') {
                    document.getElementById('custom_date_range')?.classList.remove('hidden');
                }
            </script>

            {{-- Dashboard Seed Data --}}
            <script data-swup-reload-script type="application/json" id="dashboard-seed">
                {!! json_encode([
                    'role' => $role,
                    'epdsTrend' => $epdsTrend ?? [],
                    'dassTrend' => $dassTrend ?? [],
                    'facilityStats' => $facilityStats ?? null,
                    'latestScreenings' => $latestScreenings ?? [],
                ], JSON_UNESCAPED_UNICODE) !!}
            </script>

            {{-- ApexCharts Rendering --}}
            <script data-swup-reload-script>
                (function() {
                    const seedEl = document.getElementById('dashboard-seed');
                    if (!seedEl) return;

                    const seed = JSON.parse(seedEl.textContent || '{}');
                    const role = seed.role || 'user';

                    function waitForApex(callback, tries = 50) {
                        if (window.ApexCharts) {
                            callback();
                        } else if (tries > 0) {
                            setTimeout(() => waitForApex(callback, tries - 1), 100);
                        }
                    }

                    waitForApex(() => {
                        // User Ibu Charts
                        if (role === 'user') {
                            const epdsTrend = seed.epdsTrend || [];
                            const dassTrend = seed.dassTrend || [];

                            // EPDS Chart
                            if (document.getElementById('chart-epds-trend') && epdsTrend.length > 0) {
                                new ApexCharts(document.getElementById('chart-epds-trend'), {
                                    chart: { type: 'line', height: 280, toolbar: { show: false } },
                                    series: [{ name: 'Skor EPDS', data: epdsTrend.map(d => d.total) }],
                                    xaxis: { categories: epdsTrend.map(d => d.label) },
                                    stroke: { curve: 'smooth', width: 3 },
                                    markers: { size: 5 },
                                    colors: ['#d946ef'],
                                    yaxis: { min: 0, max: 30 },
                                }).render();
                            } else if (document.getElementById('chart-epds-trend')) {
                                document.getElementById('chart-epds-trend').innerHTML = 
                                    '<div class="flex items-center justify-center h-full text-gray-500 text-sm">Belum ada data EPDS</div>';
                            }

                            // DASS Chart - FIXED
                            if (document.getElementById('chart-dass-trend') && dassTrend.length > 0) {
                                new ApexCharts(document.getElementById('chart-dass-trend'), {
                                    chart: { 
                                        type: 'line', 
                                        height: 280, 
                                        toolbar: { show: false },
                                        animations: { enabled: true }
                                    },
                                    series: [
                                        { name: 'Depression', data: dassTrend.map(d => d.dep || 0) },
                                        { name: 'Anxiety', data: dassTrend.map(d => d.anx || 0) },
                                        { name: 'Stress', data: dassTrend.map(d => d.stress || 0) }
                                    ],
                                    xaxis: { categories: dassTrend.map(d => d.label) },
                                    stroke: { curve: 'smooth', width: 2 },
                                    markers: { size: 4 },
                                    colors: ['#ef4444', '#f59e0b', '#8b5cf6'],
                                    yaxis: { min: 0 },
                                    legend: { position: 'top' }
                                }).render();
                            } else if (document.getElementById('chart-dass-trend')) {
                                document.getElementById('chart-dass-trend').innerHTML = 
                                    '<div class="flex items-center justify-center h-full text-gray-500 text-sm">Belum ada data DASS-21</div>';
                            }
                        }

                        // Admin/Superadmin Charts
                        if (role === 'admin_clinician' || role === 'superadmin') {
                            const stats = seed.facilityStats || {};
                            const tri = stats.trimester_counts || {};

                            // Trimester Distribution
                            if (document.getElementById('chart-trimester')) {
                                new ApexCharts(document.getElementById('chart-trimester'), {
                                    chart: { type: 'donut', height: 280 },
                                    labels: ['Trimester I', 'Trimester II', 'Trimester III', 'Pasca Melahirkan'],
                                    series: [tri.trimester_1 || 0, tri.trimester_2 || 0, tri.trimester_3 || 0, tri.pasca_hamil || 0],
                                    legend: { position: 'bottom' },
                                    colors: ['#10b981', '#3b82f6', '#8b5cf6', '#ec4899']
                                }).render();
                            }

                            // Aggregate trend data from latest screenings
                            const items = seed.latestScreenings || [];
                            const epdsByDate = {};
                            const dassByDate = {};

                            items.forEach(it => {
                                if (it.type === 'EPDS') {
                                    if (!epdsByDate[it.date]) epdsByDate[it.date] = [];
                                    epdsByDate[it.date].push(it.scores.total || 0);
                                } else if (it.type === 'DASS-21') {
                                    if (!dassByDate[it.date]) dassByDate[it.date] = { dep: [], anx: [], stress: [] };
                                    dassByDate[it.date].dep.push(it.scores.dep || 0);
                                    dassByDate[it.date].anx.push(it.scores.anx || 0);
                                    dassByDate[it.date].stress.push(it.scores.stress || 0);
                                }
                            });

                            const epdsAvg = Object.keys(epdsByDate).sort().map(date => ({
                                date,
                                avg: epdsByDate[date].reduce((a,b) => a+b, 0) / epdsByDate[date].length
                            })).slice(-10); // Last 10 data points

                            const dassAvg = Object.keys(dassByDate).sort().map(date => ({
                                date,
                                dep: dassByDate[date].dep.reduce((a,b) => a+b, 0) / dassByDate[date].dep.length,
                                anx: dassByDate[date].anx.reduce((a,b) => a+b, 0) / dassByDate[date].anx.length,
                                stress: dassByDate[date].stress.reduce((a,b) => a+b, 0) / dassByDate[date].stress.length
                            })).slice(-10); // Last 10 data points

                            // Admin EPDS Trend
                            if (document.getElementById('chart-admin-epds-trend') && epdsAvg.length) {
                                new ApexCharts(document.getElementById('chart-admin-epds-trend'), {
                                    chart: { type: 'area', height: 320, toolbar: { show: false } },
                                    series: [{ name: 'Rata-rata EPDS', data: epdsAvg.map(d => parseFloat(d.avg.toFixed(1))) }],
                                    xaxis: { 
                                        categories: epdsAvg.map(d => {
                                            const dt = new Date(d.date);
                                            return dt.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                                        })
                                    },
                                    stroke: { curve: 'smooth', width: 2 },
                                    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.3 } },
                                    colors: ['#d946ef'],
                                    yaxis: { min: 0 },
                                }).render();
                            } else if (document.getElementById('chart-admin-epds-trend')) {
                                document.getElementById('chart-admin-epds-trend').innerHTML = 
                                    '<div class="flex items-center justify-center h-full text-gray-500 text-sm">Belum ada data untuk ditampilkan</div>';
                            }

                            // Admin DASS Trend - FIXED
                            if (document.getElementById('chart-admin-dass-trend') && dassAvg.length) {
                                new ApexCharts(document.getElementById('chart-admin-dass-trend'), {
                                    chart: { 
                                        type: 'line', 
                                        height: 320, 
                                        toolbar: { show: false },
                                        animations: { enabled: true }
                                    },
                                    series: [
                                        { name: 'Depression', data: dassAvg.map(d => parseFloat(d.dep.toFixed(1))) },
                                        { name: 'Anxiety', data: dassAvg.map(d => parseFloat(d.anx.toFixed(1))) },
                                        { name: 'Stress', data: dassAvg.map(d => parseFloat(d.stress.toFixed(1))) }
                                    ],
                                    xaxis: { 
                                        categories: dassAvg.map(d => {
                                            const dt = new Date(d.date);
                                            return dt.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                                        })
                                    },
                                    stroke: { curve: 'smooth', width: 2 },
                                    markers: { size: 4 },
                                    colors: ['#ef4444', '#f59e0b', '#8b5cf6'],
                                    yaxis: { min: 0 },
                                    legend: { position: 'top' }
                                }).render();
                            } else if (document.getElementById('chart-admin-dass-trend')) {
                                document.getElementById('chart-admin-dass-trend').innerHTML = 
                                    '<div class="flex items-center justify-center h-full text-gray-500 text-sm">Belum ada data untuk ditampilkan</div>';
                            }
                        }
                    });

                    // Education carousel
                    const scroller = document.getElementById('edu-scroller');
                    if (scroller) {
                        const cards = Array.from(scroller.querySelectorAll('a'));
                        const dotsWrap = document.getElementById('edu-dots');
                        const btns = document.querySelectorAll('.edu-nav');

                        if (cards.length > 0) {
                            dotsWrap.innerHTML = '';
                            cards.forEach((_, i) => {
                                const b = document.createElement('button');
                                b.type = 'button';
                                b.className = 'h-2.5 w-2.5 rounded-full bg-gray-300 aria-selected:bg-teal-600 transition-colors';
                                b.addEventListener('click', () => {
                                    const step = cards[0].getBoundingClientRect().width + 16;
                                    scroller.scrollTo({ left: i * step, behavior: 'smooth' });
                                });
                                dotsWrap.appendChild(b);
                            });

                            function updateUI() {
                                const step = cards[0].getBoundingClientRect().width + 16;
                                const idx = Math.round(scroller.scrollLeft / step);
                                dotsWrap.querySelectorAll('button').forEach((d, i) => {
                                    d.setAttribute('aria-selected', i === idx ? 'true' : 'false');
                                });
                            }

                            btns.forEach(b => {
                                b.addEventListener('click', () => {
                                    const dir = Number(b.dataset.dir || 1);
                                    const step = cards[0].getBoundingClientRect().width + 16;
                                    scroller.scrollBy({ left: dir * step, behavior: 'smooth' });
                                });
                            });

                            scroller.addEventListener('scroll', updateUI);
                            updateUI();
                        }
                    }
                })();
            </script>
        </x-slot>

</x-app-layout>