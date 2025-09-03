@props(['title' => 'Skrining Ibu Hamil'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title> 
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/animate-style.css') }}" />
    <script src="{{ asset('assets/js/skrining.js') }}"></script>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f4f8;
        }

        /* animasi keluar */
    </style>

    @yield('css')
</head>

<body class="bg-[#f0f4f8]">
    <div id="swup-progress" aria-hidden="true"></div>
    <div id="app-frame" data-swup-container>
        <div class="min-h-screen flex items-center justify-center p-4">
            <div id="swup" class="w-full max-w-sm md:max-w-md">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>

@yield('scripts')
@stack('scripts')

</html>