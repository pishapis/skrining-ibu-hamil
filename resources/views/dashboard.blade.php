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
            <div>
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Halo, {{ $displayName }} 👋</h2>
                <p class="text-gray-600 mt-1">Selamat datang di Aplikasi Skrining Kesehatan Mental Ibu Hamil.</p>
            </div>
            <div class="hidden md:block">
                <a href="{{ route('skrining.epds') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-teal-600 text-white hover:bg-teal-700">
                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" />
                    </svg>
                    Mulai Skrining
                </a>
            </div>
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
    <div class="bg-white rounded-xl shadow-sm p-5 mb-10">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Ikhtisar Fasilitas</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-lg border p-4">
                <div class="text-xs text-gray-500">EPDS (30d)</div>
                <div class="text-2xl font-bold">{{ $kpi['epds_30d'] }}</div>
            </div>
            <div class="rounded-lg border p-4">
                <div class="text-xs text-gray-500">DASS-21 (30d)</div>
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
        <div class="mt-4 flex gap-3">
            <a href="{{ route('riwayat.skrining') }}"
                class="px-4 py-2 rounded-lg bg-gray-100 text-gray-900 text-sm hover:bg-gray-200">Laporan Skrining</a>
        </div>
    </div>
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
    <div class="md:hidden sticky bottom-4 inset-x-0 px-4">
        <a href="{{ route('skrining.epds') }}"
            class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-teal-600 text-white shadow-lg">
            <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" />
            </svg>
            Mulai Skrining
        </a>
    </div>

    {{-- ====== SEED JSON untuk komponen client (dibaca di app.js) ====== --}}
    <x-slot name="scripts">
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
                // Jika kamu punya grafik, controller sudah menyiapkan:
                'epdsTrend' => $epdsTrend ?? [],
                'dassTrend' => $dassTrend ?? [],
            ], JSON_UNESCAPED_UNICODE) !!}
        </script>

        <script data-swup-reload-script>
            (function() {
                const scroller = document.getElementById('edu-scroller');
                if (!scroller) return;

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
    </x-slot>

</x-app-layout>