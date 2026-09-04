@extends('layouts.agent')

@section('title', 'Monitor de Agentes')

@section('content')
<div class="px-4 animate-fade-in-up">
    
    {{-- Header Content --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-200 dark:border-slate-700 pb-6 mb-8 relative">
        <div class="absolute -top-10 -right-10 w-48 h-48 bg-blue-500/10 blur-3xl rounded-full pointer-events-none"></div>

        <div>
            <h1 class="text-3xl md:text-3xl font-extrabold text-gray-900 dark:text-gray-100 tracking-tight flex items-center gap-3">
                <span class="p-2 bg-blue-50 dark:bg-blue-900/30 text-blue-500 rounded-xl shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                </span>
                Monitor de Agentes
            </h1>
            <p class="mt-2 text-gray-500 dark:text-gray-400">Acompanhe a carga de trabalho, status dos tickets e finalizações em tempo real.</p>
        </div>
    </div>

    @php
        $authUser = auth('admin')->user() ?? auth()->user();
        $refreshRate = max(5, (int) ($authUser?->refresh_rate ?? 60));
        $refreshRateMs = $refreshRate * 1000;
    @endphp

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-8" x-data="{
        refresh() {
            globalThis.MonitorActions?.reloadTable();
        }
    }" x-init="setInterval(() => refresh(), {{ $refreshRateMs }})">

        {{-- Tabela de Monitoramento --}}
        <div class="xl:col-span-8 overflow-hidden">
            <div id="monitor-table-container" class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-200 dark:border-slate-700 overflow-x-auto relative">
                
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700" id="monitor-table">
                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                        <tr>
                            <th scope="col" class="w-[50px] py-4 px-4"></th>
                            <th scope="col" class="py-4 px-4 text-left text-xs font-bold text-gray-500  uppercase tracking-wider">Nome</th>
                            <th scope="col" class="py-4 px-4 text-center text-xs font-bold text-gray-500  uppercase tracking-wider w-32">Tickets Pendentes</th>
                            <th scope="col" class="py-4 px-4 text-center text-xs font-bold text-gray-500  uppercase tracking-wider w-32">Em Andamento</th>
                            <th scope="col" class="py-4 px-4 text-center text-xs font-bold text-gray-500  uppercase tracking-wider w-32">Cronograma</th>
                            <th scope="col" class="py-4 px-4 text-left text-xs font-bold text-gray-500  uppercase tracking-wider">Último Finalizado</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white  divide-y divide-gray-100 ">
                        @forelse($data as $agent)
                        <tr class="hover:bg-gray-50  transition-colors duration-200 group">
                            <td class="py-3 px-4">
                                {{-- Avatar Placeholder Elegante --}}
                                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-gray-200 to-gray-100   flex items-center justify-center text-gray-400  font-bold text-xs ring-2 ring-white  shadow-sm relative overflow-hidden group-hover:scale-105 transition-transform duration-300">
                                    {{ substr($agent['name'], 0, 1) }}
                                    <div class="absolute inset-0 bg-black opacity-0 group-hover:opacity-5 transition-opacity"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-sm font-bold text-gray-800 ">
                                {{ $agent['name'] }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-1 rounded-md text-sm font-extrabold {{ ($agent['pending'] > 0) ? 'bg-rose-50  text-rose-600  border border-rose-200 ' : 'text-gray-400 ' }}">
                                    {{ $agent['pending'] ?: '0' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($agent['status'] != 0)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50  text-amber-700  border border-amber-200  shadow-sm">
                                        {{ $agent['status'] }}
                                    </span>
                                @else
                                    <span class="text-gray-300  font-semibold">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center flex items-center justify-center gap-2 h-[52px]">
                                @if($agent['schedules_morning'] != 0 || $agent['schedules_afternoon'] != 0)
                                    @if($agent['schedules_morning'] != 0)
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-sm" title="Manhã">
                                            {{ $agent['schedules_morning'] }}
                                        </span>
                                    @endif
                                    @if($agent['schedules_afternoon'] != 0)
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200 shadow-sm" title="Tarde">
                                            {{ $agent['schedules_afternoon'] }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-gray-300 font-semibold">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-left text-xs font-medium">
                                @if($agent['last_completed'])
                                    <span class="flex items-center gap-1.5 text-gray-500 ">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                        {{ \Carbon\Carbon::parse($agent['last_completed'])->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="text-gray-400  italic">Nenhum</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-500 ">Nenhum dado atualizado no momento.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-400  mt-3 text-right flex items-center justify-end gap-1">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                Atualização automática ({{ $refreshRate }}s)
            </p>
        </div>

        {{-- Filtros e Gráfico Bar/Line --}}
        <div class="xl:col-span-4 space-y-6">
            
            {{-- Filtro de Datas Premium --}}
            {{-- Filtro de Datas Premium (Segue o tema) --}}
            <form action="{{ route('agent.monitor') }}" method="GET" class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-sm flex flex-col gap-4">
                <div class="flex items-center gap-2 mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    <span class="text-gray-800 dark:text-gray-100 font-bold tracking-tight">Filtros de Desempenho</span>
                </div>
                
                <div class="flex gap-3">
                    <div class="flex-grow">
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Início</label>
                        <input type="text" class="w-full rounded-xl border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-700/50 text-gray-800 dark:text-gray-200 shadow-inner focus:border-blue-500 focus:ring-blue-500 sm:text-sm date-mask transition-colors placeholder:text-gray-300 dark:placeholder:text-gray-500" name="start" value="{{ $start->format('d/m/Y') }}" placeholder="DD/MM/AAAA">
                    </div>
                    <div class="flex-grow">
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Fim</label>
                        <input type="text" class="w-full rounded-xl border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-700/50 text-gray-800 dark:text-gray-200 shadow-inner focus:border-blue-500 focus:ring-blue-500 sm:text-sm date-mask transition-colors placeholder:text-gray-300 dark:placeholder:text-gray-500" name="end" value="{{ $end->format('d/m/Y') }}" placeholder="DD/MM/AAAA">
                    </div>
                </div>
                
                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2.5 border border-transparent text-sm font-bold tracking-wide rounded-xl shadow-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500  transition-all active:scale-95">
                    Aplicar Filtros
                </button>
            </form>

            {{-- Gráfico Container (Invertido - Dark no Claro e Claro no Dark) --}}
            <div class="bg-slate-800 dark:bg-white rounded-2xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-slate-700 dark:border-gray-200 p-5 relative">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-100 dark:text-gray-800 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                        Tickets Concluídos
                    </h3>
                </div>
                <div class="relative w-full h-[320px] rounded-xl bg-slate-900/30 dark:bg-gray-50/50 p-2 border border-slate-700 dark:border-gray-100 inset-0">
                    <canvas id="myChart"
                            class="w-full h-full"
                            data-labels="{{ json_encode($chart_data['labels'] ?? []) }}"
                            data-values="{{ json_encode($chart_data['data'] ?? []) }}">
                    </canvas>
                </div>
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
</style>
@endsection

@push('scripts')
    @vite(['resources/js/agent/monitor/monitor-manager.js'])
@endpush
