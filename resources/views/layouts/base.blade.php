<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta name="description" content="@yield('description', 'Toko Jogja Electrik adalah toko yang menyediakan berbagai produk elektronik rumah tangga berkualitas dengan harga terjangkau. Temukan peralatan dapur, produk elektronik, dan masih banyak lagi.')"/>
        <meta property="og:url" content="{{ Request::url() }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ config('app.name') }}">
        <meta property="og:description" content="@yield('description', 'Toko Jogja Electrik adalah toko yang menyediakan berbagai produk elektronik rumah tangga berkualitas dengan harga terjangkau. Temukan peralatan dapur, produk elektronik, dan masih banyak lagi.')">
        <meta property="og:image" content="{{ asset('images/social-preview.png') }}">
        <meta property="og:image:alt" content="Banner Toko {{ config('app.name') }}">
        <meta property="og:locale" content="id_ID">
        <meta name="twitter:card" content="summary_large_image">
        <meta property="twitter:url" content="{{ Request::url() }}">
        <meta name="twitter:title" content="{{ config('app.name') }}">
        <meta name="twitter:description" content="@yield('description', 'Toko Jogja Electrik adalah toko yang menyediakan berbagai produk elektronik rumah tangga berkualitas dengan harga terjangkau. Temukan peralatan dapur, produk elektronik, dan masih banyak lagi.')">
        <meta name="twitter:image" content="{{ asset('images/social-preview.png') }}">
        <meta name="twitter:image:alt" content="Banner Toko {{ config('app.name') }}">
        <title>
            @hasSection('title') 
                @yield('title') - {{ config('app.name') }} 
            @else 
                {{ config('app.name') }} 
            @endif
        </title>
        <link rel="icon" type="image/png" href="{{ asset('favicon-96x96.png') }}" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
        <link rel="shortcut icon" href="{{ asset('favicon.svg') }}" />
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
        <meta name="apple-mobile-web-app-title" content="Jogja Electrik" />
        <link rel="manifest" href="{{ asset('site.webmanifest') }}" />
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="scroll-smooth font-sans antialiased">
        @yield('body')
        @stack('overlays')
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('analytics.measurement_id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ config('analytics.measurement_id') }}');
        </script>
        @stack('scripts')
    </body>
</html>
