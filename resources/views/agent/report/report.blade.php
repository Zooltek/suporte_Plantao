<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50  transition-colors duration-300 print:bg-white text-gray-900  print:text-black">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Relatórios - Amura Suporte</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/amura-icon.png') }}?v=2">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        globalThis.ReportConfig = {
            startDate: '{{ $data['start'] }}',
            endDate: '{{ $data['end'] }}'
        };

        // Inject initial theme state based on localStorage or OS preference
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>
<body class="h-full font-sans antialiased text-gray-900  print:text-black bg-transparent">

    <div id="content" class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 animate-fade-in-up" x-data="{ loading: false }">
        
        {{-- Cabeçalho do Relatório Premium --}}
        <div class="mb-8 flex flex-col md:flex-row md:items-end md:justify-between border-b-2 border-gray-200  print:border-gray-300 pb-5 gap-4">
            <div class="flex items-start gap-4">
                <div class="hidden sm:flex flex-shrink-0 mt-1 p-3 bg-indigo-50  print:bg-white text-indigo-600  print:text-indigo-600 border border-indigo-100  print:border-none rounded-2xl shadow-sm print:shadow-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-gray-900  print:text-black mb-1">
                        {{ $data['title'] }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500  print:text-gray-600">
                        <span class="inline-flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            Período Considerado:
                        </span>
                        <span class="font-bold text-gray-700  print:text-black bg-gray-100  print:bg-transparent px-2 py-0.5 rounded-md border border-gray-200  print:border-none">{{ $data['start'] }}</span>
                        <span class="text-gray-400">até</span>
                        <span class="font-bold text-gray-700  print:text-black bg-gray-100  print:bg-transparent px-2 py-0.5 rounded-md border border-gray-200  print:border-none">{{ $data['end'] }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 print:hidden">
                {{-- Theme Toggle Interno (Já que não tem o Layout Global) --}}
                <button type="button" 
                        class="p-2.5 rounded-xl border border-gray-200  bg-white  text-gray-500  shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500 transition-colors"
                        @click="document.documentElement.classList.toggle('dark'); localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light'"
                        title="Alternar Tema Escuro">
                    <svg x-show="!document.documentElement.classList.contains('dark')" class="w-5 h-5 text-orange-500 hidden " fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                    <svg x-show="document.documentElement.classList.contains('dark')" class="w-5 h-5 text-indigo-500 block " fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                </button>

                <button @click="window.print()" class="inline-flex items-center gap-2 rounded-xl bg-orange-500 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-orange-500/20 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2  transition-all active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                    Imprimir Relatório
                </button>
            </div>
        </div>

        {{-- Tabela Estilizada (Glassmorphism + Print Styles) --}}
        <div class="overflow-hidden bg-white  print:bg-white rounded-2xl shadow-xl shadow-gray-200/50  print:shadow-none border border-gray-200  print:border-gray-300">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200  print:divide-gray-300">
                    <thead class="bg-gray-50/80  print:bg-gray-50 border-b border-gray-200  print:border-gray-400">
                        <tr>
                            @foreach($data['header'] as $row)
                                <th scope="col" class="py-4 px-5 text-left text-xs font-bold text-gray-500  print:text-black uppercase tracking-wider {{ $row['class'] }}">
                                    {{ $row['value'] }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100  print:divide-gray-200 bg-white  print:bg-white">
                        @forelse($data['content'] as $row)
                            <tr class="hover:bg-gray-50  print:hover:bg-transparent transition-colors group">
                                @foreach($row as $value)
                                    <td class="whitespace-nowrap py-4 px-5 text-sm font-medium text-gray-700  print:text-black {{ $value['class'] }}">
                                        {{ $value['value'] }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($data['header']) }}" class="py-16 text-center text-gray-500  print:text-black">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300  mb-3 print:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        <p class="text-base font-semibold">Nenhum registro encontrado no sistema.</p>
                                        <p class="text-sm mt-1">Os critérios da busca não retornaram tickets para o período.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Footer do Relatório (Aparece mais na Impressão) --}}
            <div class="bg-gray-50  print:bg-transparent border-t border-gray-200  print:border-gray-400 px-5 py-4 flex items-center justify-between text-xs text-gray-500  print:text-gray-600">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-orange-500 print:hidden" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg>
                    Gerado em: {{ now()->format('d/m/Y \à\s H:i') }}
                </div>
                <div>
                    Amura Suporte Corporation &copy; {{ date('Y') }}
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.4s ease-out forwards;
        }
        
        /* Ajustes Finos para Impressão de Alta Qualidade */
        @media print {
            body { font-size: 11pt; }
            #content { padding: 0 !important; max-width: 100% !important; margin: 0 !important; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            th { border-bottom: 2px solid #cbd5e1 !important; color: #000 !important; }
            td { color: #1e293b !important; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>

    @stack('scripts')
</body>
</html>
