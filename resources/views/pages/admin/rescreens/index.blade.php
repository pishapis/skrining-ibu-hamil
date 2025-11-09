<x-app-layout>
    @section('page_title', 'Token Skrining Ulang')
    <x-slot name="title">Skrining Ulang | Admin</x-slot>

    <div class="max-w-6xl mx-auto p-6">
        @if (session('success'))
        <div class="mb-4 p-3 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
            {{ session('success') }}
        </div>
        @endif

        @if (session('info'))
        <div class="mb-4 p-3 rounded bg-blue-50 text-blue-700 border border-blue-200">
            {{ session('info') }}
        </div>
        @endif

        @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-50 text-red-700 border border-red-200">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="grid md:grid-cols-2 gap-6 mb-10">
            <!-- FORM CREATE -->
            <div class="bg-white rounded-xl shadow p-5">
                <h3 class="font-semibold text-lg mb-4">Terbitkan Token Skrining Ulang</h3>
                <form method="POST" action="{{ route('admin.rescreens.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium mb-1">Ibu <span class="text-red-500">*</span></label>
                        <select name="ibu_id" class="input-field w-full" required>
                            <option value="">-- Pilih Ibu --</option>
                            @foreach($ibus as $ibu)
                            <option value="{{ $ibu->id }}" {{ old('ibu_id') == $ibu->id ? 'selected' : '' }}>
                                {{ $ibu->nama }} (NIK: {{ $ibu->nik }})
                            </option>
                            @endforeach
                        </select>
                        @error('ibu_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Jenis <span class="text-red-500">*</span></label>
                            <select name="jenis" class="input-field w-full" required>
                                <option value="epds" {{ old('jenis') == 'epds' ? 'selected' : '' }}>EPDS</option>
                                <option value="dass" {{ old('jenis') == 'dass' ? 'selected' : '' }}>DASS-21</option>
                            </select>
                            @error('jenis')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Trimester <span class="text-red-500">*</span></label>
                            <select name="trimester" class="input-field w-full" required>
                                <option value="trimester_1" {{ old('trimester') == 'trimester_1' ? 'selected' : '' }}>Trimester I</option>
                                <option value="trimester_2" {{ old('trimester') == 'trimester_2' ? 'selected' : '' }}>Trimester II</option>
                                <option value="trimester_3" {{ old('trimester') == 'trimester_3' ? 'selected' : '' }}>Trimester III</option>
                                <option value="pasca_hamil" {{ old('trimester') == 'pasca_hamil' ? 'selected' : '' }}>Pasca Melahirkan</option>
                            </select>
                            @error('trimester')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Maks. Pemakaian</label>
                            <input type="number" class="input-field w-full" name="max_uses" value="{{ old('max_uses', 1) }}" min="1" max="10">
                            <p class="text-xs text-gray-500 mt-1">Berapa kali token bisa dipakai</p>
                            @error('max_uses')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Kedaluwarsa</label>
                            <input type="datetime-local" class="input-field w-full" name="expires_at" value="{{ old('expires_at') }}">
                            <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ada batas</p>
                            @error('expires_at')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Alasan / Catatan</label>
                        <textarea class="input-field w-full" name="reason" rows="3" placeholder="Contoh: Gejala memberat, Follow-up hasil lab, Konseling...">{{ old('reason') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Jelaskan mengapa skrining ulang diperlukan</p>
                        @error('reason')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="text-right">
                        <x-primary-button>Terbitkan Token</x-primary-button>
                    </div>
                </form>
            </div>

            <!-- FILTER & INFO -->
            <div class="bg-white rounded-xl shadow p-5">
                <h3 class="font-semibold text-lg mb-4">Filter & Statistik</h3>
                
                <!-- Filter Form -->
                <form method="GET" class="grid grid-cols-2 gap-3 mb-6">
                    <div>
                        <label class="block text-sm font-medium mb-1">Jenis</label>
                        <select name="jenis" class="input-field w-full">
                            <option value="">Semua</option>
                            <option value="epds" @selected(request('jenis')==='epds')>EPDS</option>
                            <option value="dass" @selected(request('jenis')==='dass')>DASS-21</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <select name="status" class="input-field w-full">
                            <option value="">Semua</option>
                            <option value="active" @selected(request('status')==='active')>Aktif</option>
                            <option value="used" @selected(request('status')==='used')>Terpakai</option>
                            <option value="revoked" @selected(request('status')==='revoked')>Dicabut</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Trimester</label>
                        <select name="trimester" class="input-field w-full">
                            <option value="">Semua</option>
                            <option value="trimester_1" @selected(request('trimester')==='trimester_1')>Trimester I</option>
                            <option value="trimester_2" @selected(request('trimester')==='trimester_2')>Trimester II</option>
                            <option value="trimester_3" @selected(request('trimester')==='trimester_3')>Trimester III</option>
                            <option value="pasca_hamil" @selected(request('trimester')==='pasca_hamil')>Pasca Melahirkan</option>
                        </select>
                    </div>
                    <div class="self-end">
                        <x-secondary-button class="w-full" type="submit">Terapkan</x-secondary-button>
                    </div>
                </form>

                <!-- Statistik Sederhana -->
                <div class="border-t pt-4">
                    <p class="text-sm font-medium text-gray-700 mb-2">Info Token</p>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="bg-emerald-50 rounded p-2">
                            <p class="text-2xl font-bold text-emerald-600">{{ $tokens->where('status', 'active')->count() }}</p>
                            <p class="text-xs text-gray-600">Aktif</p>
                        </div>
                        <div class="bg-amber-50 rounded p-2">
                            <p class="text-2xl font-bold text-amber-600">{{ $tokens->where('status', 'used')->count() }}</p>
                            <p class="text-xs text-gray-600">Terpakai</p>
                        </div>
                        <div class="bg-rose-50 rounded p-2">
                            <p class="text-2xl font-bold text-rose-600">{{ $tokens->where('status', 'revoked')->count() }}</p>
                            <p class="text-xs text-gray-600">Dicabut</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- LIST TOKENS -->
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold text-lg mb-4">Daftar Token ({{ $tokens->total() }})</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-600 border-b">
                            <th class="py-2 px-2">ID Token</th>
                            <th class="py-2 px-2">Ibu (NIK)</th>
                            <th class="py-2 px-2">Jenis</th>
                            <th class="py-2 px-2">Trimester</th>
                            <th class="py-2 px-2">Kuota</th>
                            <th class="py-2 px-2">Status</th>
                            <th class="py-2 px-2">Expired</th>
                            <th class="py-2 px-2">Alasan</th>
                            <th class="py-2 px-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($tokens as $t)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-2">
                                <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded" title="{{ $t->id }}">
                                    {{ substr($t->id, 0, 8) }}...
                                </span>
                            </td>
                            <td class="py-3 px-2">
                                <div>
                                    <p class="font-medium">{{ $t->ibu->nama ?? '-' }}</p>
                                    <p class="text-xs text-gray-500">{{ $t->ibu->nik ?? '-' }}</p>
                                </div>
                            </td>
                            <td class="py-3 px-2">
                                <span class="uppercase font-semibold text-xs px-2 py-1 rounded {{ $t->jenis === 'epds' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                    {{ $t->jenis }}
                                </span>
                            </td>
                            <td class="py-3 px-2">
                                <div>
                                    <span class="text-xs">{{ $t->trimester_label }}</span>
                                    @if($t->reason)
                                    <span class="block text-xs text-gray-500 mt-1" title="{{ $t->reason }}">
                                        {{ Str::limit($t->reason, 30) }}
                                    </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-2">
                                <span class="font-semibold {{ $t->remaining_uses > 0 ? 'text-emerald-600' : 'text-gray-400' }}">
                                    {{ $t->used_count }} / {{ $t->max_uses }}
                                </span>
                            </td>
                            <td class="py-3 px-2">
                                <span class="px-2 py-1 rounded text-xs font-medium
                                    @if($t->status === 'active')
                                        bg-emerald-50 text-emerald-700
                                    @elseif($t->status === 'used')
                                        bg-amber-50 text-amber-700
                                    @else
                                        bg-rose-50 text-rose-700
                                    @endif">
                                    {{ $t->status_label }}
                                </span>
                                @if($t->isExpired())
                                <span class="block text-xs text-red-500 mt-1">Expired!</span>
                                @endif
                            </td>
                            <td class="py-3 px-2 text-xs text-gray-500">
                                {{ $t->expires_at ? $t->expires_at->format('d M Y H:i') : '—' }}
                            </td>
                            <td class="py-3 px-2 max-w-xs">
                                <p class="text-xs text-gray-600 truncate" title="{{ $t->reason }}">
                                    {{ $t->reason ?? '-' }}
                                </p>
                            </td>
                            <td class="py-3 px-2 text-right">
                                <div class="inline-flex gap-2">
                                    @if($t->status === 'active')
                                    <form method="POST" action="{{ route('admin.rescreens.revoke', $t) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="text-xs px-3 py-1 rounded bg-rose-600 text-white hover:bg-rose-700" 
                                                onclick="return confirm('Yakin cabut token ini?')">
                                            Cabut
                                        </button>
                                    </form>
                                    @else
                                    <form method="POST" action="{{ route('admin.rescreens.reactivate', $t) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="text-xs px-3 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700"
                                                onclick="return confirm('Yakin aktifkan kembali token ini?')">
                                            Aktifkan
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="py-4 text-center text-gray-500" colspan="9">Belum ada token.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $tokens->withQueryString()->links() }}
            </div>
        </div>

        <!-- Info Box -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
            <h4 class="font-semibold text-blue-900 mb-2">💡 Cara Kerja Token Skrining Ulang</h4>
            <ul class="text-sm text-blue-800 space-y-1 list-disc pl-5">
                <li><strong>Normalnya</strong>: Ibu hanya bisa skrining 1x per trimester</li>
                <li><strong>Dengan Token</strong>: Admin dapat menerbitkan token untuk izinkan skrining ulang</li>
                <li><strong>Kuota</strong>: Token punya batas pemakaian (default 1x, bisa lebih)</li>
                <li><strong>Status</strong>: Token otomatis berubah "Terpakai" saat kuota habis</li>
                <li><strong>Expired</strong>: Token bisa diberi batas waktu (opsional)</li>
                <li><strong>Tracking</strong>: Setiap skrining dicatat sebagai "batch" (skrining ke-1, ke-2, dst)</li>
                <li><strong>Revoke</strong>: Token aktif bisa dicabut kapan saja jika tidak jadi dipakai</li>
            </ul>
        </div>
    </div>
</x-app-layout>