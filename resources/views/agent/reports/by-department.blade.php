@extends('layouts.agent')

@section('title', 'Relatório por Departamento')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Chamados por Departamento</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Período: {{ $from->format('d/m/Y') }} a {{ $to->format('d/m/Y') }}
                ({{ $rangeDays }} dias)
            </p>
        </div>
        <a href="{{ route('agent.ticket.index') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-indigo-600 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Voltar para Chamados
        </a>
    </div>

    {{-- Filtro de período --}}
    <div class="flex flex-wrap gap-2">
        @foreach([7 => '7 dias', 30 => '30 dias', 90 => '90 dias'] as $days => $label)
            <a href="{{ route('agent.report.by-department', ['range' => $days]) }}"
               class="px-4 py-2 text-xs font-semibold rounded-xl border transition-colors
                      {{ $rangeDays === $days ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-200 hover:border-indigo-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Tabela --}}
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        @if($report->isEmpty())
            <div class="py-12 text-center">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2M5 12V7a2 2 0 012-2h10a2 2 0 012 2v5"/>
                </svg>
                <p class="text-sm font-semibold text-gray-500">Sem chamados no período selecionado.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">Departamento</th>
                            <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-widest">Total</th>
                            <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-widest">Abertos</th>
                            <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-widest">Em Andamento</th>
                            <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-widest">Resolvidos</th>
                            <th class="px-4 py-3 text-center text-[10px] font-bold text-gray-500 uppercase tracking-widest">SLA dos abertos</th>
                            <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-widest">T. médio de atendimento</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">Top categorias</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($report as $row)
                            <tr class="hover:bg-gray-50/60 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="text-sm font-semibold text-gray-800">{{ $row['department_name'] }}</div>
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-bold text-gray-900">{{ $row['total'] }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ $row['open'] }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ $row['in_progress'] }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-emerald-700">{{ $row['resolved'] }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1 text-[10px] font-bold">
                                        @if($row['sla']['critical'] > 0)
                                            <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700" title="Crítico">{{ $row['sla']['critical'] }}</span>
                                        @endif
                                        @if($row['sla']['warning'] > 0)
                                            <span class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-700" title="Aviso">{{ $row['sla']['warning'] }}</span>
                                        @endif
                                        @if($row['sla']['attention'] > 0)
                                            <span class="px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700" title="Atenção">{{ $row['sla']['attention'] }}</span>
                                        @endif
                                        @if($row['sla']['normal'] > 0)
                                            <span class="px-2 py-0.5 rounded-full bg-green-100 text-green-700" title="Normal">{{ $row['sla']['normal'] }}</span>
                                        @endif
                                        @if(array_sum($row['sla']) === 0)
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">
                                    @if($row['avg_resolution_minutes'] !== null)
                                        {{ number_format($row['avg_resolution_minutes'] / 60, 1, ',', '.') }} h
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($row['top_categories'] as $cat)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-semibold">
                                                {{ $cat['name'] }} · {{ $cat['count'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Legenda --}}
    <div class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4">
        <p class="text-xs font-bold text-indigo-800 mb-1">Legenda SLA</p>
        <div class="flex flex-wrap gap-3 text-[11px] text-indigo-700">
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span>Crítico — ultrapassou o limite máximo</span>
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-orange-500"></span>Aviso — próximo do crítico</span>
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-yellow-500"></span>Atenção — primeiro alerta</span>
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-green-500"></span>Normal — dentro do prazo</span>
        </div>
    </div>

</div>
@endsection
