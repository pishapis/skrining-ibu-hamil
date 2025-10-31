<x-guest-layout>
    <x-slot name="title">Masuk | Simkeswa</x-slot>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="bg-gray-50 rounded-xl border border-gray-200 p-6 shadow-md">

        <!-- User Login Form (NIK Only) -->
        <div x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
            <h4 class="text-2xl font-bold text-center text-gray-800 mb-2">Login Ibu Hamil</h4>
            <p class="text-sm text-gray-600 text-center mb-6">Masukkan NIK (KTP) Anda untuk melanjutkan</p>
            
            <form method="POST" action="{{ route('login.user') }}">
                @csrf
                <input type="hidden" name="login_type" value="user">
                
                <div class="mb-4">
                    <label for="nik" class="block text-gray-700 text-sm font-medium mb-2">
                        Nomor Induk Kependudukan (NIK)
                    </label>
                    <input 
                        id="nik" 
                        name="nik" 
                        type="number" 
                        value="{{ old('nik') }}" 
                        class="input-field" 
                        placeholder="Contoh: 3374012345678901"
                        maxlength="16"
                        pattern="[0-9]{16}"
                        required 
                        autofocus 
                        autocomplete="off">
                    <p class="text-xs text-gray-500 mt-1">Masukkan 16 digit NIK sesuai KTP</p>
                    @error('nik') 
                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div> 
                    @enderror
                </div>

                <button type="submit" class="btn-primary w-full">Masuk</button>

                @if (Route::has('register'))
                <p class="text-center text-gray-600 text-sm mt-4">
                    Belum terdaftar? 
                    <a href="{{ route('register') }}" class="text-blue-600 font-medium hover:underline">
                        Daftar Sekarang
                    </a>
                </p> 
                @endif
            </form>
        </div>
    </div>
</x-guest-layout>