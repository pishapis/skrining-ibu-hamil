<x-app-layout>
    @section('page_title', 'Token Skrining Ulang')
    <x-slot name="title">Skrining Ulang | Admin</x-slot>

    <div class="max-w-6xl mx-auto p-6">
        @if (session('success'))
        <div class="mb-4 p-3 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
            {{ session('success') }}
        </div>
        @endif

        <div class="grid md:grid-cols-2 gap-6 mb-10">
            <!-- FORM CREATE -->
            <div class="bg-white rounded-xl shadow p-5">
                <h3 class="font-semibold text-lg mb-4">Terbitkan Skrining Ulang</h3>
                <form method="POST" action="{{ route('admin.rescreens.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm mb-1">Ibu</label>
                        <select name="ibu_id" class="input-field w-full" required>
                            <option value="">-- Pilih Ibu --</option>
                            @foreach($ibus as $ibu)
                            <option value="{{ $ibu->id }}">{{ $ibu->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm mb-1">Jenis</label>
                            <select name="jenis" class="input-field w-full" required>
                                <option value="epds">EPDS</option>
                                <option value="dass">DASS-21</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm mb-1">Trimester</label>
                            <select name="trimester" class="input-field w-full" required>
                                <option value="trimester_1">Trimester I</option>
                                <option value="trimester_2">Trimester II</option>
                                <option value="trimester_3">Trimester III</option>
                                <option value="pasca_hamil">Pasca Melahirkan</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm mb-1">Maks. Pemakaian</label>
                            <input type="number" class="input-field w-full" name="max_uses" value="1" min="1" max="10">
                        </div>
                        <div>
                            <label class="block text-sm mb-1">Kedaluwarsa (opsional)</label>
                            <input type="datetime-local" class="input-field w-full" name="expires_at">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Alasan (opsional)</label>
                        <textarea class="input-field w-full" name="reason" rows="3" placeholder="Contoh: gejala memberat, follow-up, dsb."></textarea>
                    </div>
                    <div class="text-right">
                        <x-primary-button>Terbitkan</x-primary-button>
                    </div>
                </form>
            </div>

            <!-- FILTER & INFO -->
            <div class="bg-white rounded-xl shadow p-5">
                <h3 class="font-semibold text-lg mb-4">Filter</h3>
                <form method="GET" class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm mb-1">Jenis</label>
                        <select name="jenis" class="input-field w-full">
                            <option value="">Semua</option>
                            <option value="epds" @selected(request('jenis')==='epds' )>EPDS</option>
                            <option value="dass" @selected(request('jenis')==='dass' )>DASS-21</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Status</label>
                        <select name="status" class="input-field w-full">
                            <option value="">Semua</option>
                            @foreach(['active','used','revoked'] as $st)
                            <option value="{{ $st }}" @selected(request('status')===$st)>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Trimester</label>
                        <select name="trimester" class="input-field w-full">
                            <option value="">Semua</option>
                            <option value="trimester_1" @selected(request('trimester')==='trimester_1' )>Trimester I</option>
                            <option value="trimester_2" @selected(request('trimester')==='trimester_2' )>Trimester II</option>
                            <option value="trimester_3" @selected(request('trimester')==='trimester_3' )>Trimester III</option>
                            <option value="pasca_hamil" @selected(request('trimester')==='pasca_hamil' )>Pasca Melahirkan</option>
                        </select>
                    </div>
                    <div class="self-end">
                        <x-secondary-button class="w-full" type="submit">Terapkan</x-secondary-button>
                    </div>
                </form>
            </div>
        </div>

        <!-- LIST TOKENS -->
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold text-lg mb-4">Daftar Token</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-600">
                            <th class="py-2">ID</th>
                            <th class="py-2">Ibu</th>
                            <th class="py-2">Jenis</th>
                            <th class="py-2">Trimester</th>
                            <th class="py-2">Kuota</th>
                            <th class="py-2">Status</th>
                            <th class="py-2">Kedaluwarsa</th>
                            <th class="py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($tokens as $t)
                        <tr>
                            <td class="py-2 font-mono text-xs">{{ $t->id }}</td>
                            <td class="py-2">ID Ibu #{{ $t->ibu_id }}</td>
                            <td class="py-2 uppercase">{{ $t->jenis }}</td>
                            <td class="py-2">
                                @switch($t->trimester)
                                @case('trimester_1') Trimester I @break
                                @case('trimester_2') Trimester II @break
                                @case('trimester_3') Trimester III @break
                                @default Pasca Melahirkan
                                @endswitch
                            </td>
                            <td class="py-2">{{ $t->used_count }} / {{ $t->max_uses }}</td>
                            <td class="py-2">
                                <span class="px-2 py-1 rounded text-xs
                                      @class([
                                        'bg-emerald-50 text-emerald-700' => $t->status==='active',
                                        'bg-amber-50 text-amber-700' => $t->status==='used',
                                        'bg-rose-50 text-rose-700' => $t->status==='revoked'
                                      ])">
                                    {{ strtoupper($t->status) }}
                                </span>
                            </td>
                            <td class="py-2">{{ $t->expires_at ? \Carbon\Carbon::parse($t->expires_at)->format('d M Y H:i') : '—' }}</td>
                            <td class="py-2 text-right">
                                <div class="inline-flex gap-2">
                                    @if($t->status==='active')
                                    <form method="POST" action="{{ route('admin.rescreens.revoke', $t) }}">
                                        @csrf @method('PATCH')
                                        <x-secondary-button class="!bg-rose-600 !text-white">Cabut</x-secondary-button>
                                    </form>
                                    @else
                                    <form method="POST" action="{{ route('admin.rescreens.reactivate', $t) }}">
                                        @csrf @method('PATCH')
                                        <x-secondary-button>Aktifkan</x-secondary-button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="py-4 text-center text-gray-500" colspan="8">Belum ada token.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $tokens->withQueryString()->links() }}
            </div>
        </div>
    </div>
</x-app-layout>