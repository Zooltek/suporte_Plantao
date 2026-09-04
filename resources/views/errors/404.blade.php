<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Página não encontrada</title>
    @vite(['resources/css/app.css'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center px-4">

    <div class="text-center max-w-md">

        {{-- Ícone --}}
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-indigo-50 border border-indigo-100 mb-6">
            <svg class="w-10 h-10 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        {{-- Código --}}
        <p class="text-7xl font-black text-gray-200 leading-none mb-2">404</p>

        {{-- Título --}}
        <h1 class="text-xl font-black text-gray-900 mb-2">Página não encontrada</h1>

        {{-- Descrição --}}
        <p class="text-sm text-gray-500 mb-8">
            O recurso que você está tentando acessar não existe ou pode ter sido movido.
        </p>

        {{-- Ações --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ url('/') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md transition-all active:scale-95">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Voltar ao Dashboard
            </a>
            <button type="button"
                    onclick="goBack()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white border border-gray-200 hover:border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold text-sm transition-all active:scale-95 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar
            </button>
        </div>
    </div>

    <script>
        function goBack() {
            if (document.referrer && document.referrer !== window.location.href) {
                window.location.href = document.referrer;
            } else if (window.history.length > 1) {
                window.history.back();
            } else if (window.opener) {
                window.close();
            } else {
                window.location.href = "{{ url('/') }}";
            }
        }
    </script>

</body>
</html>
