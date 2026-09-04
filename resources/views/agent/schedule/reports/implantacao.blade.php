<!DOCTYPE html>
<html lang="pt-br" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatório - Implantação Consolidada {{ $customer->trade_name }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/amura-icon.png') }}?v=2">
    
    {{-- Carrega o Tailwind CSS --}}
    @vite(['resources/css/app.css'])

    {{-- Dark Mode Standalone (Fallback se aberto fora de Iframe/App) --}}
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        }
    </script>
    
    <style>
        @media print {
            @page { margin: 10mm; size: landscape; } /* Paisagem para tabelas robustas A4 */
            body { 
                background: white !important; 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            .no-print { display: none !important; }
            .print-border-black { border-color: #cbd5e1 !important; }
            .print-text-black { color: #000 !important; }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans text-gray-900  print:bg-white min-h-screen py-8 print:py-0 transition-colors duration-300">
    
    <div class="max-w-[1200px] w-[98%] mx-auto bg-white  rounded-2xl shadow-xl border border-gray-200  print:border-none print:shadow-none print:w-full print:rounded-none overflow-hidden pb-8">
        
        {{-- Banner Topo Gradiente Consolidado --}}
        <div class="relative w-full h-2 bg-gradient-to-r from-blue-500 to-indigo-500 no-print"></div>

        {{-- Cabeçalho do Cliente Premium --}}
        <div class="bg-gray-50  print:bg-white border-b border-gray-200  p-6 flex flex-col sm:flex-row justify-between items-start gap-4">
            
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <div class="h-10 w-10 rounded-xl bg-blue-100 text-blue-600   flex items-center justify-center print:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-extrabold text-gray-800  print-text-black tracking-tight leading-none mb-1">
                            Implantação Consolidada: <span class="text-blue-600  print-text-black">{{ $customer->trade_name }}</span>
                        </h1>
                        <p class="text-xs font-mono text-gray-500  font-semibold tracking-widest uppercase">ID Cliente: #{{ $customer->id }}</p>
                    </div>
                </div>

                {{-- Telefones & Detalhes com Badges --}}
                <div class="flex flex-wrap items-center gap-3 mt-4 print-text-black">
                    @if($customer->software)
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-indigo-50 px-2 py-1 text-xs font-bold text-indigo-700 ring-1 ring-inset ring-indigo-700/10    print-border-black print-text-black">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>
                            {{ $customer->software->name }}
                        </span>
                    @endif

                    @if($customer->telephone_1)
                    <div class="flex items-center text-sm font-medium text-gray-600  gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 print:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                        <span>{{ preg_replace('/(\d{2})(\d{4,5})(\d{4})/', '($1) $2-$3', $customer->telephone_1) }}</span>
                    </div>
                    @endif

                    @if($customer->telephone_2)
                    <div class="flex items-center text-sm font-medium text-gray-600  gap-1.5 border-l border-gray-300  pl-3 print:border-gray-300">
                        <span>{{ preg_replace('/(\d{2})(\d{4,5})(\d{4})/', '($1) $2-$3', $customer->telephone_2) }}</span>
                    </div>
                    @endif
                </div>
            </div>
            
            <div class="no-print">
                <button onclick="window.print()" class="flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-500/30 active:scale-95 text-white font-bold rounded-xl shadow-md transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                    Imprimir Consolidado
                </button>
            </div>
            
        </div>

        {{-- Tabela de Dados Principais Zebrada --}}
        <div class="w-full overflow-x-auto print:overflow-visible p-4 sm:p-6 print:p-0">

            @if(count($schedules) > 0)
                <table class="w-full text-left border-collapse border border-gray-200  print-border-black rounded-lg overflow-hidden whitespace-nowrap">
                    <thead>
                        <tr class="bg-blue-50/80  print:bg-gray-100 text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-gray-600  print:text-black border-b border-gray-200  print-border-black">
                            <th class="p-2 sm:p-3 text-center">O.S #</th>
                            <th class="p-2 sm:p-3">Responsável</th>
                            <th class="p-2 sm:p-3 text-center">Data</th>
                            <th class="p-2 sm:p-3 text-center">Hora</th>
                            <th class="p-2 sm:p-3 text-center" title="Quantidade de Atividades/Registros">Dossiês</th>
                            <th class="p-2 sm:p-3 truncate max-w-[150px]">Módulo</th>
                            <th class="p-2 sm:p-3 truncate max-w-[150px]">Funcionário</th>
                            <th class="p-2 sm:p-3 text-center bg-blue-100/50  font-extrabold text-blue-800  print:bg-transparent">Tempo Gasto</th>
                            <th class="p-2 sm:p-3 text-center">Status Confirmação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200  print:divide-gray-300 text-xs text-gray-700  print-text-black">
                        @foreach($schedules as $schedule)
                        <tr class="even:bg-gray-50 odd:bg-white   print:even:bg-gray-50 hover:bg-blue-50  transition-colors group">
                            
                            <td class="p-2 sm:p-3 text-center font-mono font-medium text-gray-700 ">{{ $schedule->id }}</td>
                            
                            {{-- Avatar Agent --}}
                            <td class="p-2 sm:p-3 align-middle">
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 rounded-full bg-gradient-to-br from-indigo-100 to-blue-200   flex items-center justify-center text-indigo-700  text-[10px] font-bold shadow-sm flex-shrink-0 print:hidden border border-indigo-200 ">
                                        {{ strtoupper(substr($schedule->agent?->name ?? '?', 0, 1)) }}
                                    </div>
                                    <span class="text-sm font-semibold text-gray-800  print-text-black truncate max-w-[120px]">
                                        {{ $schedule->agent?->name ?? '--' }}
                                    </span>
                                </div>
                            </td>

                            <td class="p-2 sm:p-3 text-center font-medium">{{ $schedule->start_at?->format("d/m/Y") ?? '--/--/----' }}</td>
                            <td class="p-2 sm:p-3 text-center font-medium">{{ $schedule->start_at?->format("H:i") ?? '--:--' }}</td>
                            
                            {{-- Contador de Fichas/Atividades --}}
                            <td class="p-2 sm:p-3 text-center">
                                @if($schedule->records->count() > 0)
                                    <span class="inline-flex items-center justify-center min-w-[28px] h-[28px] rounded-lg bg-orange-100 text-orange-700   font-bold text-xs ring-1 ring-inset ring-orange-500/20">
                                        {{ $schedule->records->count() }}
                                    </span>
                                @else
                                    <span class="text-gray-300  font-bold">-</span>
                                @endif
                            </td>

                            <td class="p-2 sm:p-3 font-medium truncate max-w-[150px]" title="{{ $schedule->module?->name }}">{{ $schedule->module?->name ?? '--' }}</td>
                            
                            <td class="p-2 sm:p-3 italic text-gray-500  print-text-black truncate max-w-[150px]">{{ $schedule->contact ?? '--' }}</td>
                            
                            {{-- Tempo --}}
                            <td class="p-2 sm:p-3 text-center text-[13px] font-bold text-blue-600  bg-blue-50/50  group-hover:bg-transparent print-text-black">{{ $schedule->hours ?? '00:00' }}</td>
                            
                            {{-- Status Badge com Tailwind injection --}}
                            @php
                                $oldBadgeClass = $schedule->getStatusBadge();
                                $twBadge = 'bg-gray-100 text-gray-700   ring-gray-500/10 border-gray-200';
                                
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
                            <td class="p-2 sm:p-3 text-center">
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-[10px] sm:text-[11px] font-bold uppercase tracking-wider border ring-1 ring-inset {{ $twBadge }} print:border-black print:text-black whitespace-nowrap">
                                    {{ $schedule->getStatusName() }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="flex flex-col items-center justify-center p-12 bg-gray-50  rounded-xl border border-dashed border-gray-300 ">
                    <svg class="h-16 w-16 text-gray-300  mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span class="text-lg font-bold text-gray-700  mb-1">Sem Implantações</span>
                    <span class="text-sm text-center text-gray-500  max-w-sm">
                        Não existem agendas/implantações pais cadastradas para compor este relatório consolidado.
                    </span>
                </div>
            @endif

            {{-- Footer / Soma de Horas Premium Consolidado --}}
            @if(count($schedules) > 0)
                <div class="mt-6 flex flex-col sm:flex-row justify-end items-end sm:items-center gap-4">
                    <div class="bg-blue-50  border border-blue-200  rounded-xl py-3 px-6 shadow-sm print:border-black print:border-2 print:rounded-none flex items-center gap-4">
                        <div class="flex flex-col text-right">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-blue-600  print-text-black">RESUMO DE IMPLANTAÇÃO DA O.S</span>
                            <span class="text-xs text-gray-500  font-medium print-text-black">Soma total das horas aplicadas</span>
                        </div>
                        <div class="h-10 w-px bg-blue-200  print:bg-black hidden sm:block"></div>
                        <div class="text-2xl font-extrabold font-mono text-blue-700  tracking-tight print-text-black">
                            {{ $total_horas ?? '00:00' }}
                        </div>
                    </div>
                </div>
            @endif

        </div>

    </div>

</body>
</html>
