<x-app-layout>
    @section('page_title','Edukasi')
    <x-slot name="title">Edukasi</x-slot>
    <x-header-back>Edukasi</x-header-back>

    <div class="sticky top-0 z-10 -mx-4 md:-mx-6 px-4 md:px-6 py-3 bg-slate-100 my-5">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-semibold">Konten Edukasi</h2>
            <form class="ml-auto" action="{{ route('edukasi.index') }}" method="get">
                <div class="flex items-center gap-2">
                    <input name="search" value="{{ request('search') }}" placeholder="Cari topik..." class="text-sm rounded-lg border px-3 py-2 w-44">
                    <button class="px-3 py-2 rounded-lg bg-gray-900 text-white text-sm">Cari</button>
                </div>
            </form>
            @if(in_array((int)(Auth::user()->role_id ?? 1), [2,3]))
            <a href="{{ route('edukasi.create') }}" class="px-3 py-2 rounded-lg bg-teal-600 text-white text-sm">+ Buat</a>
            @endif
        </div>
    </div>

    @if(session('ok'))
    <div class="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-800 text-sm">{{ session('ok') }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($contents as $c)
        <a href="{{ route('edukasi.show',$c->slug) }}" class="group rounded-2xl border bg-white overflow-hidden shadow-sm hover:shadow">
            <div class="aspect-[16/9] bg-gray-100">
                @if($c->cover_path)
                <img src="{{ asset('storage/'.$c->cover_path) }}" alt="{{ $c->title }}" class="w-full h-full object-cover">
                @endif
            </div>
            <div class="p-4">
                <div class="flex items-center gap-2 text-[11px] text-gray-500">
                    @if($c->visibility==='facility')
                    <span class="px-2 py-0.5 rounded-full bg-teal-50 text-teal-700">Fasilitas</span>
                    @elseif($c->visibility==='public')
                    <span class="px-2 py-0.5 rounded-full bg-sky-50 text-sky-700">Publik</span>
                    @endif
                    @if($c->reading_time) <span>{{ $c->reading_time }} mnt</span>@endif
                    @if($c->published_at) <span>• {{ $c->published_at->translatedFormat('d M Y') }}</span> @endif
                </div>
                <h3 class="mt-1 font-semibold text-gray-900 line-clamp-2">{{ $c->title }}</h3>
                @if($c->summary)
                <p class="mt-1 text-sm text-gray-600 line-clamp-2">{{ $c->summary }}</p>
                @endif
                <div class="mt-2 flex flex-wrap gap-1">
                    @foreach($c->tags as $t)
                    <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 text-[11px]">{{ $t->name }}</span>
                    @endforeach
                </div>
            </div>
        </a>
        @empty
        <div class="col-span-full p-6 rounded-2xl border bg-white text-center text-gray-600">
            Belum ada konten.
        </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $contents->links() }}
    </div>
</x-app-layout>