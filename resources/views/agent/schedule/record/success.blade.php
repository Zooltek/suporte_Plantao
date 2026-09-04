<!DOCTYPE html>
<html lang="pt-br" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sucesso - Registro Salvo</title>
    
    {{-- Tailwind via Vite --}}
    @vite(['resources/css/app.css'])

    {{-- Dark Mode Local injetado antes para evitar Flash Branco na janela --}}
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        }
    </script>
    
    {{-- Micro-animações SVG e Layout --}}
    <style>
        .animate-scale-in {
            animation: scaleIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .check-icon circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .check-icon path {
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.6s forwards;
        }

        .loading-bar {
            width: 0%;
            /* Sincronizado estritamente com o setTimeout de 1300ms do JS Vanilla (1.3s) */
            animation: fillBar 1.3s linear forwards; 
        }

        @keyframes scaleIn {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes stroke {
            100% { stroke-dashoffset: 0; }
        }

        @keyframes fillBar {
            0% { width: 0%; }
            100% { width: 100%; }
        }
    </style>
</head>
<body class="bg-gray-100  h-screen w-full flex items-center justify-center p-4 transition-colors duration-300 overflow-hidden font-sans">
    
    <div class="animate-scale-in bg-white  rounded-2xl shadow-2xl border border-gray-200  w-full max-w-sm overflow-hidden relative flex flex-col items-center justify-center py-8">
        
        {{-- Fita de gradiente superior --}}
        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-emerald-400 to-teal-500"></div>

        {{-- Círculo animado de Sucesso --}}
        <div class="mb-5 relative">
            <div class="absolute inset-0 bg-emerald-100  rounded-full scale-110 blur-sm"></div>
            <svg class="check-icon w-20 h-20 text-emerald-500 relative z-10" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="stroke-current" fill="none" cx="26" cy="26" r="25" stroke-width="3" />
                <path class="stroke-current" fill="none" stroke-width="3" stroke-linecap="round" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
            </svg>
        </div>

        {{-- Texto da Mensagem --}}
        <h2 class="text-xl font-extrabold text-gray-800  tracking-tight text-center px-6">
            Atendimento Salvo!
        </h2>
        <p class="mt-2 text-sm text-gray-500  text-center font-medium px-6">
            A sua ficha técnica foi registrada com sucesso, fechando janela aguarde...
        </p>

        {{-- Loading Bar Footer (Dá UX pro momento final em que a popup morre) --}}
        <div class="absolute bottom-0 left-0 w-full h-1.5 bg-gray-100 ">
            <div class="loading-bar h-full bg-emerald-500 rounded-r-full shadow-[0_0_10px_rgba(16,185,129,0.5)]"></div>
        </div>
    </div>

    {{-- Script Vanilla para Timeout Automático --}}
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Fecha a janela após 1.3 segundos estritos (Sincronizado com a animacao CSS timer .loading-bar)
            setTimeout(() => {
                if (window.opener && typeof window.opener.refresh === 'function') {
                    window.opener.refresh();
                }
                window.close();
            }, 1300);
        });
    </script>
</body>
</html>
