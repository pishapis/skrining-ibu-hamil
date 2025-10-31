{{-- resources/views/pages/master/pengguna/index.blade.php --}}

<x-app-layout title="Manajemen Jabatan | Simkeswa">
    @section('page_title', $title)

    {{-- ===================== HEADER + TAB ===================== --}}
    <div x-data="{ tab: @js(request('tab','jabatan')) }"
        class="grid grid-cols-1 mb-8 p-6 rounded-xl border border-gray-200 bg-gray-50 shadow-md space-y-5 mx-auto">

        <div class="flex justify-between items-center">
            <div class="mt-2 text-lg font-bold text-teal-400">Manajemen Jabatan</div>

            <button type="button"
                @click.prevent="$dispatch('open-modal', 'modal-register')"
                class="btn-primary col-span-1 justify-self-end">
                Buat Baru
            </button>
        </div>

        {{-- ===================== TABEL: DATA Jabatan ===================== --}}
        <div x-show="tab==='jabatan'" x-cloak class="bg-white p-6 rounded-xl shadow-md">
            <div class="relative overflow-x-auto">
                <table id="tableJabatan" class="min-w-full table-fixed divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase rounded-tl-lg">#</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase rounded-tr-lg">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($jabatans as $index => $item)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-xs font-medium text-gray-700">
                                {{ $jabatans->firstItem() + $index }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-right text-gray-700">{{ $item->nama }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-right">
                                <a href="#"
                                    @click.prevent="openEditJabatan('jabatan', { id: {{ $item->id }}, nama: @js($item->nama) })"
                                    class="text-blue-600 hover:text-blue-900">Edit</a>

                                <a href="#"
                                    @click.prevent="$store.confirm.id = {{ $item->id }}; $dispatch('open-modal','confirm-deletion')"
                                    class="text-red-600 hover:text-red-900">Hapus</a>

                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-xs font-medium text-gray-500 text-center">Tidak ada data</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $jabatans->withQueryString()->appends(['tab' => 'jabatan'])->links() }}
            </div>
        </div>
    </div>

    {{-- ===================== MODAL (JETSTREAM) ===================== --}}
    <x-modal name="confirm-deletion" focusable>
        <form method="POST" x-cloak
            :action="$store.confirm ? `{{ route('jabatan.destroy', '_ID_') }}`.replace('_ID_', $store.confirm.id ?? '') : '#'"
            class="p-6">
            @csrf
            <input type="hidden" name="id" :value="$store.confirm ? $store.confirm.id : ''" />
            <h2 class="text-lg font-medium text-gray-900">
                Apakah Anda yakin ingin menghapus jabatan ini?
            </h2>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Hapus') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>

    <x-modal name="edit-entity" focusable>
        <div id="editModal" class="p-4"
            x-data="{ get S(){ return $store.editJabatan ?? { type:'jabatan', form:{} } } }">

            <!-- FORM: JABATAN -->
            <form x-show="S.type === 'jabatan'" x-cloak
                method="POST"
                :action="`{{ route('jabatan.update', '_ID_') }}`.replace('_ID_', S.form?.id ?? '')"
                class="space-y-4">
                @csrf
                @method('POST')

                <input type="hidden" name="id" x-model="S.form.id" required />
                <div>
                    <label class="block text-sm font-medium">Nama Jabatan</label>
                    <input name="nama" type="text" class="input-field" x-model="S.form.nama" required>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" class="btn-secondary" x-on:click="show = false">Batal</button>
                    <button type="submit" class="btn-primary" :disabled="!(S.form?.id)">Simpan</button>
                </div>
            </form>

            <!-- (form puskesmas kamu biarkan tetap, kalau nanti dipakai halaman lain) -->
        </div>
    </x-modal>

    <x-modal name="modal-register" focusable>
        <div class="p-8">
            <form method="POST" action="{{ route('jabatan.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium">Nama Jabatan</label>
                    <input name="nama" type="text" class="input-field" required>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" class="btn-secondary" x-on:click="$dispatch('close-modal', 'modal-register')">Batal</button>
                    <button type="submit" class="btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </x-modal>

    {{-- ===================== SCRIPTS ===================== --}}
    <x-slot name="scripts">
        <script>
            // ========== Alpine Global Store + Open/Close Modal ==========
            (function() {
                const defaults = () => ({
                    id: null,
                    user_id: null,
                    nama: '',
                });

                function installStore() {
                    if (Alpine.store('editJabatan')) return;
                    Alpine.store('editJabatan', {
                        type: 'jabatan',
                        form: defaults(),
                        set(type, item = {}) {
                            this.type = type;
                            this.form = Object.assign(defaults(), item);
                        },
                        reset() {
                            this.set('jabatan', {});
                        }
                    });

                    window.openEditJabatan = function(type, item) {
                        Alpine.store('editJabatan').set(type, item);
                        window.dispatchEvent(new CustomEvent('open-modal', {
                            detail: 'edit-entity'
                        }));
                    };
                    window.closeEdit = () => window.dispatchEvent(new CustomEvent('close-modal', {
                        detail: 'edit-entity'
                    }));
                }

                function installConfirmStore() {
                    if (!Alpine.store('confirm')) {
                        Alpine.store('confirm', {
                            id: null
                        });
                    }
                }

                if (window.Alpine) installStore();
                else document.addEventListener('alpine:init', installStore, {
                    once: true
                });

                if (window.Alpine) installConfirmStore();
                else document.addEventListener('alpine:init', installConfirmStore, {
                    once: true
                });

                document.addEventListener('alpine:init', () => {
                    Alpine.store('confirm', {
                        id: null
                    });
                });

                function getModalRoot() {
                    return document.getElementById('editModal') || document;
                }
            })();
        </script>

        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>
    </x-slot>
</x-app-layout>