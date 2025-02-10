<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta name="description" content="@yield('description', 'Toko Jogja Electrik adalah toko yang menyediakan berbagai produk elektronik rumah tangga berkualitas dengan harga terjangkau. Temukan peralatan dapur, produk elektronik, dan masih banyak lagi. Belanja mudah, murah, dan aman hanya di Toko Jogja Electrik.')"/>
        <title>
            @hasSection('title') 
                @yield('title') - {{ config('app.name') }} 
            @else 
                {{ config('app.name') }} 
            @endif
        </title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">
        <script type="module" src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="scroll-smooth font-sans antialiased">
        @yield('body')
        @stack('overlays')
        @stack('scripts')
    </body>
</html>
