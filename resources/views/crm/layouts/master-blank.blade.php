<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CRM Consuldata') }}</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/amura-icon.png') }}?v=2">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')

    <script>
        if (localStorage.getItem('theme') === 'ocean' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('ocean');
        } else {
            document.documentElement.classList.remove('ocean');
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-slate-900 font-sans text-gray-900 dark:text-gray-100 antialiased selection:bg-brand-500 selection:text-white transition-colors duration-300">

    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        
        <main>
            @yield('content')
        </main>

    </div>

    @yield('footer')
    @stack('scripts')
    <x-flash-toast />

</body>
</html>
