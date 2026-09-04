@extends('layouts.agent')

@section('content')
<div class="w-full">
    
    <!-- Hero Header Premium Analítico -->
    <div class="mb-8 p-6 bg-gradient-to-r from-violet-600 to-indigo-600   rounded-2xl shadow-lg border border-indigo-500/30  relative overflow-hidden flex flex-col md:flex-row justify-between items-start md:items-center gap-6 group print:hidden">
        
        <!-- Fundo decorativo SVG -->
        <div class="absolute right-0 top-0 w-64 h-full opacity-10 pointer-events-none transition-transform duration-1000 group-hover:scale-110 flex items-center justify-end">
            <svg class="h-48 w-48 -mr-16 text-white fill-current" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        </div>

        <div class="relative z-10 flex items-center gap-5">
            <div class="p-3 bg-white/10  rounded-xl backdrop-blur-md ring-1 ring-white/20">
                <svg class="w-8 h-8 text-white drop-shadow-md" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div>
                <h2 class="text-3xl font-extrabold text-white tracking-tight drop-shadow-sm">
                    Produtividade por Suporte
                </h2>
                <p class="mt-1 text-indigo-100  text-sm font-medium">Relatório analítico focado no desempenho volumétrico das equipes</p>
            </div>
        </div>
        
        <div class="relative z-10 w-full md:w-auto mt-4 md:mt-0">
            <button onclick="window.print()"
                    class="flex items-center justify-center gap-2 w-full md:w-auto px-6 py-2.5 bg-white hover:bg-gray-50 text-indigo-700    rounded-xl font-bold shadow-lg shadow-indigo-900/20 hover:shadow-xl transition-all duration-300 ring-1 ring-white/30 active:scale-95 group/btn">
                <svg class="w-4 h-4 text-indigo-600  group-hover/btn:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9v4a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimir Relatório
            </button>
        </div>
    </div>

    <!-- Container Multi-DataGrid (Equipes e Outros) -->
    <div class="space-y-8">
        @php
            $tables = [
                2 => ['title' => '01. Outros Chamados', 'color' => 'gray'],
                1 => ['title' => '02. Chamados por Suportes', 'color' => 'indigo']
            ];
        @endphp

        @foreach($tables as $key => $config)
        <section class="bg-white  rounded-2xl shadow-xl shadow-gray-200/50  border border-gray-200  overflow-hidden print:border-gray-500 print:shadow-none print:rounded-none">
            <div class="overflow-x-auto w-full">
                <table class="min-w-full text-left whitespace-nowrap border-collapse">
                    <thead>
                        <tr class="{{ $config['color'] == 'indigo' ? 'bg-indigo-600 ' : 'bg-gray-700 ' }} print:bg-gray-200 border-b border-transparent  print:border-gray-400">
                            <th id="title-{{ $key }}" scope="colgroup" colspan="5" class="py-4 px-6 text-left text-xs font-black text-white print:text-gray-900 uppercase tracking-[0.3em]">
                                {{ $config['title'] }}
                            </th>
                        </tr>
                        <tr class="bg-gray-50  border-b border-gray-200  print:border-gray-400 print:bg-gray-100">
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500  print:text-gray-600 uppercase tracking-wider w-[35%]">Agente Operacional</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500  print:text-gray-600 uppercase tracking-wider w-[15%]">Pendentes</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500  print:text-gray-600 uppercase tracking-wider w-[20%]">Novos Hoje</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500  print:text-gray-600 uppercase tracking-wider w-[15%]">Finalizados</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500  print:text-gray-600 uppercase tracking-wider w-[15%]">Total da Fila</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100  print:divide-gray-300">
                        @forelse($data[$key] ?? [] as $agent)
                            <tr class="even:bg-gray-50 odd:bg-white   hover:bg-gray-100/60  transition-colors group print:even:bg-gray-50 print:odd:bg-white print:text-black">
                                
                                <!-- Agente Suporte -->
                                <th scope="row" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800  transition-colors tracking-tight text-left print:text-black">
                                    <div class="flex items-center gap-3">
                                        <span class="w-2.5 h-2.5 rounded-full {{ $config['color'] == 'indigo' ? 'bg-indigo-500 ' : 'bg-gray-400 ' }} shadow-[0_0_8px_rgba(0,0,0,0.2)] {{ $config['color'] == 'indigo' ? 'shadow-indigo-500/50' : 'shadow-gray-400/50' }} print:hidden"></span>
                                        <span class="group-hover:text-{{ $config['color'] == 'indigo' ? 'indigo' : 'slate' }}-600 {{ $config['color'] == 'indigo' ? 'indigo' : 'slate' }}-300 transition-colors">
                                            {{ is_object($agent['agent']) ? ($agent['agent']->name ?? 'Não Atribuído') : ($agent['agent']['name'] ?? 'Não Atribuído') }}
                                        </span>
                                    </div>
                                </th>
                                
                                <!-- Pendentes -->
                                <td class="px-6 py-4 text-center">
                                    @if($agent['pendings'] > 0)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-blue-50  text-blue-700  ring-1 ring-blue-200  print:bg-white print:text-black print:ring-1 print:ring-black">
                                            {{ $agent['pendings'] }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-50  text-gray-400  print:text-gray-400">
                                            {{ $agent['pendings'] }}
                                        </span>
                                    @endif
                                </td>
                                
                                <!-- Novos Hoje -->
                                <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500  font-semibold print:text-black">
                                    {{ $agent['pendings_date'] }}
                                </td>
                                
                                <!-- Finalizados -->
                                <td class="px-6 py-4 text-center whitespace-nowrap text-sm font-bold text-emerald-600  print:text-black">
                                    {{ $agent['completed'] }}
                                </td>
                                
                                <!-- Total da Fila -->
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center bg-gray-100  px-3 py-1 rounded-lg text-sm font-black text-gray-900  border border-gray-200  print:bg-white print:border-black print:text-black">
                                        {{ $agent['total'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <span class="text-gray-400  font-bold uppercase text-xs tracking-widest italic">Nenhum registro operacional alocado</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @endforeach
    </div>

    <!-- Rodapé de Relatório para Print -->
    <div class="mt-8 hidden print:flex justify-between items-center text-[10px] text-gray-500 font-bold uppercase tracking-tighter border-t border-gray-400 pt-3">
        <span>Sistema de Monitoramento Operacional Amura</span>
        <span>Gerado em: {{ now()->format('d/m/Y H:i:s') }}</span>
    </div>
</div>

<style>
    @media print {
        @page { size: portrait; margin: 1.2cm; }
        body { background-color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .rounded-2xl { border-radius: 0.75rem !important; border: 1px solid #e5e7eb !important; }
        .shadow-sm, .shadow-lg, .shadow-xl { box-shadow: none !important; }
        tr { page-break-inside: avoid; }
    }
</style>
@endsection
