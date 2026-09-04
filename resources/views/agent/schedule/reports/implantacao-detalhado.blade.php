<!DOCTYPE html>
<html lang="pt-br" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatório - Implantação {{ $customer->trade_name }}</title>
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
            @page { margin: 10mm; size: landscape; } /* Paisagem por ter muitas colunas */
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
        
        {{-- Banner Topo Gradiente --}}
        <div class="relative w-full h-2 bg-gradient-to-r from-emerald-500 to-teal-400 no-print"></div>

        {{-- Cabeçalho do Cliente (Modernizado) --}}
        <div class="bg-gray-50  print:bg-white border-b border-gray-200  p-6 flex flex-col sm:flex-row justify-between items-start gap-4">
            
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-600   flex items-center justify-center print:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-extrabold text-gray-800  print-text-black tracking-tight leading-none mb-1">
                            Implantação: <span class="text-emerald-600  print-text-black">{{ $customer->trade_name }}</span>
                        </h1>
                        <p class="text-xs font-mono text-gray-500  font-semibold tracking-widest uppercase">ID Cliente: #{{ $customer->id }}</p>
                    </div>
                </div>

                {{-- Telefones & Detalhes com Badges --}}
                <div class="flex flex-wrap items-center gap-3 mt-4 print-text-black">
                    @if($customer->software)
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 px-2 py-1 text-xs font-bold text-blue-700 ring-1 ring-inset ring-blue-700/10    print-border-black print-text-black">
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
                <button onclick="window.print()" class="flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-500/30 active:scale-95 text-white font-bold rounded-xl shadow-md transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                    Imprimir
                </button>
            </div>
            
        </div>

        {{-- Tabela de Dados Analítica Zebrada --}}
        <div class="w-full overflow-x-auto print:overflow-visible p-4 sm:p-6 print:p-0">
            @php $hasSchedules = false; @endphp
            @foreach($schedules as $schedule)
                @if($schedule->records->count() > 0)
                    @php $hasSchedules = true; break; @endphp
                @endif
            @endforeach

            @if($hasSchedules)
                <table class="w-full text-left border-collapse border border-gray-200  print-border-black rounded-lg overflow-hidden whitespace-nowrap">
                    <thead>
                        <tr class="bg-emerald-50/80  print:bg-gray-100 text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-gray-600  print:text-black border-b border-gray-200  print-border-black">
                            <th class="p-2 sm:p-3 text-center">O.S #</th>
                            <th class="p-2 sm:p-3 max-w-[150px] truncate" title="Módulo/Área de Implantação">Módulo</th>
                            <th class="p-2 sm:p-3 text-center">Data</th>
                            <th class="p-2 sm:p-3 text-center">Início</th>
                            <th class="p-2 sm:p-3 text-center">Término</th>
                            <th class="p-2 sm:p-3 text-center" title="Atraso / Início de Intervalo">Interv.</th>
                            <th class="p-2 sm:p-3 text-center" title="Volta de Intervalo">Até</th>
                            <th class="p-2 sm:p-3 text-center bg-emerald-100/50  font-extrabold text-emerald-800  print:bg-transparent">H. Extras</th>
                            <th class="p-2 sm:p-3">Analista/Suporte</th>
                            <th class="p-2 sm:p-3 truncate max-w-[120px]">Colaborador (Cliente)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200  print:divide-gray-300 text-xs text-gray-700  print-text-black">
                        @foreach($schedules as $schedule)
                            @foreach($schedule->records as $record)
                            <tr class="even:bg-gray-50 odd:bg-white   print:even:bg-gray-50 hover:bg-emerald-50  transition-colors group">
                                <td class="p-2 sm:p-3 text-center font-mono font-medium">{{ $record->id }}</td>
                                <td class="p-2 sm:p-3 font-semibold text-gray-800  print-text-black max-w-[150px] truncate" title="{{ $record->module?->name }}">{{ $record->module?->name ?? '--' }}</td>
                                <td class="p-2 sm:p-3 text-center">{{ $record->start?->format("d/m/Y") ?? '--/--/----' }}</td>
                                <td class="p-2 sm:p-3 text-center">{{ $record->start?->format("H:i") ?? '--:--' }}</td>
                                <td class="p-2 sm:p-3 text-center">{{ $record->end?->format("H:i") ?? '--:--' }}</td>
                                <td class="p-2 sm:p-3 text-center text-gray-500  font-mono">{{ $record->interval_start?->format("H:i") ?? '--:--' }}</td>
                                <td class="p-2 sm:p-3 text-center text-gray-500  font-mono">{{ $record->interval_end?->format("H:i") ?? '--:--' }}</td>
                                <td class="p-2 sm:p-3 text-center text-[13px] font-bold text-emerald-600  bg-emerald-50/50  group-hover:bg-transparent print-text-black">{{ $record->hours ?? '00:00' }}</td>
                                <td class="p-2 sm:p-3 font-medium">{{ $record->agent?->name ?? '--' }}</td>
                                <td class="p-2 sm:p-3 italic text-gray-500  print-text-black truncate max-w-[120px]" title="{{ $record->contact }}">{{ $record->contact ?? '--' }}</td>
                            </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="flex flex-col items-center justify-center p-12 bg-gray-50  rounded-xl border border-dashed border-gray-300 ">
                    <svg class="h-16 w-16 text-gray-300  mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span class="text-lg font-bold text-gray-700  mb-1">Sem Implantações</span>
                    <span class="text-sm text-center text-gray-500  max-w-sm">
                        O cliente selecionado não tem nenhum registro de implantação detalhado neste banco de dados.
                    </span>
                </div>
            @endif

            {{-- Footer / Soma de Horas Premium --}}
            @if($hasSchedules)
                <div class="mt-6 flex flex-col sm:flex-row justify-end items-end sm:items-center gap-4">
                    <div class="bg-emerald-50  border border-emerald-200  rounded-xl py-3 px-6 shadow-sm print:border-black print:border-2 print:rounded-none flex items-center gap-4">
                        <div class="flex flex-col text-right">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-600  print-text-black">TOTAL GERAL DE IMPLANTAÇÃO</span>
                            <span class="text-xs text-gray-500  font-medium print-text-black">Soma das horas trabalhadas</span>
                        </div>
                        <div class="h-10 w-px bg-emerald-200  print:bg-black hidden sm:block"></div>
                        <div class="text-2xl font-extrabold font-mono text-emerald-700  tracking-tight print-text-black">
                            {{ $total_horas ?? '00:00' }}
                        </div>
                    </div>
                </div>
            @endif

        </div>

    </div>

</body>
</html>
