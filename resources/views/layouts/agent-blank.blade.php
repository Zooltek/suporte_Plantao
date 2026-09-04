<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Suporte - Administração') }}</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/amura-icon.png') }}?v=2">

    <script>
        globalThis.AppConfig = {
            url: '{{ config('app.url') }}',
            csrf: '{{ csrf_token() }}'
        };
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full font-sans antialiased text-gray-900">
    <!-- Theme Toggle -->
    <div class="fixed top-5 right-5 z-50">
        <x-theme-toggle />
    </div>
    
    <div id="app" x-data="{ loading: false }" class="min-h-screen">
        <div x-show="loading" class="fixed inset-0 z-50 flex items-center justify-center bg-white/50">
            <i class="fa-solid fa-circle-notch fa-spin fa-2xl text-blue-600"></i>
        </div>

        <main>
            @yield('content')
        </main>
    </div>

    @stack('scripts')
    <x-flash-toast />

    <noscript>
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-red-50 text-red-600 p-10 text-center">
            <strong>Atenção:</strong> Este sistema requer JavaScript para funcionar.
        </div>
    </noscript>
</body>
</html>
