<x-app-layout title="Manajemen Pengguna | Skrining Ibu Hamil">
    @section('page_title', $title)
    @section('css')
        <!-- <link rel="stylesheet" href="{{ url('assets/modules/bootstrap/css/bootstrap.min.css') }}"> -->
        <link rel="stylesheet" href="{{ url('assets/modules/datatables/datatables.min.css') }}">
    @endsection

    <div class="grid grid-cols-1 mb-8 p-6 rounded-xl border border-gray-200 bg-gray-50 shadow-md space-y-5 mx-auto ">
        <div class="flex justify-between">
            <div class="mt-2 text-lg font-bold text-teal-400">Daftar Pengguna Skrining Ibu Hamil</div>
            <button type="button" class="btn-primary">Buat Akun Baru</button>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md">
            <div class="relative overflow-x-auto">
                <table id="myTableLead" class="min-w-full table-fixed divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider rounded-tl-lg">#</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nik</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tempat Lahir</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Lahir</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gol Darah</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No JKN</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Telp</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alamat</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Puskesmas</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Faskes</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider rounded-tr-lg">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($data as $index => $item)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-xs font-medium text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-xs font-medium text-gray-500">{{ $item->nama }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-xs font-medium text-gray-500">{{ $item->nik }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-xs font-medium text-gray-500">{{ $item->tempat_lahir }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-xs font-medium text-gray-500">{{ formatTanggal($item->tanggal_lahir) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-xs font-medium text-gray-500 uppercase">{{ $item->golongan_darah }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-xs font-medium text-gray-500">{{ $item->no_jkn }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-xs font-medium text-gray-500">{{ $item->no_telp }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-xs font-medium text-gray-500">{{ $item->alamat_rumah }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-xs font-medium text-gray-500">{{ $item->puskesmas?->nama }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-xs font-medium text-gray-500">{{ $item->faskes?->nama }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-xs font-medium text-gray-500">
                                <a href="#" class="text-blue-600 hover:text-blue-900">Edit</a>
                                <form action="#" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="px-6 py-4 text-xs font-medium text-gray-500 text-center">Tidak ada data</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @section('scripts')
            <script src="{{ url('js/jquery-3.7.1.js') }}"></script>
            <script src="{{ url('assets/modules/datatables/datatables.min.js') }}"></script>
            <script src="{{ url('assets/modules/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js') }}"></script>
            <script src="{{ url('js/my_datatables.js') }}"></script>
    @endsection
</x-app-layout>
