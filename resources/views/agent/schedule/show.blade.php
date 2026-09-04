@extends('layouts.agent')

@section('title', 'Agendamento #' . $schedule->id)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 space-y-6">
    @php
        $activeRecordCount = $schedule->records->where('status', 1)->count();
        $canFinalize = auth('admin')->user()?->can('finalize', $schedule) ?? false;
        $canShowFinalizeAction = $canFinalize && !$schedule->isFinalized() && !$schedule->isCancelled();
        $finalizeBlockedReason = null;

        if ($canShowFinalizeAction && $schedule->needsAdminConfirmation()) {
            $finalizeBlockedReason = 'Confirme o agendamento com o administrador antes de encerrar a implantação.';
        } elseif ($canShowFinalizeAction && $activeRecordCount === 0) {
            $finalizeBlockedReason = 'Registre pelo menos um RAT antes de finalizar este agendamento.';
        }
    @endphp

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('agent.calendar.condensed', ['active' => 'schedules']) }}"
               class="flex-shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-800 transition-colors"
               title="Voltar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="min-w-0">
                <p class="text-xs text-gray-400 uppercase tracking-widest font-semibold mb-0.5">Agendamento #{{ $schedule->id }}</p>
                <h1 class="text-xl font-bold text-gray-800 truncate" title="{{ $schedule->display_title }}">
                    {{ $schedule->display_title }}
                </h1>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0 flex-wrap justify-end">
            @php
                $statusClasses = match($schedule->status) {
                    'pen' => 'bg-yellow-100 text-yellow-700 border border-yellow-200',
                    'sch' => 'bg-blue-100 text-blue-700 border border-blue-200',
                    'con' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
                    'fin' => 'bg-gray-100 text-gray-600 border border-gray-200',
                    'can' => 'bg-red-100 text-red-600 border border-red-200',
                    default => 'bg-gray-100 text-gray-500 border border-gray-200',
                };
            @endphp
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide {{ $statusClasses }}">
                {{ $schedule->getStatusName() }}
            </span>
            <a href="{{ route('agent.schedules.edit', $schedule) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar
            </a>
            @if($canShowFinalizeAction && !$finalizeBlockedReason)
                <form action="{{ route('agent.schedules.finalize', $schedule) }}" method="POST" class="inline-flex">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Finalizar
                    </button>
                </form>
            @endif

            @if($schedule->isPending() && auth('admin')->user()->can('confirmOwn', $schedule))
                <form action="{{ route('agent.schedules.confirm-own', $schedule) }}" method="POST" class="inline-flex">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Confirmar
                    </button>
                </form>
            @endif

            @if(!$schedule->isFinalized() && !$schedule->isCancelled() && auth('admin')->user()->can('cancel', $schedule))
                <form action="{{ route('agent.schedules.cancel', $schedule) }}" method="POST" class="inline-flex"
                      onsubmit="return confirm('Cancelar este agendamento?')">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 hover:bg-red-50 text-gray-600 hover:text-red-600 border border-gray-200 hover:border-red-200 rounded-lg text-sm font-semibold shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Cancelar
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Dados do agendamento --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/60 flex items-center gap-2">
            <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Informações do agendamento</h2>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">

            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Data / Horário</p>
                <p class="text-sm font-semibold text-gray-800">
                    {{ $schedule->start_at?->format('d/m/Y') ?? '—' }}
                    @if($schedule->start_at)
                        <span class="text-gray-500 font-normal">às {{ $schedule->start_at->format('H:i') }}</span>
                    @endif
                </p>
            </div>

            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Tipo</p>
                <p class="text-sm font-semibold text-gray-800">{{ $schedule->getKindLabel() }}</p>
            </div>

            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Técnico responsável</p>
                <p class="text-sm font-semibold text-gray-800">{{ $schedule->agent?->name ?? '—' }}</p>
            </div>

            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Cliente</p>
                <p class="text-sm font-semibold text-gray-800">{{ $schedule->customer?->trade_name ?? '—' }}</p>
            </div>

            @if($schedule->module)
                <div>
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Módulo</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $schedule->module->name }}</p>
                </div>
            @endif

            @if($schedule->ticket)
                <div>
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Chamado de origem</p>
                    <a href="{{ route('agent.ticket.show', $schedule->ticket) }}"
                       class="text-sm font-semibold text-blue-600 hover:underline">
                        #{{ $schedule->ticket->id }} — {{ $schedule->ticket->subject }}
                    </a>
                </div>
            @endif

            @if($schedule->contact)
                <div>
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Contato</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $schedule->contact }}</p>
                </div>
            @endif

            @if($schedule->needsAdminConfirmation())
                <div class="sm:col-span-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 border border-amber-200 rounded-lg text-xs font-semibold text-amber-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Aguardando confirmação do administrador
                    </span>
                </div>
            @endif

            @if($canShowFinalizeAction && $finalizeBlockedReason)
                <div class="sm:col-span-2">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                        {{ $finalizeBlockedReason }}
                    </div>
                </div>
            @endif

            @if($schedule->obs)
                <div class="sm:col-span-2">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Observação</p>
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ $schedule->obs }}</p>
                </div>
            @endif

        </div>
    </div>

    {{-- RATs associados --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/60 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Registros de atendimento (RAT)</h2>
                @if($schedule->records->isNotEmpty())
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-orange-100 text-orange-600 text-[11px] font-bold">
                        {{ $activeRecordCount }}
                    </span>
                @endif
            </div>
            @if(!$schedule->isFinalized())
            <a href="{{ route('agent.record.create', $schedule) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Novo RAT
            </a>
            @endif
        </div>

        @if($schedule->records->isEmpty())
            <div class="py-14 flex flex-col items-center gap-3">
                <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-600">Nenhum RAT registrado</p>
                <p class="text-xs text-gray-400">Clique em "Novo RAT" para registrar o atendimento técnico.</p>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($schedule->records as $record)
                    @php
                        $duration = $record->total_minutes;
                        $hours    = intdiv($duration, 60);
                        $minutes  = $duration % 60;
                    @endphp
                    <div class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50/60 transition-colors group">

                        {{-- Ícone de status --}}
                        <div @class([
                            'flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center mt-0.5',
                            'bg-emerald-100' => $record->resolved,
                            'bg-gray-100'    => !$record->resolved,
                        ])>
                            @if($record->resolved)
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="text-sm font-bold text-gray-800">
                                    {{ $record->start?->format('d/m/Y') ?? '—' }}
                                </span>
                                @if($record->start && $record->end)
                                    <span class="text-xs text-gray-500">
                                        {{ $record->start->format('H:i') }} – {{ $record->end->format('H:i') }}
                                    </span>
                                @endif
                                @if($duration > 0)
                                    <span class="text-[11px] font-semibold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                                        {{ $hours > 0 ? $hours . 'h ' : '' }}{{ $minutes }}min
                                    </span>
                                @endif
                                @if($record->resolved)
                                    <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full uppercase tracking-wide">
                                        Resolvido
                                    </span>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                @if($record->agent)
                                    <span>{{ $record->agent->name }}</span>
                                @endif
                                @if($record->module)
                                    <span class="text-gray-300">·</span>
                                    <span>{{ $record->module->name }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Ações do RAT --}}
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="{{ route('agent.record.print', [$schedule, $record]) }}"
                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                               title="Imprimir RAT" target="_blank">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                            </a>
                            <a href="{{ route('agent.record.edit', [$schedule, $record]) }}"
                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-orange-600 hover:bg-orange-50 transition-colors"
                               title="Editar RAT">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                        </div>

                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection
