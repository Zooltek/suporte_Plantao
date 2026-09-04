<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 — Sessão Expirada</title>
    @vite(['resources/css/app.css'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center px-4">

    <div class="text-center max-w-md">

        {{-- Ícone --}}
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-amber-50 border border-amber-100 mb-6">
            <svg class="w-10 h-10 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        {{-- Código --}}
        <p class="text-7xl font-black text-gray-200 leading-none mb-2">419</p>

        {{-- Título --}}
        <h1 class="text-xl font-black text-gray-900 mb-2">Sua sessão expirou</h1>

        {{-- Descrição --}}
        <p class="text-sm text-gray-500 mb-8">
            Por motivos de segurança, a sua sessão foi encerrada por tempo de inatividade. Clique abaixo para fazer login novamente.
        </p>

        {{-- Ações --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('login') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md transition-all active:scale-95">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                Fazer Login
            </a>
            <button type="button"
                    onclick="window.location.reload()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white border border-gray-200 hover:border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold text-sm transition-all active:scale-95 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Recarregar Página
            </button>
        </div>
    </div>

</body>
</html>
