<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sucesso - Atualização de Chamado</title>
    {{-- Configuração do Tailwind via Vite + Alpine.js (Herança base Amura) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100  min-h-screen flex items-center justify-center m-0 p-4 transition-colors">
    
    <div class="bg-white  rounded-2xl shadow-xl shadow-indigo-100/50  border border-gray-200  p-8 max-w-sm w-full text-center relative overflow-hidden transform transition-all">
        
        <!-- Gradiente decorativo topo -->
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-emerald-400 to-teal-500"></div>

        <!-- Ícone Animado (Pulse) -->
        <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-emerald-50  mb-6">
            <svg class="h-10 w-10 text-emerald-500  animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <!-- Texto de Sucesso -->
        <h2 class="text-xl font-black text-gray-800  mb-2 tracking-tight">Registro Salvo!</h2>
        <p class="text-sm font-medium text-gray-500 ">O chamado foi processado na base de dados com sucesso.</p>
        
        <!-- Loading Bar -->
        <div class="mt-8 mx-auto w-3/4 h-1.5 bg-gray-100  rounded-full overflow-hidden">
            <div class="h-full bg-emerald-500 rounded-full animate-[progress_1.3s_ease-in-out_forwards]"></div>
        </div>
        <p class="mt-3 text-[10px] font-bold text-gray-400  uppercase tracking-widest">Aguarde o redirecionamento...</p>
    </div>

    {{-- Tailblock configuration for loading animation --}}
    <style>
        @keyframes progress {
            from { width: 0%; }
            to { width: 100%; }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const redirectUrl = "{{ route('agent.ticket.edit', $ticket->id) }}";

            setTimeout(function() {
                window.location.href = redirectUrl;
            }, 1300);
        });
    </script>
</body>
</html>
