@extends('layouts.agent')

@section('content')
<div x-data="{ search: '', loading: false }" class="w-full">
    
    <!-- Hero Header Premium Operacional -->
    <div class="mb-8 p-6 bg-gradient-to-r from-indigo-600 to-purple-600   rounded-2xl shadow-lg border border-indigo-500/30  relative overflow-hidden flex flex-col md:flex-row justify-between items-start md:items-center gap-6 group print:hidden">
        
        <!-- Fundo decorativo SVG dinâmico -->
        <div class="absolute right-0 top-0 w-64 h-full opacity-10 pointer-events-none transition-transform duration-1000 group-hover:rotate-6 flex items-center justify-end">
            <svg class="h-48 w-48 -mr-16 text-white fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
        </div>

        <div class="relative z-10 flex items-center gap-5">
            <div class="p-3 bg-white/10  rounded-xl backdrop-blur-md ring-1 ring-white/20">
                <svg class="w-8 h-8 text-white drop-shadow-md" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <div>
                <h2 class="text-3xl font-extrabold text-white tracking-tight drop-shadow-sm">
                    Relatório Operacional
                </h2>
                <p class="mt-1 text-indigo-100  text-sm font-medium">Consolidado de chamados estratificados por Categoria de Problema</p>
            </div>
        </div>
        
        <div class="relative z-10 flex flex-col md:flex-row items-center gap-4 w-full md:w-auto mt-4 md:mt-0">
            
            <!-- Barra de Filtro / Busca Glassmorphism (AlpineJS) -->
            <div class="relative w-full md:w-72">
                <input type="text" x-model="search" placeholder="Filtrar por problema..."
                       class="w-full pl-10 pr-4 py-2.5 bg-white/10  backdrop-blur-md border border-white/20  text-white placeholder-indigo-200  font-medium rounded-xl text-sm focus:bg-white focus:text-gray-900   focus:ring-4 focus:ring-indigo-500/30 outline-none transition-all duration-300">
                <svg class="w-5 h-5 text-indigo-200  absolute left-3 top-2.5 transition-colors" :class="{'text-indigo-500': search.length > 0}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                
                <!-- Botão Limpar Filtro -->
                <button x-show="search.length > 0" @click="search = ''" x-transition class="absolute right-3 top-3 text-indigo-300 hover:text-indigo-600  transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <button @click="window.print()"
                    class="flex items-center justify-center gap-2 w-full md:w-auto px-6 py-2.5 bg-white hover:bg-gray-50 text-indigo-700    rounded-xl font-bold shadow-lg shadow-indigo-900/20 hover:shadow-xl transition-all duration-300 ring-1 ring-white/30 active:scale-95 group/btn">
                <svg class="w-4 h-4 text-indigo-600  group-hover/btn:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9v4a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimir
            </button>
        </div>
    </div>

    <!-- Container Premium DataGrid -->
    <div class="bg-white  rounded-2xl shadow-xl shadow-gray-200/50  border border-gray-200  overflow-hidden print:border-gray-500 print:shadow-none print:rounded-none">
        
        <div class="overflow-x-auto w-full">
            <table class="min-w-full text-left whitespace-nowrap border-collapse">
                <thead>
                    <tr class="bg-indigo-50/80  print:bg-gray-200">
                        <th scope="colgroup" colspan="5" class="py-4 text-center text-xs font-black text-indigo-800  print:text-gray-900 uppercase tracking-[0.3em] border-b border-indigo-100  print:border-gray-400">
                            Distribuição de Chamados por Categoria
                        </th>
                    </tr>
                    <tr class="bg-gray-50  border-b border-gray-200  print:border-gray-400 print:bg-gray-100">
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500  print:text-gray-600 uppercase tracking-wider w-[35%]">Escopo de Problema</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500  print:text-gray-600 uppercase tracking-wider w-[15%]">Pendentes</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500  print:text-gray-600 uppercase tracking-wider w-[20%]">Abertos Hoje</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500  print:text-gray-600 uppercase tracking-wider w-[15%]">Finalizados</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500  print:text-gray-600 uppercase tracking-wider w-[15%]">Total Acumulado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100  print:divide-gray-300">
                    @forelse($data as $trouble)
                        
                        <tr x-show="search === '' || '{{ strtolower($trouble['name']) }}'.includes(search.toLowerCase())" x-cloak
                            class="even:bg-gray-50 odd:bg-white   hover:bg-indigo-50/60  transition-colors group print:even:bg-gray-50 print:odd:bg-white print:text-black">
                            
                            <!-- Nome Problema -->
                            <th scope="row" class="px-6 py-4 text-left font-normal">
                                <span class="text-sm font-bold text-gray-800  group-hover:text-indigo-700  transition-colors tracking-tight print:text-black">
                                    {{ $trouble['name'] }}
                                </span>
                            </th>
                            
                            <!-- Pendentes -->
                            <td class="px-6 py-4 text-center">
                                @if($trouble['pendings'] > 0)
                                    <span class="inline-flex items-center justify-center min-w-[32px] px-3 py-1.5 rounded-full text-xs font-black bg-amber-50  text-amber-700  ring-1 ring-amber-200  print:bg-white print:text-black print:ring-1 print:ring-black">
                                        {{ $trouble['pendings'] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center min-w-[32px] px-3 py-1.5 rounded-full text-xs font-medium bg-gray-50  text-gray-400  print:text-gray-400">
                                        {{ $trouble['pendings'] }}
                                    </span>
                                @endif
                            </td>
                            
                            <!-- Abertos Hoje -->
                            <td class="px-6 py-4 text-center text-sm font-semibold text-gray-500  print:text-black">
                                {{ $trouble['pendings_date'] }}
                            </td>
                            
                            <!-- Finalizados -->
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm font-extrabold text-emerald-600  print:text-black">
                                    {{ $trouble['completed'] }}
                                </span>
                            </td>
                            
                            <!-- Total Acumulado -->
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center bg-gray-100  px-3 py-1 rounded-lg text-sm font-black text-gray-900  border border-gray-200  print:bg-white print:border-black print:text-black">
                                    {{ $trouble['total'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="h-16 w-16 bg-gray-50  rounded-full flex items-center justify-center border border-dashed border-gray-300  mb-3 shadow-sm">
                                        <svg class="w-8 h-8 text-gray-400 " fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <span class="text-gray-400  font-bold uppercase text-xs tracking-widest">Nenhuma categoria registrada</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                
                @if(count($data) > 0)
                    {{-- Alpine X-Show para o Footer: Oculta o Totalizador se a Busca não encontrar nada --}}
                    <tfoot class="bg-indigo-50/50  border-t-2 border-indigo-200  print:bg-gray-100 print:border-gray-500 print:border-t-4" 
                           x-show="Array.from($el.closest('table').querySelectorAll('tbody tr')).filter(row => row.style.display !== 'none').length > 0">
                        <tr class="font-black">
                            <th scope="row" class="px-6 py-4 text-right text-xs uppercase text-gray-500  tracking-widest print:text-black">Totais Gerais Consolidados:</th>
                            <td class="px-6 py-4 text-center text-sm text-gray-900  print:text-black">{{ collect($data)->sum('pendings') }}</td>
                            <td class="px-6 py-4 text-center text-sm text-gray-900  print:text-black">{{ collect($data)->sum('pendings_date') }}</td>
                            <td class="px-6 py-4 text-center text-sm text-emerald-700  print:text-black">{{ collect($data)->sum('completed') }}</td>
                            <td class="px-6 py-4 text-center text-sm border-x border-indigo-200  bg-indigo-100/80  text-indigo-900  print:bg-transparent print:text-black print:border-transparent">{{ collect($data)->sum('total') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- Rodapé de Relatório para Print -->
    <div class="mt-8 hidden print:flex justify-between items-center text-[10px] text-gray-500 font-bold uppercase tracking-tighter border-t border-gray-400 pt-3">
        <span>Extraído via Sistema Operacional Amura</span>
        <span>Gerado em: {{ now()->format('d/m/Y H:i:s') }}</span>
    </div>
</div>

<style>
    @media print {
        @page { size: portrait; margin: 1.2cm; }
        body { background-color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .rounded-3xl { border-radius: 0.75rem !important; border: 1px solid #e5e7eb !important; }
        .shadow-sm, .shadow-lg { box-shadow: none !important; }
    }
</style>
@endsection
