{{-- Bottom Navigation (mobile) --}}
<nav class="md:hidden fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 p-2 z-40">
    <div class="flex justify-around">
        <a href="{{ route('dashboard') }}"
            @class(['flex flex-col items-center text-xs', request()->routeIs('dashboard') ? 'text-blue-600' : 'text-gray-400'])>
            <svg class="w-5 h-5 mb-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
            </svg>
            Beranda
        </a>
        <a href="{{ route('skrining.epds') }}" class="flex flex-col items-center text-xs text-gray-400">
            <svg class="w-5 h-5 mb-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2V1a1 1 0 10-2 0v2H4zm5 0V1a1 1 0 10-2 0v2H7zm4 0V1a1 1 0 10-2 0v2H9zm2-2a2 2 0 012 2h2V1a1 1 0 10-2 0v2h-2zM5 5h10V18a2 2 0 01-2 2H7a2 2 0 01-2-2V5zm12 0h-1V3h1a2 2 0 012 2v13a2 2 0 01-2 2h-1V5z" clip-rule="evenodd" />
            </svg>
            Skrining
        </a>
        <a href="#" class="flex flex-col items-center text-xs text-gray-400">
            <svg class="w-5 h-5 mb-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM6 10a4 4 0 118 0 4 4 0 01-8 0z" />
            </svg>
            Edukasi
        </a>
        <a href="#" class="flex flex-col items-center text-xs text-gray-400">
            <svg class="w-5 h-5 mb-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
            </svg>
            Akun
        </a>
    </div>
</nav>