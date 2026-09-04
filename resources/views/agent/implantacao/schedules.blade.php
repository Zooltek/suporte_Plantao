@extends('layouts.agent')

@section('title', 'Implantação — Controle de Agendamentos')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Agendamentos de Implantação</h1>
            <p class="text-sm text-gray-500 mt-0.5">Agendamentos ativos — os finalizados saem automaticamente da lista</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('agent.implantacao.index') }}"
               class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-orange-600 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Visão Geral
            </a>
            <a href="{{ route('agent.schedules.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold shadow-sm transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Novo
            </a>
        </div>
    </div>

    {{-- Lista --}}
    <div class="overflow-hidden bg-white rounded-2xl border border-gray-200 shadow-sm">
        @if($schedules->isEmpty())
            <div class="py-16 text-center">
                <div class="w-14 h-14 bg-purple-50 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="h-7 w-7 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="text-gray-700 font-semibold">Nenhum agendamento ativo com atividades</p>
                <p class="text-gray-400 text-sm mt-1">Crie um agendamento e registre as atividades realizadas.</p>
                <a href="{{ route('agent.schedules.create') }}"
                   class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold transition-colors">
                    Novo Agendamento
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Agendamento</th>
                            <th class="px-4 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 hidden md:table-cell">Cliente</th>
                            <th class="px-4 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 hidden lg:table-cell">Responsável</th>
                            <th class="px-4 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 hidden lg:table-cell">Módulo</th>
                            <th class="px-4 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400">RATs</th>
                            <th class="px-4 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400">Tempo Total</th>
                            <th class="px-4 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400">Status</th>
                            <th class="px-4 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider text-gray-400 w-28">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($schedules as $schedule)
                            @php
                                $totalMinutes = $schedule->records->sum('total_minutes');
                                $totalHours   = floor($totalMinutes / 60);
                                $totalMins    = $totalMinutes % 60;
                            @endphp
                            <tr class="group hover:bg-purple-50/30 transition-colors duration-150">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-gray-900 group-hover:text-purple-700 transition-colors">
                                        {{ $schedule->display_title }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ $schedule->start_at?->format('d/m/Y') ?? '—' }}
                                    </p>
                                </td>
                                <td class="px-4 py-4 hidden md:table-cell text-gray-600">
                                    {{ $schedule->customer?->trade_name ?? $schedule->customer?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-4 hidden lg:table-cell text-gray-600">
                                    {{ $schedule->agent?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-4 hidden lg:table-cell">
                                    @if($schedule->module)
                                        <span class="inline-flex items-center px-2 py-0.5 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-full">
                                            {{ $schedule->module->name }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <a href="{{ route('agent.record.index', $schedule->id) }}"
                                       class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 bg-purple-100 text-purple-700 text-xs font-bold rounded-full hover:bg-purple-200 transition-colors"
                                       title="Ver atividades">
                                        {{ $schedule->active_records_count }}
                                    </a>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @if($totalMinutes > 0)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-teal-50 text-teal-700 text-xs font-bold rounded-full">
                                            <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            {{ sprintf('%02d:%02d', $totalHours, $totalMins) }}h
                                        </span>
                                    @else
                                        <span class="text-gray-300 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @php
                                        $statusMap = [
                                            'sch' => ['bg-blue-100',   'text-blue-700',  'Agendado'],
                                            'con' => ['bg-teal-100',   'text-teal-700',  'Confirmado'],
                                            'fin' => ['bg-green-100',  'text-green-700', 'Finalizado'],
                                            'can' => ['bg-gray-100',   'text-gray-500',  'Cancelado'],
                                        ];
                                        [$bgClass, $textClass, $label] = $statusMap[$schedule->status] ?? ['bg-gray-100', 'text-gray-500', $schedule->status];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 {{ $bgClass }} {{ $textClass }} text-[11px] font-bold rounded-full uppercase">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('agent.record.index', $schedule->id) }}"
                                           class="p-1.5 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors"
                                           title="Ver RATs">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('agent.record.create', $schedule->id) }}"
                                           class="p-1.5 text-gray-400 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors"
                                           title="Novo RAT">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('agent.schedules.edit', $schedule->id) }}"
                                           class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                                           title="Editar agendamento">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        @if(auth('admin')->user()?->can('finalize', $schedule) && !$schedule->needsAdminConfirmation())
                                            <form action="{{ route('agent.schedules.finalize', $schedule) }}" method="POST" class="inline-flex">
                                                @csrf
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-100"
                                                        title="Finalizar agendamento">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    Finalizar
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    {{-- Rodapé com somatório da página --}}
                    @php
                        $grandTotal = $schedules->sum(fn ($s) => $s->records->sum('total_minutes'));
                        $grandHours = floor($grandTotal / 60);
                        $grandMins  = $grandTotal % 60;
                    @endphp
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr class="font-bold text-sm">
                            <td class="px-5 py-3.5 text-gray-600" colspan="4">
                                Total (página atual)
                            </td>
                            <td class="px-4 py-3.5 text-center text-purple-700">
                                {{ $schedules->sum('active_records_count') }}
                            </td>
                            <td class="px-4 py-3.5 text-center text-teal-700">
                                @if($grandTotal > 0)
                                    {{ sprintf('%02d:%02d', $grandHours, $grandMins) }}h
                                @else
                                    —
                                @endif
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($schedules->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">
                    {{ $schedules->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
