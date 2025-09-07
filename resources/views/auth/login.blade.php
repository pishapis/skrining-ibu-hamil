<x-guest-layout>
    <x-slot name="title">Masuk | Skrining Ibu Hamil</x-slot>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="bg-gray-50 rounded-xl border border-gray-200 p-6 shadow-md">
        <h4 class="text-2xl font-bold text-center text-gray-800 mb-6">Selamat Datang Kembali!</h4>
        <form method="POST" action="{{ route('login') }}" data-swup-form>
            @csrf
            <div class="mb-4">
                <label for="login" class="block text-gray-700 text-sm font-medium mb-2">Email atau Username</label>
                <input id="login" name="login" type="text" value="{{ old('login') }}" class="input-field" required autofocus autocomplete="username">
                @error('login') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="mb-2" x-data="{ show: false }">
                <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password</label>

                <div class="relative">
                    <input
                        id="password"
                        name="password"
                        :type="show ? 'text' : 'password'"
                        class="input-field pr-10"
                        required
                        autocomplete="current-password"
                    >

                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center"
                        @click="show = !show"
                        :aria-label="show ? 'Sembunyikan password' : 'Lihat password'"
                        :title="show ? 'Sembunyikan' : 'Lihat'">
                        <!-- eye -->
                        <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.036 12.322a1.012 1.012 0 010-.644C3.423 7.51 7.454 5 12 5s8.577 2.51 9.964 6.678c.07.214.07.43 0 .644C20.577 16.49 16.546 19 12 19s-8.577-2.51-9.964-6.678z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <!-- eye-off -->
                        <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.98 8.223A10.477 10.477 0 001.934 12C3.29 15.64 7.3 18 12 18c1.66 0 3.23-.32 4.64-.9M9.88 9.88A3 3 0 0114.12 14.12M6.1 6.1L17.9 17.9M9.88 9.88L6.1 6.1m8.24 8.24L17.9 17.9" />
                        </svg>
                    </button>
                </div>

                @error('password')
                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex items-center justify-between mt-2 mb-6">
                <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remember" class="rounded"> Ingat saya
                </label>
                @if (Route::has('password.request'))
                <a class="text-sm text-blue-600 hover:underline" href="{{ route('password.request') }}">Lupa password?</a>
                @endif
            </div>
            <button type="submit" class="btn-primary w-full">Masuk</button>
            @if (Route::has('register'))
            <p class="text-center text-gray-600 text-sm mt-4">Belum punya akun? 
                <a href="{{ route('register') }}" class="text-blue-600 font-medium hover:underline">Daftar</a>
            </p> 
            @endif
        </form>
    </div>
</x-guest-layout>