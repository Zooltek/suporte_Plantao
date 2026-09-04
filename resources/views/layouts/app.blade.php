<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Área de Usuário') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <link href="{{ asset('css/login.css') }}" rel="stylesheet">

    <style>
        body {
            font-family: 'Lato', sans-serif;
            background-image: url("{{ asset('img/login_bg.png') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
        }
    </style>
</head>
<body id="app-layout">
    <!-- Theme Toggle -->
    <div class="fixed top-5 right-5 z-50">
        <x-theme-toggle />
    </div>

    <main class="container py-4">
        @yield('content')
    </main>

    @stack('footer')
    <x-flash-toast />
</body>
</html>
