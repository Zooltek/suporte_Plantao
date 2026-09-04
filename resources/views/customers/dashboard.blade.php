<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - Cliente</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-900">

    <div class="min-h-screen flex flex-col" x-data="{ openSettings: false }">
        <nav class="bg-white border-b border-slate-200 p-4 shadow-sm">
            <div class="container mx-auto flex justify-between items-center">
                <span class="font-bold text-xl text-emerald-600 tracking-tight">Portal do Cliente</span>
                <div class="text-sm text-slate-500">
                    Bem-vindo, <span class="font-semibold">{{ auth()->user()->name }}</span>
                </div>
            </div>
        </nav>

        <main class="flex-grow container mx-auto px-4 py-10">
            <div class="bg-white shadow-sm rounded-2xl p-8 border border-slate-200 relative overflow-hidden">
                <div class="absolute left-0 top-0 h-full w-2 bg-emerald-500"></div>
                
                <h1 class="text-3xl font-black text-slate-900">
                    Sucesso!
                </h1>
                
                <p class="mt-2 text-lg text-slate-600">
                    Teste C: Redirecionado para <span class="font-mono bg-emerald-50 text-emerald-700 px-2 py-1 rounded">/my-account</span> (Painel do Cliente).
                </p>

                <hr class="my-8 border-slate-100">

                <div class="space-y-4">
                    <button
                        @click="openSettings = !openSettings"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl font-bold transition-all flex items-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                        </svg>
                        <span x-text="openSettings ? 'Fechar Painel' : 'Gerenciar Minha Conta'"></span>
                    </button>

                    <div
                        x-show="openSettings"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="p-6 bg-slate-50 rounded-xl border border-slate-200"
                    >
                        <p class="text-slate-600">
                            Este espaço está pronto para receber formulários de edição de perfil e histórico de transações.
                        </p>
                    </div>
                </div>
            </div>
        </main>
        
        <footer class="p-8 text-center text-slate-400 text-xs uppercase tracking-widest">
            &copy; {{ date('Y') }} - Área do Cliente Protegida
        </footer>
    </div>

</body>
</html>
