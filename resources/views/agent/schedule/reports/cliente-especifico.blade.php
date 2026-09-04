<!DOCTYPE html>
<html lang="pt-br" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Suporte - Relatório de Atendimentos Específico</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/amura-icon.png') }}?v=2">
    
    {{-- Carrega o Tailwind CSS --}}
    @vite(['resources/css/app.css'])

    {{-- Dark Mode Local se aberto fora do App principal --}}
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        }
    </script>
    
    <style>
        /* Melhorias específicas para Impressão P&B */
        @media print {
            body { 
                background: white !important; 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            .no-print { display: none !important; }
            .print-border-black { border-color: #cbd5e1 !important; } /* Cor amigavel print */
            .print-text-black { color: #000 !important; }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans text-gray-900  print:bg-white min-h-screen py-8 print:py-0 transition-colors duration-300">
    
    {{-- Container do Relatório (Fluid na Web, Ajustado no Print) --}}
    <div class="max-w-[1200px] w-[95%] mx-auto bg-white  rounded-2xl shadow-xl border border-gray-200  print:border-none print:shadow-none print:rounded-none overflow-hidden pb-8">
        
        {{-- Fita de topo gradiente e Botão de Print --}}
        <div class="relative w-full h-2 bg-gradient-to-r from-blue-600 to-indigo-500 no-print"></div>

        {{-- Head-Block Corporativo --}}
        <div class="bg-gray-50  print:bg-white border-b border-gray-200  p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-xl font-extrabold text-gray-800  print-text-black uppercase tracking-tight flex items-center gap-2 m-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-500  no-print" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    {!! $data['title'] !!}
                </h1>
                <p class="text-sm font-medium text-gray-500  print:text-black mt-1">
                    Período Apurado: <span class="font-bold text-gray-700  print-text-black">{{ $data['start'] }} a {{ $data['end'] }}</span>
                </p>
            </div>
            
            <div class="no-print hidden sm:block">
                <button onclick="window.print()" class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-bold rounded-xl shadow-md transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                    Imprimir Relatório
                </button>
            </div>
        </div>

        {{-- Tabela de Dados Zebrada Premium --}}
        <div class="w-full overflow-x-auto print:overflow-visible p-4 sm:p-6 print:p-0 mt-2">
            
            @if(count($schedules) > 0)
                <table class="w-full text-left border-collapse border border-gray-200  print-border-black rounded-lg overflow-hidden">
                    <thead>
                        <tr class="bg-indigo-50/80  print:bg-gray-100 text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-600  print:text-black border-b border-gray-200  print-border-black">
                            <th class="p-3 text-center w-16">O.S.</th>
                            <th class="p-3">Responsável</th>
                            <th class="p-3 text-center">Data</th>
                            <th class="p-3 text-center">Início</th>
                            <th class="p-3 text-center" title="Quantidade de Atividades/Registros">Fichas</th>
                            <th class="p-3 text-center">Módulo</th>
                            <th class="p-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200  print:divide-gray-300">
                        @foreach($schedules as $schedule)
                        {{-- Zebrado Fluído --}}
                        <tr class="even:bg-gray-50 odd:bg-white   print:even:bg-gray-50/50 hover:bg-indigo-50  transition-colors group">
                            
                            {{-- ID O.S. --}}
                            <td class="p-3 align-middle text-center font-mono font-semibold text-gray-700  text-sm">
                                #{{ $schedule->id }}
                            </td>
                            
                            {{-- Responsável Avatar --}}
                            <td class="p-3 align-middle">
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 rounded-full bg-gradient-to-br from-indigo-100 to-blue-200   flex items-center justify-center text-indigo-700  text-[10px] font-bold shadow-sm flex-shrink-0 print:hidden border border-indigo-200 ">
                                        {{ strtoupper(substr($schedule->agent?->name ?? '?', 0, 1)) }}
                                    </div>
                                    <span class="text-sm font-semibold text-gray-800  print-text-black whitespace-nowrap">
                                        {{ $schedule->agent?->name ?? 'Não Definido' }}
                                    </span>
                                </div>
                            </td>
                            
                            {{-- Data --}}
                            <td class="p-3 align-middle text-center">
                                <span class="text-sm font-medium text-gray-600  print:text-black whitespace-nowrap">{{ $schedule->start_at?->format("d/m/Y") }}</span>
                            </td>
                            
                            {{-- Hora --}}
                            <td class="p-3 align-middle text-center">
                                <span class="text-sm font-medium text-gray-500  print:text-black whitespace-nowrap">{{ $schedule->start_at?->format("H:i") }}</span>
                            </td>
                            
                            {{-- Atividades --}}
                            <td class="p-3 align-middle text-center">
                                @if($schedule->records->count() > 0)
                                    <span class="inline-flex items-center justify-center min-w-[30px] h-[30px] rounded-lg bg-orange-100 text-orange-700   font-bold text-xs ring-1 ring-inset ring-orange-500/20">
                                        {{ $schedule->records->count() }}
                                    </span>
                                @else
                                    <span class="text-gray-300  font-bold">-</span>
                                @endif
                            </td>
                            
                            {{-- Módulo --}}
                            <td class="p-3 align-middle text-center">
                                <span class="text-xs uppercase tracking-wide font-bold text-gray-500  print:text-black">
                                    {{ $schedule->module?->name ?? '--' }}
                                </span>
                            </td>
                            
                            {{-- Status Badge (Reutilizando logica Amura de classe mas envelopando sem bootstrap) --}}
                            @php
                                // Converter badge- classes legadas de bootstrap em Tailwind na unha rapidamente
                                $oldBadgeClass = $schedule->getStatusBadge();
                                $twBadge = 'bg-gray-100 text-gray-700   ring-gray-500/10 border-gray-200'; // fallback light default
                                
                                if (strpos($oldBadgeClass, 'success') !== false) {
                                    $twBadge = 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 border-emerald-200   ';
                                } elseif (strpos($oldBadgeClass, 'danger') !== false) {
                                    $twBadge = 'bg-rose-50 text-rose-700 ring-rose-600/20 border-rose-200   ';
                                } elseif (strpos($oldBadgeClass, 'warning') !== false) {
                                    $twBadge = 'bg-orange-50 text-orange-700 ring-orange-600/20 border-orange-200   ';
                                } elseif (strpos($oldBadgeClass, 'info') !== false || strpos($oldBadgeClass, 'primary') !== false) {
                                    $twBadge = 'bg-blue-50 text-blue-700 ring-blue-600/20 border-blue-200   ';
                                }
                            @endphp
                            <td class="p-3 align-middle text-center">
                                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider border ring-1 ring-inset {{ $twBadge }} print:border-black print:text-black whitespace-nowrap">
                                    {{ $schedule->getStatusName() }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="flex flex-col items-center justify-center p-12 bg-gray-50  rounded-xl border border-dashed border-gray-300 ">
                    <svg class="h-16 w-16 text-gray-300  mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    <span class="text-lg font-bold text-gray-700  mb-1">Nenhum Registro</span>
                    <span class="text-sm text-center text-gray-500  max-w-sm">
                        O cliente selecionado não tem nenhum atendimento ou registro que se encaixe neste período filtrado.
                    </span>
                </div>
            @endif
            
            @if(count($schedules) > 0)
                <div class="mt-4 pt-4 border-t border-gray-200  print:border-t-2 print:border-gray-800 flex justify-end px-2">
                    <h3 class="text-sm text-gray-600  print-text-black font-semibold">
                        Total Encontrado: <span class="text-gray-900  font-extrabold text-base print-text-black ml-1">{{ count($schedules) }}</span> ordens.
                    </h3>
                </div>
            @endif

        </div>

    </div>

</body>
</html>
