<x-app-layout>
    @section('page_title', $content->title)
    <x-slot name="title">{{ $content->title }}</x-slot>
    <x-header-back>Konten Edukasi</x-header-back>

    <article class="mx-auto max-w-3xl py-5">
        <header class="mb-4">
            <div class="text-[11px] text-gray-500 flex items-center gap-2">
                @if($content->visibility==='facility')
                <span class="px-2 py-0.5 rounded-full bg-teal-50 text-teal-700">Fasilitas</span>
                @elseif($content->visibility==='public')
                <span class="px-2 py-0.5 rounded-full bg-sky-50 text-sky-700">Publik</span>
                @endif
                @if($content->reading_time) <span>{{ $content->reading_time }} mnt baca</span> @endif
                @if($content->published_at) <span>• {{ $content->published_at->translatedFormat('d M Y') }}</span> @endif
            </div>
            <h1 class="mt-1 text-2xl md:text-3xl font-bold text-gray-900">{{ $content->title }}</h1>
            @if($content->summary)
            <p class="mt-1 text-gray-600">{{ $content->summary }}</p>
            @endif
            <div class="mt-2 flex flex-wrap gap-1">
                @foreach($content->tags as $t)
                <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 text-[11px]">{{ $t->name }}</span>
                @endforeach
            </div>
        </header>

        @if($content->media->count() > 0)
        <div class="relative rounded-2xl overflow-hidden border bg-black/5">
            <div id="slider" class="overflow-hidden">
                <div id="slides" class="whitespace-nowrap transition-transform duration-300 ease-out">
                    @foreach($content->media as $m)
                    <div class="inline-block align-top w-full">
                        <div class="aspect-[16/9] bg-gray-100 relative">
                            @if($m->is_image)
                            <img src="{{ $m->url }}" alt="{{ $m->alt }}" class="w-full h-full object-cover" loading="lazy">
                            @elseif($m->is_video)
                            <video class="w-full h-full object-contain bg-black" controls preload="metadata" playsinline poster="{{ $m->poster_url }}">
                                <source src="{{ $m->url }}" type="{{ $m->mime ?? 'video/mp4' }}">
                                Browser Anda tidak mendukung video.
                            </video>
                            @elseif($m->is_embed)
                            <iframe class="w-full h-full" src="{{ $m->embed_src }}" title="Video"
                                frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen loading="lazy"></iframe>
                            @endif
                        </div>
                        @if($m->caption)
                        <div class="px-3 py-2 text-xs text-gray-600 bg-white border-t">{{ $m->caption }}</div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            @if($content->media->count() > 1)
            <button id="prevBtn" class="absolute left-2 top-1/2 -translate-y-1/2 grid place-items-center w-9 h-9 rounded-full bg-white/80 hover:bg-white shadow">
                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M12.293 15.707a1 1 0 010-1.414L15.586 11H4a1 1 0 110-2h11.586l-3.293-3.293a1 1 0 111.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" />
                </svg>
            </button>
            <button id="nextBtn" class="absolute right-2 top-1/2 -translate-y-1/2 grid place-items-center w-9 h-9 rounded-full bg-white/80 hover:bg-white shadow">
                <svg class="w-5 h-5 rotate-180" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M12.293 15.707a1 1 0 010-1.414L15.586 11H4a1 1 0 110-2h11.586l-3.293-3.293a1 1 0 111.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" />
                </svg>
            </button>

            <div id="dots" class="absolute bottom-2 left-0 right-0 flex justify-center gap-1.5">
                @foreach($content->media as $i => $m)
                <button class="dot w-2.5 h-2.5 rounded-full bg-white/60 border border-white/80" data-index="{{ $i }}"></button>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        @if($content->body)
        <div class="prose prose-sm sm:prose lg:prose-lg max-w-none mt-4">
            {!! \Illuminate\Support\Str::of($content->body)->markdown() !!}
        </div>
        @endif

        @if(in_array((int)(Auth::user()->role_id ?? 1), [2,3]))
        <div class="mt-6 flex gap-2">
            <a href="{{ route('edukasi.edit',$content->slug) }}" class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm">Edit</a>
            <form action="{{ route('edukasi.destroy',$content->slug) }}" method="post" onsubmit="return confirm('Hapus konten?')">
                @csrf @method('DELETE')
                <button class="px-4 py-2 rounded-lg bg-rose-600 text-white text-sm">Hapus</button>
            </form>
        </div>
        @endif
    </article>

    @if($content->media->count() > 1)
        <x-slot name="scripts">
            <script>
                (function() {
                    const wrap = document.getElementById('slides');
                    if (!wrap) return;
                    const items = wrap.children,
                        total = items.length;
                    let current = 0,
                        startX = 0,
                        dx = 0,
                        touching = false;

                    const prev = document.getElementById('prevBtn');
                    const next = document.getElementById('nextBtn');
                    const dots = document.querySelectorAll('#dots .dot');

                    function update() {
                        wrap.style.transform = `translateX(-${current*100}%)`;
                        dots.forEach((d, i) => {
                            d.style.opacity = i === current ? '1' : '0.6';
                        });
                        [...items].forEach((el, i) => {
                            const v = el.querySelector('video');
                            if (v && i !== current) {
                                try {
                                    v.pause();
                                } catch {}
                            }
                        });
                    }

                    function go(n) {
                        current = (n + total) % total;
                        update();
                    }

                    prev?.addEventListener('click', () => go(current - 1));
                    next?.addEventListener('click', () => go(current + 1));
                    dots.forEach(d => d.addEventListener('click', () => go(parseInt(d.dataset.index, 10) || 0)));

                    const slider = document.getElementById('slider');
                    slider.addEventListener('touchstart', e => {
                        touching = true;
                        startX = e.touches[0].clientX;
                        dx = 0;
                    }, {
                        passive: true
                    });
                    slider.addEventListener('touchmove', e => {
                        if (!touching) return;
                        dx = e.touches[0].clientX - startX;
                    }, {
                        passive: true
                    });
                    slider.addEventListener('touchend', () => {
                        if (!touching) return;
                        touching = false;
                        if (Math.abs(dx) > 50) {
                            go(current + (dx < 0 ? 1 : -1));
                        }
                        dx = 0;
                    });

                    document.addEventListener('keydown', e => {
                        if (e.key === 'ArrowLeft') go(current - 1);
                        if (e.key === 'ArrowRight') go(current + 1);
                    });

                    update();
                })();
            </script>
        </x-slot>
    @endif
</x-app-layout>