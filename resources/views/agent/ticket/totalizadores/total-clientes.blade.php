@extends('layouts.agent')

@section('content')
<div x-data="{ printing: false }" class="w-full">
    
    <!-- Hero Header Premium da Etapa de Relatórios -->
    <div class="mb-8 p-6 bg-gradient-to-r from-blue-600 to-indigo-600   rounded-2xl shadow-lg border border-indigo-500/30  relative overflow-hidden flex flex-col md:flex-row justify-between items-start md:items-center gap-6 group print:hidden">
        
        <!-- Fundo decorativo SVG -->
        <div class="absolute right-0 top-0 w-64 h-full opacity-10 pointer-events-none transition-transform duration-700 group-hover:scale-110 flex items-center justify-end">
            <svg class="h-40 w-40 -mr-10 text-white fill-current" viewBox="0 0 100 100"><path d="M50 0 L100 25 L100 75 L50 100 L0 75 L0 25 Z"></path></svg>
        </div>

        <div class="relative z-10 flex items-center gap-5">
            <div class="p-3 bg-white/10  rounded-xl backdrop-blur-md ring-1 ring-white/20">
                <svg class="w-8 h-8 text-white drop-shadow-md" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight drop-shadow-sm">
                    Relatório por Clientes
                </h1>
                <p class="mt-1 text-blue-100  text-sm font-medium">Análise de volumetria total e status pendentes por organização</p>
            </div>
        </div>
        
        <div class="relative z-10 w-full md:w-auto mt-4 md:mt-0">
            <button @click="window.print()"
                    class="flex items-center justify-center gap-2 w-full md:w-auto px-6 py-3 bg-white hover:bg-blue-50 text-blue-700    rounded-xl font-bold shadow-lg hover:shadow-xl transition-all duration-300 ring-1 ring-blue-500/20 active:scale-95 group/btn">
                <svg class="w-5 h-5 text-blue-600  group-hover/btn:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9v4a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Exportar / Imprimir
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
                            Volumetria Consolidada
                        </th>
                    </tr>
                    <tr class="bg-gray-50  border-b border-gray-200  print:border-gray-400 print:bg-gray-100">
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500  print:text-gray-600 uppercase tracking-wider w-[35%]">Organização / Cliente</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500  print:text-gray-600 uppercase tracking-wider w-[15%]">Pendentes</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500  print:text-gray-600 uppercase tracking-wider w-[20%]">Novos Hoje</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500  print:text-gray-600 uppercase tracking-wider w-[15%]">Finalizados</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500  print:text-gray-600 uppercase tracking-wider w-[15%]">Total Global</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100  print:divide-gray-300">
                    @forelse($data as $company)
                        <tr class="even:bg-gray-50 odd:bg-white   hover:bg-blue-50/60  transition-colors group print:even:bg-gray-50 print:odd:bg-white print:text-black">
                            
                            <!-- Cliente -->
                            <th scope="row" class="px-6 py-4 text-left font-normal">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 flex items-center justify-center bg-white  rounded-xl shadow-sm border border-gray-200  text-blue-600  font-extrabold text-sm group-hover:scale-110 transition-transform print:hidden">
                                        {{ mb_substr($company['name'], 0, 2) }}
                                    </div>
                                    <span class="text-sm font-bold text-gray-800  group-hover:text-blue-700  transition-colors tracking-tight print:text-black">
                                        {{ $company['name'] }}
                                    </span>
                                </div>
                            </th>
                            
                            <!-- Pendentes -->
                            <td class="px-6 py-4 text-center">
                                @if($company['pendings'] > 0)
                                    <span class="inline-flex items-center justify-center min-w-[32px] px-3 py-1.5 rounded-lg text-sm font-black bg-amber-50  text-amber-700  ring-1 ring-amber-200  print:bg-white print:text-black print:ring-1 print:ring-black">
                                        {{ $company['pendings'] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center min-w-[32px] px-3 py-1.5 rounded-lg text-sm font-medium bg-gray-50  text-gray-400  print:text-gray-400">
                                        {{ $company['pendings'] }}
                                    </span>
                                @endif
                            </td>
                            
                            <!-- Novos Hoje -->
                            <td class="px-6 py-4 text-center text-sm font-semibold text-gray-500  print:text-black">
                                {{ $company['pendings_date'] }}
                            </td>
                            
                            <!-- Finalizados -->
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm font-extrabold text-emerald-600  print:text-black">
                                    {{ $company['completed'] }}
                                </span>
                            </td>
                            
                            <!-- Total Global -->
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center bg-gray-100  px-3 py-1 rounded-lg text-sm font-black text-gray-900  border border-gray-200  print:bg-white print:border-black print:text-black">
                                    {{ $company['total'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="h-20 w-20 bg-gray-50  rounded-full flex items-center justify-center border border-dashed border-gray-300  mb-4 shadow-sm">
                                        <svg class="w-10 h-10 text-gray-400 " fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-800  mb-1">Nenhum Registro</h3>
                                    <p class="text-sm text-gray-500  max-w-sm mb-5">
                                        Ainda não há volumetria de chamados registada para clientes ativos.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                
                @if(count($data) > 0)
                    <tfoot class="bg-indigo-50/50  border-t-2 border-indigo-200  print:bg-gray-100 print:border-gray-500 print:border-t-4">
                        <tr class="font-black">
                            <th scope="row" class="px-6 py-5 text-right text-xs uppercase tracking-[0.2em] text-gray-500  print:text-black">Totais Consolidados:</th>
                            <td class="px-6 py-5 text-center text-sm text-amber-800  bg-amber-100/50  print:bg-transparent print:text-black">{{ collect($data)->sum('pendings') }}</td>
                            <td class="px-6 py-5 text-center text-sm text-gray-700  print:text-black">{{ collect($data)->sum('pendings_date') }}</td>
                            <td class="px-6 py-5 text-center text-sm text-emerald-700  print:text-black">{{ collect($data)->sum('completed') }}</td>
                            <td class="px-6 py-5 text-center text-sm border-x border-indigo-200  bg-indigo-100/80  text-indigo-900  print:bg-transparent print:text-black print:border-transparent">{{ collect($data)->sum('total') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- Rodapé de Relatório para Print -->
    <div class="mt-8 hidden print:flex justify-between items-center border-t border-gray-400 pt-4 text-[11px] text-gray-600 font-bold uppercase tracking-[0.2em]">
        <span>Plataforma de Suporte Amura</span>
        <span>Gerado por: {{ auth()->user()->name ?? 'Agente' }} • Data: {{ now()->format('d/m/Y H:i') }}</span>
    </div>
</div>

<style>
    @media print {
        @page { size: portrait; margin: 1.5cm; }
        body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
@endsection
