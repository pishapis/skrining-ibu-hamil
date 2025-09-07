{{-- resources/views/layouts/app.blade.php --}}
@props(['title' => 'Skrining Ibu Hamil'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="manifest" href="{{ asset('./manifest.json') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/animate-style.css') }}" />
    @vite(['resources/css/app.css','resources/js/app.js'])
    <script src="{{ asset('assets/js/skrining.js') }}" defer></script>


    <style>
        [x-cloak] {
            display: none !important
        }

        body {
            font-family: 'Poppins', sans-serif
        }

        /* animasi keluar */
    </style>
    @yield('css')
</head>

<body class="bg-cover bg-no-repeat md:bg-slate-100 md:bg-none" style="background-image: url('/assets/img/bg-mobile.png');">
    <div id="swup-progress" aria-hidden="true"></div>
    <div id="app-frame" data-swup-container x-data="{ openSidebar:false }" x-bind:class="{ 'overflow-hidden': openSidebar }" class="overflow-auto min-h-screen md:flex transition-opacity duration-75">
        @include('layouts.sidebar-desktop')
        <x-session />
        <div class="flex-1">
            <main class="p-4 md:p-8 pb-24 md:pb-8">
                <header class="hidden md:flex items-center gap-4 bg-white border border-gray-200 rounded-2xl shadow-sm p-4 mb-6">
                    <div class="font-semibold">@yield('page_title','Dashboard')</div>
                    <form method="POST" action="{{ route('logout') }}" class="ml-auto" enctype="multipart/form-data">
                        @csrf
                        <button class="px-3 py-1.5 rounded-lg border text-sm">Keluar</button>
                    </form>
                </header>

                <div id="swup">
                    {{ $slot }}

                    @if (Auth::user()->role_id === 1)
                        @include('layouts.navigation-mobile')
                    @endif
                </div>
            </main>
        </div>
        @include('layouts.footer')
        {{ $scripts ?? '' }}
    </div>

    <script data-swup-reload-script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(console.error);
        }
    </script>
</body>


</html>