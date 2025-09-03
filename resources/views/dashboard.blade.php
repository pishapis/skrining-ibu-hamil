<x-app-layout>
    @section('page_title','Beranda')
    <x-slot name="title">Dashboard | Skrining Ibu Hamil</x-slot>

    @php
    $displayName = Auth::user()->name ?? 'Ibu';
    $trimesterLabel = [
    'trimester_1' => 'Trimester I',
    'trimester_2' => 'Trimester II',
    'trimester_3' => 'Trimester III',
    'pasca_hamil' => 'Pasca Hamil',
    ][$usia['trimester'] ?? ''] ?? \Illuminate\Support\Str::headline($usia['trimester'] ?? '-');
    @endphp

    <!-- Header -->
    <div class="bg-white p-6 md:p-8 rounded-xl shadow-sm mb-6">
        <div class="flex items-start justify-between gap-4">
            <div class="md:hidden">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Halo, {{ $displayName }} 👋</h2>
                <p class="text-gray-600 mt-1">Selamat datang di Aplikasi Skrining Kesehatan Mental Ibu Hamil.</p>
                </divc>
                @if (Auth::user()->role_id == 1)
                <div class="hidden md:block">
                    <a href="{{ route('skrining.epds') }}"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-teal-600 text-white hover:bg-teal-700">
                        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" />
                        </svg>
                        Mulai Skrining
                    </a>
                </div>
                @endif
            </div>
        </div>

        {{-- Alerts --}}
        @if(!empty($alerts))
        <div class="space-y-3 mb-6">
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
        <!-- KPI & Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
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
                <p class="text-fuchsia-700 text-sm font-semibold mb-1">EPDS Terakhir</p>
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
                <p class="text-rose-700 text-sm font-semibold mb-1">DASS-21 Terakhir</p>
                <div class="text-gray-900 font-bold">
                    Dep {{ optional($latestDass)->total_depression ?? '—' }} •
                    Anx {{ optional($latestDass)->total_anxiety ?? '—' }} •
                    Str {{ optional($latestDass)->total_stress ?? '—' }}
                </div>
                <div class="text-sm text-gray-600 mt-1">
                    {{ $latestDass ? \Carbon\Carbon::parse($latestDass->screening_date)->translatedFormat('d M Y') : 'Belum ada' }}
                </div>
                <div class="mt-3">
                    <a href="{{ route('skrining.epds') }}"
                        class="inline-block text-xs px-3 py-1 rounded-lg bg-rose-600 text-white hover:bg-rose-700">Skrining DASS-21</a>
                </div>
            </div>
        </div>

        {{-- Aksi Cepat --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-8">
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

        {{-- ===== Konten per role ===== --}}
        @if($role === 'user')
        {{-- ===== Rekomendasi Edukasi untukmu ===== --}}
        @if(!empty($eduRecs))
        <section class="mb-10">
            <div class="flex items-end justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Edukasi untukmu</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Dipilih dari riwayat skrining & trimester.</p>
                </div>
                <a href="{{ route('edukasi.index') }}" class="md:w-1/5 md:text-center text-sm text-teal-700 hover:underline">Lihat semua</a>
            </div>

            <div class="mt-4 relative">
                {{-- edge fade hints --}}
                <div class="pointer-events-none absolute inset-y-0 left-0 w-10 hidden sm:block"></div>
                <div class="pointer-events-none absolute inset-y-0 right-0 w-10 hidden sm:block"></div>

                {{-- scroller --}}
                <div id="edu-scroller"
                    class="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-3 scroll-smooth"
                    role="region" aria-roledescription="carousel" aria-label="Konten edukasi direkomendasikan"
                    style="scroll-padding-inline:1rem;">
                    @foreach($eduRecs as $c)
                    <a href="{{ $c['url'] }}"
                        class="group snap-start shrink-0 w-80 md:w-auto
                            bg-gray-100 border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition"
                        aria-label="{{ $c['title'] }}">
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
                                        viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>

                {{-- nav buttons (desktop/tablet) --}}
                <div class="hidden sm:flex gap-2 absolute -top-10 right-0">
                    <button type="button" class="edu-nav px-3 py-1.5 rounded-lg border text-sm disabled:opacity-40" data-dir="-1" aria-label="Sebelumnya">‹</button>
                    <button type="button" class="edu-nav px-3 py-1.5 rounded-lg border text-sm disabled:opacity-40" data-dir="1" aria-label="Berikutnya">›</button>
                </div>

                {{-- dots --}}
                <div id="edu-dots" class="mt-3 flex items-center gap-1.5 justify-center sm:justify-start"></div>
            </div>
        </section>
        @endif
        @elseif($role === 'admin_clinician')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Ringkasan 30 Hari Terakhir</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="rounded-lg border p-4">
                        <div class="text-xs text-gray-500">EPDS</div>
                        <div class="text-2xl font-bold">{{ $kpi['epds_30d'] }}</div>
                    </div>
                    <div class="rounded-lg border p-4">
                        <div class="text-xs text-gray-500">DASS-21</div>
                        <div class="text-2xl font-bold">{{ $kpi['dass_30d'] }}</div>
                    </div>
                    <div class="rounded-lg border p-4">
                        <div class="text-xs text-gray-500">EPDS (user ini)</div>
                        <div class="text-2xl font-bold">{{ $epdsCount }}</div>
                    </div>
                    <div class="rounded-lg border p-4">
                        <div class="text-xs text-gray-500">DASS (user ini)</div>
                        <div class="text-2xl font-bold">{{ $dassCount }}</div>
                    </div>
                </div>
            </div>
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
                <h3 class="text-lg font-semibold text-amber-900 mb-2">Tindakan Cepat</h3>
                <div class="text-sm text-amber-900 mb-3">Review hasil skor tinggi & jadwalkan tindak lanjut.</div>
                <a href="{{ route('riwayat.skrining') }}"
                    class="inline-block px-4 py-2 rounded-lg bg-amber-600 text-white hover:bg-amber-700 text-sm">Lihat Hasil Pasien</a>
            </div>
        </div>
        @elseif($role === 'admin_facility' || $role === 'superadmin')
        <section class="mb-10">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        Ikhtisar {{ $role === 'superadmin' ? 'Semua Fasilitas' : 'Fasilitas' }}
                    </h3>
                    <p class="text-xs text-gray-500">Periode 30 hari terakhir.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('riwayat.skrining') }}"
                        class="px-3 py-2 rounded-lg border text-sm hover:bg-gray-50">
                        Laporan Skrining
                    </a>
                </div>
            </div>

            {{-- KPI Cards --}}
            @php
            $trimCounts = $facilityStats['trimester_counts'] ?? ['trimester_1'=>0,'trimester_2'=>0,'trimester_3'=>0,'pasca_hamil'=>0];
            $totalIbu = $facilityStats['total_ibu'] ?? 0;

            $items = $latestScreenings ?? [];
            $epdsFlag = 0; $dassFlag = 0;

            foreach ($items as $it) {
            if ($it['type'] === 'EPDS') {
            $t = (int)($it['scores']['total'] ?? 0);
            if ($t >= 13) $epdsFlag++;
            } else {
            $dep = (int)($it['scores']['dep'] ?? 0);
            $anx = (int)($it['scores']['anx'] ?? 0);
            $str = (int)($it['scores']['stress'] ?? 0);
            if ($dep >= 15 || $anx >= 12 || $str >= 20) $dassFlag++;
            }
            }
            @endphp

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="rounded-xl border p-4 bg-white">
                    <div class="text-xs text-gray-500">Total Ibu Terdaftar</div>
                    <div class="text-2xl font-bold">{{ number_format($totalIbu) }}</div>
                    <div class="text-xs text-gray-500 mt-1">
                        T1 {{ $trimCounts['trimester_1'] ?? 0 }} •
                        T2 {{ $trimCounts['trimester_2'] ?? 0 }} •
                        T3 {{ $trimCounts['trimester_3'] ?? 0 }} •
                        Pasca {{ $trimCounts['pasca_hamil'] ?? 0 }}
                    </div>
                </div>
                <div class="rounded-xl border p-4 bg-white">
                    <div class="text-xs text-gray-500">EPDS (30 hari)</div>
                    <div class="text-2xl font-bold">{{ $kpi['epds_30d'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">Sesi unik</div>
                </div>
                <div class="rounded-xl border p-4 bg-white">
                    <div class="text-xs text-gray-500">DASS-21 (30 hari)</div>
                    <div class="text-2xl font-bold">{{ $kpi['dass_30d'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">Sesi unik</div>
                </div>
                <div class="rounded-xl border p-4 bg-white">
                    <div class="text-xs text-gray-500">Flagged (tinggi risiko)</div>
                    <div class="text-2xl font-bold">{{ $epdsFlag + $dassFlag }}</div>
                    <div class="text-xs text-gray-500 mt-1">EPDS {{ $epdsFlag }} • DASS {{ $dassFlag }}</div>
                </div>
            </div>

            {{-- Charts + Activity --}}
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mt-6">
                {{-- Trend 30 hari --}}
                <div class="lg:col-span-2 rounded-xl border bg-white p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-900">Tren Skrining (30 hari)</h4>
                        <div class="text-xs text-gray-500">EPDS vs DASS-21</div>
                    </div>
                    <div id="chart-trend" class="w-full" style="height:320px"></div>
                </div>

                {{-- Distribusi Trimester --}}
                <div class="lg:col-span-2 rounded-xl border bg-white p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-900">Distribusi Trimester</h4>
                        <div class="text-xs text-gray-500">Terbaru per ibu</div>
                    </div>
                    <div id="chart-trimester" class="w-full" style="height:320px"></div>
                </div>

                {{-- Flagged by Type --}}
                <div class="lg:col-span-2 rounded-xl border bg-white p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-900">Flagged by Type</h4>
                        <div class="text-xs text-gray-500">Ambang: EPDS ≥ 13 • DASS dep ≥ 15 / anx ≥ 12 / str ≥ 20</div>
                    </div>
                    <div id="chart-flagged" class="w-full" style="height:280px"></div>
                </div>

                {{-- Aktivitas Terbaru --}}
                <div class="lg:col-span-2 rounded-xl border bg-white p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-900">Aktivitas Terbaru</h4>
                        <a href="{{ route('riwayat.skrining') }}" class="text-sm text-teal-700 hover:underline">Lihat semua</a>
                    </div>
                    @if(!empty($latestScreenings))
                    <ul class="divide-y">
                        @foreach($latestScreenings as $it)
                        @php
                        $isEPDS = $it['type'] === 'EPDS';
                        $badge = $isEPDS
                        ? ((int)($it['scores']['total'] ?? 0) >= 13 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700')
                        : (
                        ((int)($it['scores']['dep'] ?? 0) >= 15 || (int)($it['scores']['anx'] ?? 0) >= 12 || (int)($it['scores']['stress'] ?? 0) >= 20)
                        ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'
                        );
                        @endphp
                        <li class="py-3 flex items-start gap-3">
                            <div class="h-9 w-9 grid place-content-center rounded-full bg-gray-100 text-xs font-semibold">
                                {{ $isEPDS ? 'EP' : 'DA' }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <div class="font-medium text-gray-900">{{ $it['ibu'] ?? 'Ibu' }}</div>
                                    <div class="text-xs text-gray-500">{{ $it['label'] }}</div>
                                </div>
                                <div class="mt-0.5 text-sm text-gray-700">
                                    @if($isEPDS)
                                    Skor EPDS: <span class="font-semibold">{{ (int)($it['scores']['total'] ?? 0) }}</span>
                                    @else
                                    Dep <span class="font-semibold">{{ (int)($it['scores']['dep'] ?? 0) }}</span> •
                                    Anx <span class="font-semibold">{{ (int)($it['scores']['anx'] ?? 0) }}</span> •
                                    Str <span class="font-semibold">{{ (int)($it['scores']['stress'] ?? 0) }}</span>
                                    @endif
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[11px] {{ $badge }}">
                                {{ $isEPDS
                                            ? ((int)($it['scores']['total'] ?? 0) >= 13 ? 'Perlu perhatian' : 'Wajar')
                                            : (((int)($it['scores']['dep'] ?? 0) >= 15 || (int)($it['scores']['anx'] ?? 0) >= 12 || (int)($it['scores']['stress'] ?? 0) >= 20) ? 'Perlu perhatian' : 'Wajar') }}
                            </span>
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <div class="text-sm text-gray-500">Belum ada aktivitas.</div>
                    @endif
                </div>
            </div>
        </section>
        @endif

        <style>
            .no-scrollbar::-webkit-scrollbar {
                display: none;
            }

            .no-scrollbar {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }
        </style> 

        <!-- CTA Mobile -->
        @if (Auth::user()->role_id == 1)
        <div class="md:hidden sticky bottom-4 inset-x-0 px-4">
            <a href="{{ route('skrining.epds') }}"
                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-teal-600 text-white shadow-lg">
                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" />
                </svg>
                Mulai Skrining 
            </a>
        </div>
        @endif

        {{-- ====== SEED JSON untuk komponen client (dibaca di app.js) ====== --}}
        <x-slot name="scripts">
            <!-- <script data-swup-reload-script src="https://cdn.jsdelivr.net/npm/apexcharts"></script> -->
            <script data-swup-reload-script type="application/json" id="dashboard-seed">
                {!! json_encode([
                    'kpi' => $kpi,
                    'alerts' => $alerts,
                    'latest' => [
                        'epds' => $latestEpds ? [
                            'score' => (int)($latestEpds->total_score ?? 0),
                            'date'  => \Carbon\Carbon::parse($latestEpds->screening_date)->toDateString(),
                        ] : null,
                        'dass' => $latestDass ? [
                            'dep'  => (int)($latestDass->total_depression ?? 0),
                            'anx'  => (int)($latestDass->total_anxiety ?? 0),
                            'str'  => (int)($latestDass->total_stress ?? 0),
                            'date' => \Carbon\Carbon::parse($latestDass->screening_date)->toDateString(),
                        ] : null,
                    ],
                    'nextSchedule' => $nextSchedule,
                    'epdsTrend' => $epdsTrend ?? [],
                    'dassTrend' => $dassTrend ?? [],

                    // === Tambahan untuk Admin/Superadmin ===
                    'facilityStats' => $facilityStats ?? null,
                    'latestScreenings' => $latestScreenings ?? [],
                    'role' => $role,
                ], JSON_UNESCAPED_UNICODE) !!}
            </script>

            <script data-swup-reload-script>
                (function() {
                    const scroller = document.getElementById('edu-scroller');
                    if (!scroller) return;
                    const mantap = null; 
                    const cards = Array.from(scroller.querySelectorAll('a'));
                    const dotsWrap = document.getElementById('edu-dots');
                    const btns = document.querySelectorAll('.edu-nav');
                    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                    // build dots
                    dotsWrap.innerHTML = '';
                    cards.forEach((_, i) => {
                        const b = document.createElement('button');
                        b.type = 'button';
                        b.className = 'h-2.5 w-2.5 rounded-full bg-gray-300 aria-selected:bg-teal-600 transition-colors';
                        b.setAttribute('aria-label', 'Slide ' + (i + 1));
                        b.addEventListener('click', () => {
                            const step = cardStep();
                            scroller.scrollTo({
                                left: i * step,
                                behavior: prefersReduced ? 'auto' : 'smooth'
                            });
                        });
                        dotsWrap.appendChild(b);
                    });

                    function cardStep() {
                        const first = cards[0];
                        if (!first) return 0;
                        const gap = parseFloat(getComputedStyle(scroller).columnGap || getComputedStyle(scroller).gap || '16');
                        return first.getBoundingClientRect().width + gap;
                    }

                    function activeIndex() {
                        const step = cardStep() || 1;
                        return Math.round(scroller.scrollLeft / step);
                    }

                    function updateUI() {
                        const idx = Math.max(0, Math.min(cards.length - 1, activeIndex()));
                        dotsWrap.querySelectorAll('button').forEach((d, i) => {
                            d.setAttribute('aria-selected', i === idx ? 'true' : 'false');
                        });
                        // disable buttons at edges
                        const atStart = scroller.scrollLeft <= 2;
                        const atEnd = Math.ceil(scroller.scrollLeft + scroller.clientWidth) >= scroller.scrollWidth - 2;
                        btns.forEach(b => {
                            const dir = Number(b.dataset.dir || 1);
                            b.disabled = (dir < 0 && atStart) || (dir > 0 && atEnd);
                        });
                    }

                    // nav click
                    btns.forEach(b => {
                        b.addEventListener('click', () => {
                            const dir = Number(b.dataset.dir || 1);
                            const step = cardStep() || 320;
                            scroller.scrollBy({
                                left: dir * step,
                                behavior: prefersReduced ? 'auto' : 'smooth'
                            });
                        });
                    });

                    // drag-scroll (desktop)
                    let isDown = false,
                        startX = 0,
                        startLeft = 0;
                    scroller.addEventListener('mousedown', (e) => {
                        isDown = true;
                        startX = e.pageX;
                        startLeft = scroller.scrollLeft;
                        scroller.classList.add('cursor-grabbing');
                    });
                    ['mouseleave', 'mouseup'].forEach(ev => scroller.addEventListener(ev, () => {
                        isDown = false;
                        scroller.classList.remove('cursor-grabbing');
                    }));
                    scroller.addEventListener('mousemove', (e) => {
                        if (!isDown) return;
                        e.preventDefault();
                        scroller.scrollLeft = startLeft - (e.pageX - startX);
                    });

                    // sync on events
                    scroller.addEventListener('scroll', () => requestAnimationFrame(updateUI), {
                        passive: true
                    });
                    window.addEventListener('resize', () => requestAnimationFrame(updateUI));
                    // init
                    updateUI();
                })();
            </script>

            <script data-swup-reload-script>
                (function() {
                    // ---- util kecil ----
                    const safeNum = v => {
                        const n = Number(v);
                        return Number.isFinite(n) ? n : 0;
                    };
                    const lastNDays = (n) => {
                        const out = [],
                            now = new Date();
                        for (let i = n - 1; i >= 0; i--) {
                            const d = new Date(now);
                            d.setDate(d.getDate() - i);
                            out.push(d.toISOString().slice(0, 10));
                        }
                        return out;
                    };

                    function init() {
                        const seedEl = document.getElementById('dashboard-seed');
                        if (!seedEl) return;

                        const seed = JSON.parse(seedEl.textContent || '{}');
                        if (seed.role !== 'admin_facility' && seed.role !== 'superadmin') return;

                        const stats = seed.facilityStats || {
                            trimester_counts: {
                                trimester_1: 0,
                                trimester_2: 0,
                                trimester_3: 0,
                                pasca_hamil: 0
                            }
                        };
                        const latest = Array.isArray(seed.latestScreenings) ? seed.latestScreenings : [];

                        // ===== helper data =====
                        const days = lastNDays(30);
                        const countByDay = Object.fromEntries(days.map(d => [d, {
                            EPDS: 0,
                            DASS: 0
                        }]));
                        latest.forEach(it => {
                            const day = String(it?.date || '');
                            if (!countByDay[day]) return;
                            if (it?.type === 'EPDS') countByDay[day].EPDS += 1;
                            else countByDay[day].DASS += 1;
                        });
                        const epdsSeries = days.map(d => safeNum(countByDay[d].EPDS));
                        const dassSeries = days.map(d => safeNum(countByDay[d].DASS));

                        const tri = stats.trimester_counts || {};
                        const triSeries = [safeNum(tri.trimester_1), safeNum(tri.trimester_2), safeNum(tri.trimester_3), safeNum(tri.pasca_hamil)];

                        // ===== elemen & guard ukuran =====
                        const trendEl = document.querySelector('#chart-trend');
                        const triEl = document.querySelector('#chart-trimester');
                        const flagEl = document.querySelector('#chart-flagged');

                        const visibleReady = () => {
                            const els = [trendEl, triEl, flagEl].filter(Boolean);
                            return els.length && els.every(el => el.offsetParent !== null && el.clientWidth > 0 && el.clientHeight > 0);
                        };

                        function renderCharts() {
                            if (!window.ApexCharts) return; // safety

                            if (trendEl) {
                                const trendChart = new ApexCharts(trendEl, {
                                    chart: {
                                        type: 'area',
                                        height: 320,
                                        toolbar: {
                                            show: false
                                        },
                                        parentHeightOffset: 0
                                    },
                                    stroke: {
                                        curve: 'smooth',
                                        width: 2
                                    },
                                    dataLabels: {
                                        enabled: false
                                    },
                                    grid: {
                                        borderColor: '#eee'
                                    },
                                    series: [{
                                            name: 'EPDS',
                                            data: epdsSeries
                                        },
                                        {
                                            name: 'DASS-21',
                                            data: dassSeries
                                        }
                                    ],
                                    xaxis: {
                                        categories: days,
                                        labels: {
                                            rotate: -45
                                        }
                                    },
                                    yaxis: {
                                        min: 0,
                                        forceNiceScale: true
                                    },
                                    tooltip: {
                                        shared: true
                                    }
                                });
                                trendChart.render();
                            }

                            if (triEl) {
                                const triChart = new ApexCharts(triEl, {
                                    chart: {
                                        type: 'donut',
                                        height: 320,
                                        parentHeightOffset: 0
                                    },
                                    labels: ['Trimester I', 'Trimester II', 'Trimester III', 'Pasca Hamil'],
                                    series: triSeries,
                                    legend: {
                                        position: 'bottom'
                                    },
                                    dataLabels: {
                                        enabled: true
                                    },
                                    plotOptions: {
                                        pie: {
                                            donut: {
                                                size: '70%',
                                                labels: {
                                                    show: true,
                                                    total: {
                                                        show: true,
                                                        label: 'Total Ibu',
                                                        formatter: () => String(triSeries.reduce((a, b) => a + safeNum(b), 0))
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });
                                triChart.render();
                            }

                            if (flagEl) {
                                let epdsFlag = 0,
                                    dassFlag = 0;
                                latest.forEach(it => {
                                    if (it?.type === 'EPDS') {
                                        if (safeNum(it?.scores?.total) >= 13) epdsFlag++;
                                    } else {
                                        const dep = safeNum(it?.scores?.dep),
                                            anx = safeNum(it?.scores?.anx),
                                            str = safeNum(it?.scores?.stress);
                                        if (dep >= 15 || anx >= 12 || str >= 20) dassFlag++;
                                    }
                                });
                                const flaggedChart = new ApexCharts(flagEl, {
                                    chart: {
                                        type: 'bar',
                                        height: 280,
                                        toolbar: {
                                            show: false
                                        },
                                        parentHeightOffset: 0
                                    },
                                    plotOptions: {
                                        bar: {
                                            borderRadius: 6,
                                            columnWidth: '40%'
                                        }
                                    },
                                    dataLabels: {
                                        enabled: false
                                    },
                                    xaxis: {
                                        categories: ['EPDS', 'DASS-21']
                                    },
                                    series: [{
                                        name: 'Flagged',
                                        data: [epdsFlag, dassFlag]
                                    }]
                                });
                                flaggedChart.render();
                            }
                        }

                        // ===== tunggu ApexCharts & ukuran kontainer =====
                        (function wait(tries = 80) {
                            if (window.ApexCharts && visibleReady()) return renderCharts();
                            if (tries <= 0) return renderCharts(); // fallback, biar tetap coba render
                            setTimeout(() => wait(tries - 1), 100);
                        })();
                    }

                    // jalan segera jika DOM sudah siap, kalau belum tunggu
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', init, {
                            once: true
                        });
                    } else {
                        init();
                    }
                    // untuk navigasi Swup
                    document.addEventListener('swup:contentReplaced', init);
                })();
            </script>

        </x-slot>

</x-app-layout>