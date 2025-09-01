<x-app-layout>
    @section('page_title','Beranda')

    <x-slot name="title">
        Dashboard | Skrining Ibu Hamil
    </x-slot>

    <div class="bg-white p-8 rounded-xl shadow-md mb-6">
        <h3 class="text-3xl font-bold text-gray-800 mb-2">Halo, {{ Auth::user()->name ?? 'Ibu' }}! 👋</h3>
        <p class="text-gray-600 text-lg">Selamat datang kembali di Aplikasi Skrining Kesehatan Mental Ibu Hamil.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-[#d1f7ef] p-6 rounded-xl shadow-sm border border-[#a7edec]">
            <h4 class="text-lg font-semibold text-[#38b2ac] mb-2">Jadwal ANC Berikutnya</h4>
            <p class="text-xl font-bold text-gray-800">15 Agustus 2025</p>
            <p class="text-sm text-gray-600">Puskesmas Maju Jaya, 10:00 WIB</p>
        </div>
        <div class="bg-[#e0f2f7] p-6 rounded-xl shadow-sm border border-[#b2e6f7]">
            <h4 class="text-lg font-semibold text-[#63b3ed] mb-2">Mulai Skrining Baru</h4>
            <p class="text-gray-700 mb-4">Lakukan skrining kesehatan mental Anda.</p>
            <a href="{{ route('skrining.epds') }}" class="btn-primary w-full inline-block text-center">Mulai Skrining</a>
        </div>
        <div class="bg-[#fde6ed] p-6 rounded-xl shadow-sm border border-[#fbd3d3]">
            <h4 class="text-lg font-semibold text-[#e53e3e] mb-2">Edukasi Kesehatan</h4>
            <p class="text-gray-700 mb-4">Pelajari lebih lanjut tentang kesehatan mental ibu hamil.</p>
            <a href="#" class="btn-secondary w-full inline-block text-center">Lihat Edukasi</a>
        </div>
    </div>
</x-app-layout>