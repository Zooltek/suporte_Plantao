@php
    $isImplementationView = request()->query('active') === 'schedules';
    $pageTitle = $isImplementationView ? 'Calendário de Implantação' : 'Visão Condensada';
    $pageDescription = $isImplementationView
        ? 'Agenda operacional da semana atual para os agendamentos de implantação'
        : 'Chamados e agendamentos da semana atual';
    $backRoute = $isImplementationView ? route('agent.implantacao.index') : route('agent.index');
    $backLabel = $isImplementationView ? 'Voltar para Implantação' : 'Voltar ao Dashboard';
@endphp

@extends('layouts.agent')

@section('title', $pageTitle . ' - Agente')

@section('content')

{{-- Dark Mode (Ocean) overrides para a Visão Condensada --}}
<style>
    .ocean .condensed-card   { background-color: #1e293b; border-color: #334155; }
    .ocean .condensed-thead  { background-color: #0f172a; border-color: #334155; }
    .ocean .condensed-tbody  { --tw-divide-opacity: 1; border-color: #334155; }
    .ocean .condensed-row    { color: #cbd5e1; }
    .ocean .condensed-row:hover { background-color: rgba(249,115,22,0.06); }
    .ocean .condensed-th     { color: #94a3b8; }
    .ocean .condensed-td-muted { color: #64748b; }
    .ocean .condensed-td-main  { color: #e2e8f0; }
    .ocean .condensed-toolbar  { background-color: #1e293b; border-color: #334155; }
    .ocean .condensed-tab-wrap { background-color: #0f172a; }
    .ocean .condensed-tab-active { background-color: #1e293b; color: #f1f5f9; }
    .ocean .condensed-calendar-grid { background-color: #1e293b; border-color: #334155; }
    .ocean .condensed-calendar-thead { background-color: #0f172a; border-color: #334155; color: #94a3b8; }
    .ocean .condensed-day-cell { color: #94a3b8; border-color: #334155; }
    .ocean .condensed-kpi    { background-color: #1e293b; border-color: #334155; }
    .ocean .condensed-kpi p.text-2xl { color: #f1f5f9; }
    /* Calendário: textos das células em modo escuro */
    .ocean .condensed-calendar-grid td { color: #cbd5e1; }
    .ocean .condensed-calendar-grid tr { border-color: #334155; }
    .ocean .condensed-calendar-grid tr:hover { background-color: rgba(30,41,59,0.6); }
    /* Bug 3 — unificar cor de todas as bordas internas do calendário no modo escuro */
    .ocean .condensed-calendar-grid td.border-r,
    .ocean .condensed-calendar-grid td.border-x,
    .ocean .condensed-calendar-grid th.border-x,
    .ocean .condensed-calendar-grid td.bg-gray-50,
    .ocean .condensed-calendar-grid th.bg-gray-100,
    .ocean .condensed-calendar-grid tbody tr { border-color: #334155 !important; }
    .ocean .condensed-calendar-grid td.bg-gray-50,
    .ocean .condensed-calendar-grid th.bg-gray-100 { background-color: #0f172a !important; }
    /* Bug 3 — divisores e bordas internas do calendário no modo escuro */
    .ocean .condensed-calendar-grid td.border-r,
    .ocean .condensed-calendar-grid td.border-x { border-color: #334155 !important; }
    .ocean .condensed-calendar-grid tbody tr { border-color: #334155 !important; }
    .ocean .condensed-calendar-grid td.bg-gray-50,
    .ocean .condensed-calendar-grid th.bg-gray-100 { background-color: #0f172a !important; border-color: #334155 !important; }
    .ocean .condensed-calendar-grid .divide-y > tr { border-color: #334155 !important; }
</style>

@php
    $authUser = auth('admin')->user() ?? auth()->user();
    $isAdmin = $authUser?->ticketit_admin;
    $openTicketNewTab = (bool) ($settings['open_ticket_new_tab'] ?? true);
    $queuePendingTickets = $tickets->filter(fn ($ticket) => $ticket->isQueuePending());
    $assignedTickets = $tickets->reject(fn ($ticket) => $ticket->isQueuePending());
    $pendingCount = $queuePendingTickets->count();
    $assignedCount = $assignedTickets->count();
    $refreshRate = max(5, (int) ($authUser?->refresh_rate ?? 60));
    $refreshRateMs = $refreshRate * 1000;
@endphp

<div class="space-y-6" x-data="ticketManager()" x-init="init()">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ $pageTitle }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $pageDescription }}</p>
        </div>
        <a href="{{ $backRoute }}"
           class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-orange-600 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            {{ $backLabel }}
        </a>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="condensed-kpi bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center text-amber-500 shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Fila de Pendências</p>
                <p class="text-2xl font-black text-gray-900">{{ $pendingCount }}</p>
            </div>
        </div>
        <div class="condensed-kpi bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center text-blue-500 shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Com Responsável</p>
                <p class="text-2xl font-black text-gray-900">{{ $assignedCount }}</p>
            </div>
        </div>
        <div class="condensed-kpi bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-purple-50 flex items-center justify-center text-purple-500 shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Agendamentos</p>
                <p class="text-2xl font-black text-gray-900">{{ $schedules_count }}</p>
            </div>
        </div>

        {{-- Atendimentos Hoje --}}
        <div class="condensed-kpi bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-teal-50 flex items-center justify-center text-teal-500 shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3-1-3z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Atendimentos Hoje</p>
                <p class="text-2xl font-black text-gray-900">{{ $attendances_today_count }}</p>
            </div>
        </div>
    </div>

    {{-- Tabs Navigation --}}
    <div class="condensed-tab-wrap flex p-1 gap-1 bg-gray-100 rounded-xl w-full sm:w-max print:hidden">
        <button @click="activeTab = 'pending'"
                :class="activeTab === 'pending'
                    ? 'bg-white shadow-sm text-gray-900'
                    : 'text-gray-500 hover:text-gray-700'"
                class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-lg transition-all focus:outline-none">
            <span class="w-2 h-2 rounded-full bg-amber-400" x-show="activeTab !== 'pending'"></span>
            Fila de Pendências
            <span :class="activeTab === 'pending' ? 'bg-orange-100 text-orange-600' : 'bg-gray-200 text-gray-500'"
                  class="inline-flex items-center justify-center min-w-[22px] h-5 px-1.5 text-xs font-bold rounded-full">
                {{ $pendingCount }}
            </span>
        </button>
        <button @click="activeTab = 'in-progress'"
                :class="activeTab === 'in-progress'
                    ? 'bg-white shadow-sm text-gray-900'
                    : 'text-gray-500 hover:text-gray-700'"
                class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-lg transition-all focus:outline-none">
            Com Responsável
            <span :class="activeTab === 'in-progress' ? 'bg-orange-100 text-orange-600' : 'bg-gray-200 text-gray-500'"
                  class="inline-flex items-center justify-center min-w-[22px] h-5 px-1.5 text-xs font-bold rounded-full">
                {{ $assignedCount }}
            </span>
        </button>
        <button @click="activeTab = 'schedules'"
                :class="activeTab === 'schedules'
                    ? 'bg-white shadow-sm text-gray-900'
                    : 'text-gray-500 hover:text-gray-700'"
                class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-lg transition-all focus:outline-none">
            Agendamentos
            <span :class="activeTab === 'schedules' ? 'bg-orange-100 text-orange-600' : 'bg-gray-200 text-gray-500'"
                  class="inline-flex items-center justify-center min-w-[22px] h-5 px-1.5 text-xs font-bold rounded-full">
                {{ $schedules_count }}
            </span>
        </button>
        <button @click="activeTab = 'attendances'"
                :class="activeTab === 'attendances'
                    ? 'bg-white shadow-sm text-gray-900'
                    : 'text-gray-500 hover:text-gray-700'"
                class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-lg transition-all focus:outline-none">
            Atendimentos
            <span :class="activeTab === 'attendances' ? 'bg-teal-100 text-teal-700' : 'bg-gray-200 text-gray-500'"
                  class="inline-flex items-center justify-center min-w-[22px] h-5 px-1.5 text-xs font-bold rounded-full">
                {{ $attendances_today_count }}
            </span>
        </button>
    </div>

    {{-- Tab Content --}}
    <div class="tab-content relative">

        {{-- Aba: Fila de Pendências --}}
        <div x-show="activeTab === 'pending'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="condensed-card overflow-hidden bg-white shadow-sm rounded-2xl border border-gray-200">

            @if($pendingCount > 0)
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $pendingCount }} chamado(s) aguardando atribuição</p>
                <a href="{{ route('agent.ticket.create') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-orange-600 hover:text-orange-700 transition-colors">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Novo ticket
                </a>
            </div>
            @endif

            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="condensed-thead bg-gray-50">
                    <tr>
                        <th class="condensed-th px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Nº</th>
                        <th class="condensed-th px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Data</th>
                        <th class="condensed-th px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Cliente</th>
                        @if($settings['show_ticket_category'] ?? false)
                            <th class="condensed-th px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Categoria</th>
                        @endif
                        <th class="condensed-th px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Contato</th>
                        <th class="condensed-th px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Obs</th>
                        <th class="condensed-th px-3 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400 w-10">Ori.</th>
                        <th class="condensed-th px-3 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400 w-28">Status</th>
                        @if($isAdmin)
                            <th class="px-3 py-3 w-10"></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="condensed-tbody divide-y divide-gray-50">
                    @forelse($queuePendingTickets as $ticket)
                        @php $rawColor = $ticket->status?->color ?? '#f97316'; @endphp
                        <tr class="condensed-row group hover:bg-orange-50/40 transition-colors duration-150 relative">
                            <td class="px-4 py-3 font-bold relative">
                                <div class="absolute left-0 top-0 bottom-0 w-1 rounded-r-full" style="background-color: {{ $rawColor }}"></div>
                                <a href="{{ route('agent.ticket.show', $ticket->id) }}"
                                   @if($openTicketNewTab) target="_blank" rel="noopener noreferrer" @endif
                                   class="text-orange-600 hover:text-orange-800 font-extrabold hover:underline focus:outline-none focus:ring-2 focus:ring-orange-300 rounded">
                                    #{{ $ticket->id }}
                                </a>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-400 text-xs font-medium tabular-nums">
                                {{ $ticket->created_at->format('d/m H:i') }}
                            </td>
                            <td class="px-4 py-3 font-semibold text-gray-800">{{ $ticket->company_name }}</td>
                            @if($settings['show_ticket_category'] ?? false)
                                <td class="px-4 py-3 text-gray-400 text-xs">{{ $ticket->category_name }}</td>
                            @endif
                            <td class="px-4 py-3">
                                <span class="block truncate w-28 text-gray-600 text-sm" title="{{ $ticket->contact }}">
                                    {{ $ticket->contact }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-400 italic truncate max-w-[160px]" title="{{ $ticket->obs }}">
                                {{ $ticket->obs ?: '—' }}
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($ticket->origin && $ticket->origin->icon)
                                    <img width="16" src="{{ asset($ticket->origin->icon) }}"
                                         class="inline opacity-70 group-hover:opacity-100 transition-opacity"
                                         alt="" title="{{ $ticket->origin->name }}">
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <a href="{{ route('agent.ticket.show', $ticket->id) }}"
                                   @if($openTicketNewTab) target="_blank" rel="noopener noreferrer" @endif
                                   class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 rounded-lg text-xs font-semibold text-gray-700 hover:bg-orange-50 hover:text-orange-700 border border-gray-200 hover:border-orange-200 transition-all shadow-xs mx-auto active:scale-95">
                                    @if($ticket->sub_category_id == 76 && $ticket->status_id != 3)
                                        <span class="text-rose-600 font-bold">Retorno</span>
                                    @else
                                        @if($ticket->status?->icon)
                                            <img width="13" src="{{ asset($ticket->status->icon) }}" class="opacity-80" alt="">
                                        @endif
                                        <span>{{ $ticket->status?->name ?? 'Novo' }}</span>
                                    @endif
                                </a>
                            </td>
                            @if($isAdmin)
                            <td class="px-3 py-3 text-center">
                                <button type="button"
                                        class="w-7 h-7 inline-flex items-center justify-center rounded-full text-gray-300 hover:text-red-500 hover:bg-red-50 transition-colors mx-auto"
                                        @click="confirmDelete('{{ $ticket->id }}')">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($settings['show_ticket_category'] ?? false) ? 10 : 9 }}" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 bg-emerald-50 rounded-full flex items-center justify-center">
                                        <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-700 font-semibold">Tudo em dia!</p>
                                    <p class="text-gray-400 text-sm">Nenhum chamado aguardando atribuição no momento.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Aba: Com Responsável --}}
        <div x-show="activeTab === 'in-progress'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="condensed-card overflow-hidden bg-white shadow-sm rounded-2xl border border-gray-200">

            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="condensed-thead bg-gray-50">
                    <tr>
                        <th class="condensed-th px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Nº</th>
                        <th class="condensed-th px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Início/Fim</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Cliente</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Responsável</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Contato</th>
                        <th class="px-3 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400">Status</th>
                        @if($isAdmin)
                            <th class="px-3 py-3 w-10"></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($assignedTickets as $ticket)
                        @php $rawColor = $ticket->status?->color ?? '#3b82f6'; @endphp
                        <tr class="condensed-row group hover:bg-blue-50/30 transition-colors duration-150 relative">
                            <td class="px-4 py-3 font-bold relative">
                                <div class="absolute left-0 top-0 bottom-0 w-1 rounded-r-full" style="background-color: {{ $rawColor }}"></div>
                                <a href="{{ route('agent.ticket.show', $ticket->id) }}"
                                   @if($openTicketNewTab) target="_blank" rel="noopener noreferrer" @endif
                                   class="text-blue-600 hover:text-blue-800 font-extrabold hover:underline focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">
                                    #{{ $ticket->id }}
                                </a>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-400 text-xs tabular-nums">
                                {{ $ticket->created_at->format('H:i') }}
                                <span class="text-gray-300 mx-0.5">→</span>
                                {{ $ticket->completed_at?->format('H:i') ?? '...' }}
                            </td>
                            <td class="px-4 py-3 font-semibold text-gray-800">{{ $ticket->company_name }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                @if($isAdmin)
                                    <button @click="filter('{{ $ticket->agent_id }}')"
                                            class="hover:text-orange-600 font-medium hover:underline transition-colors">
                                        {{ $ticket->agent->name ?? 'Sem responsável' }}
                                    </button>
                                @else
                                    {{ $ticket->agent->name ?? 'Sem responsável' }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                <span class="block truncate w-32" title="{{ $ticket->contact }}">
                                    {{ $ticket->contact ?: '—' }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($ticket->status?->icon)
                                    <img width="18" src="{{ asset($ticket->status->icon) }}"
                                         class="inline opacity-80 group-hover:opacity-100 transition-opacity mx-auto"
                                         alt="" title="{{ $ticket->status->name }}">
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                        {{ $ticket->status?->name ?? 'Em análise' }}
                                    </span>
                                @endif
                            </td>
                            @if($isAdmin)
                            <td class="px-3 py-3 text-center">
                                <button type="button"
                                        class="w-7 h-7 inline-flex items-center justify-center rounded-full text-gray-300 hover:text-red-500 hover:bg-red-50 transition-colors mx-auto"
                                        @click="confirmDelete('{{ $ticket->id }}')">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isAdmin ? 7 : 6 }}" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center">
                                        <svg class="w-7 h-7 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-700 font-semibold">Nenhum chamado em andamento</p>
                                    <p class="text-gray-400 text-sm">Os chamados ativos aparecerão aqui.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Aba: Agendamentos --}}
        <div x-show="activeTab === 'schedules'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="space-y-4">

            {{-- Toolbar --}}
            <div class="condensed-toolbar flex flex-col sm:flex-row justify-between items-center gap-4 px-4 py-3 bg-white rounded-2xl shadow-sm border border-gray-200">
                <div class="flex gap-2">
                    <a href="{{ route('agent.schedules.create') }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-xs font-bold transition shadow-sm">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        Novo Agendamento
                    </a>
                    <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-bold transition border border-gray-200"
                            @click="toggleSchedules()">
                        Mostrar Tudo
                    </button>
                    <button class="inline-flex items-center gap-1.5 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-xs font-bold transition shadow-sm"
                            @click="syncRecords()">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Sincronizar
                    </button>
                </div>

                {{-- Calendar Navigator --}}
                <div class="flex items-center bg-gray-100 dark:bg-slate-800 rounded-xl p-1 border border-gray-200 dark:border-slate-700 gap-0.5">
                    <button class="p-2 hover:bg-white dark:hover:bg-slate-700 text-gray-500 dark:text-slate-300 hover:text-gray-800 dark:hover:text-white rounded-lg shadow-sm transition"
                            @click="calendarPrevious()">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <button class="px-4 py-1.5 text-xs font-bold text-gray-700 dark:text-slate-200 uppercase tracking-widest hover:text-orange-500 dark:hover:text-orange-400 transition"
                            @click="calendarToday()">
                        Hoje
                    </button>
                    <button class="p-2 hover:bg-white dark:hover:bg-slate-700 text-gray-500 dark:text-slate-300 hover:text-gray-800 dark:hover:text-white rounded-lg shadow-sm transition"
                            @click="calendarNext()">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Schedule Grid --}}
            <div class="condensed-calendar-grid overflow-x-auto bg-white shadow-sm rounded-2xl border border-gray-200">
                <table class="min-w-full text-xs border-collapse">
                    <thead class="condensed-calendar-thead bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="p-3 w-20 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400">Dia</th>
                            <th colspan="4" class="p-3 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400">
                                <div class="flex items-center justify-center gap-1.5">
                                    <svg class="h-3 w-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="10"/></svg>
                                    Manhã
                                </div>
                            </th>
                            <th class="w-3 bg-gray-100 border-x border-gray-200"></th>
                            <th colspan="4" class="p-3 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400">
                                <div class="flex items-center justify-center gap-1.5">
                                    <svg class="h-3 w-3 text-orange-400" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="10"/></svg>
                                    Tarde
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @if(empty($schedules_data))
                            <tr>
                                <td colspan="10" class="py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-14 h-14 bg-purple-50 rounded-full flex items-center justify-center">
                                            <svg class="w-7 h-7 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <p class="text-gray-700 font-semibold">Semana livre</p>
                                        <p class="text-gray-400 text-sm">Nenhum agendamento encontrado para esta semana.</p>
                                    </div>
                                </td>
                            </tr>
                        @endif

                        @foreach($schedules_data as $row)
                            <tr @class(['bg-orange-50/30' => $row['today'], 'bg-white dark:bg-slate-800' => !$row['today']])>
                                <td @class([
                                    'condensed-day-cell p-3 text-center align-middle border-r border-gray-200 font-black',
                                    'text-orange-600' => $row['today'],
                                    'text-gray-700 dark:text-slate-400' => !$row['today'],
                                ]) rowspan="{{ $row['max'] + 1 }}">
                                    <div class="flex flex-col items-center justify-center">
                                        @if($row['today'])
                                            <span class="text-[9px] font-bold uppercase tracking-wider text-orange-400 mb-0.5">Hoje</span>
                                        @endif
                                        <span class="text-2xl font-black leading-none">{{ explode(' - ', $row['day'])[0] }}</span>
                                        <span class="text-[10px] font-bold uppercase tracking-wide opacity-70 mt-1">{{ $row['day_week'] }}</span>
                                        @if($row['today'])
                                            <span class="w-1.5 h-1.5 bg-orange-400 rounded-full mt-1.5"></span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @for($j = 0; $j < $row['max']; $j++)
                                <tr @class(['hover:bg-gray-50/60 transition-colors', 'bg-orange-50/10' => $row['today']])>
                                    {{-- Morning --}}
                                    @php $sm = $row['morning'][$j] ?? null; @endphp
                                    <td class="p-1.5 w-14 border-r border-gray-100">
                                        @if($sm)
                                            <a href="{{ route('agent.schedules.show', $sm) }}"
                                               class="block w-full py-1.5 px-1 rounded-lg font-bold transition text-center shadow-sm text-xs status-{{ $sm->status }}">
                                                {{ $sm->start_at->format('H:i') }}
                                            </a>
                                        @endif
                                    </td>
                                    <td colspan="3" class="px-3 py-2 text-gray-700 max-w-[220px]">
                                        @if($sm)
                                            <div class="min-w-0">
                                                <a href="{{ route('agent.schedules.show', $sm) }}"
                                                   class="truncate font-medium hover:text-blue-600 transition-colors block"
                                                   title="{{ $sm->display_title }}">
                                                    {{ $sm->display_title }}
                                                </a>
                                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600">
                                                        {{ $sm->getKindLabel() }}
                                                    </span>
                                                    @if($sm->needsAdminConfirmation())
                                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800">
                                                            Aguardando admin
                                                        </span>
                                                        @if($isAdmin)
                                                            <form method="POST" action="{{ route('agent.schedules.confirm', $sm) }}" class="inline-flex">
                                                                @csrf
                                                                <button type="submit" class="inline-flex items-center rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white hover:bg-emerald-700">
                                                                    Confirmar
                                                                </button>
                                                            </form>
                                                        @endif
                                                    @endif
                                                </div>
                                                {{-- Ações --}}
                                                <div class="mt-1.5 flex items-center gap-1">
                                                    <a href="{{ route('agent.schedules.show', $sm) }}"
                                                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors"
                                                       title="Visualizar agendamento">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                        </svg>
                                                        Ver
                                                    </a>
                                                    <a href="{{ route('agent.schedules.edit', $sm) }}"
                                                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors"
                                                       title="Editar agendamento">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                        </svg>
                                                        Editar
                                                    </a>
                                                    @if($isAdmin)
                                                    <form method="POST" action="{{ route('agent.schedules.destroy', $sm) }}"
                                                          onsubmit="return confirm('Excluir o agendamento de {{ addslashes($sm->display_title) }}? Esta ação não pode ser desfeita.')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit"
                                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold text-red-600 bg-red-50 hover:bg-red-100 transition-colors"
                                                                title="Excluir agendamento">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                            Excluir
                                                        </button>
                                                    </form>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Divider --}}
                                    <td class="w-3 bg-gray-50 border-x border-gray-100"></td>

                                    {{-- Afternoon --}}
                                    @php $sa = $row['afternoon'][$j] ?? null; @endphp
                                    <td class="p-1.5 w-14 border-r border-gray-100">
                                        @if($sa)
                                            <a href="{{ route('agent.schedules.show', $sa) }}"
                                               class="block w-full py-1.5 px-1 rounded-lg font-bold transition text-center shadow-sm text-xs status-{{ $sa->status }}">
                                                {{ $sa->start_at->format('H:i') }}
                                            </a>
                                        @endif
                                    </td>
                                    <td colspan="3" class="px-3 py-2 text-gray-700 max-w-[220px]">
                                        @if($sa)
                                            <div class="min-w-0">
                                                <a href="{{ route('agent.schedules.show', $sa) }}"
                                                   class="truncate font-medium hover:text-blue-600 transition-colors block"
                                                   title="{{ $sa->display_title }}">
                                                    {{ $sa->display_title }}
                                                </a>
                                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600">
                                                        {{ $sa->getKindLabel() }}
                                                    </span>
                                                    @if($sa->needsAdminConfirmation())
                                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800">
                                                            Aguardando admin
                                                        </span>
                                                        @if($isAdmin)
                                                            <form method="POST" action="{{ route('agent.schedules.confirm', $sa) }}" class="inline-flex">
                                                                @csrf
                                                                <button type="submit" class="inline-flex items-center rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white hover:bg-emerald-700">
                                                                    Confirmar
                                                                </button>
                                                            </form>
                                                        @endif
                                                    @endif
                                                </div>
                                                {{-- Ações --}}
                                                <div class="mt-1.5 flex items-center gap-1">
                                                    <a href="{{ route('agent.schedules.show', $sa) }}"
                                                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors"
                                                       title="Visualizar agendamento">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                        </svg>
                                                        Ver
                                                    </a>
                                                    <a href="{{ route('agent.schedules.edit', $sa) }}"
                                                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors"
                                                       title="Editar agendamento">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                        </svg>
                                                        Editar
                                                    </a>
                                                    @if($isAdmin)
                                                    <form method="POST" action="{{ route('agent.schedules.destroy', $sa) }}"
                                                          onsubmit="return confirm('Excluir o agendamento de {{ addslashes($sa->display_title) }}? Esta ação não pode ser desfeita.')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit"
                                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold text-red-600 bg-red-50 hover:bg-red-100 transition-colors"
                                                                title="Excluir agendamento">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                            Excluir
                                                        </button>
                                                    </form>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endfor
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Aba: Atendimentos --}}
        <div x-show="activeTab === 'attendances'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="overflow-hidden bg-white shadow-sm rounded-2xl border border-gray-200">

            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-teal-400"></span>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Histórico — últimos 7 dias
                        @if($attendances->count() > 0)
                            <span class="ml-1 text-teal-600 font-bold">({{ $attendances->count() }})</span>
                        @endif
                    </p>
                </div>
            </div>

            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 w-14">Chamado</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 w-28">Data/Hora</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Cliente</th>
                        @if($isAdmin)
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Técnico</th>
                        @endif
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Observação</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400 w-24">Retorno</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 w-40">Agendado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($attendances as $att)
                        <tr class="group hover:bg-teal-50/30 transition-colors duration-150 relative">
                            <td class="px-4 py-3 font-bold relative">
                                <div class="absolute left-0 top-0 bottom-0 w-1 rounded-r-full" style="background-color: #14b8a6"></div>
                                <a href="{{ route('agent.ticket.show', $att->ticket_id) }}"
                                   @if($openTicketNewTab) target="_blank" rel="noopener noreferrer" @endif
                                   class="text-teal-600 hover:text-teal-800 font-extrabold hover:underline">
                                    #{{ $att->ticket_id }}
                                </a>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-400 text-xs font-medium tabular-nums">
                                {{ $att->created_at->format('d/m H:i') }}
                            </td>
                            <td class="px-4 py-3 font-semibold text-gray-800">
                                {{ $att->ticket?->company?->trade_name ?? '—' }}
                            </td>
                            @if($isAdmin)
                                <td class="px-4 py-3 text-gray-600 text-sm">
                                    {{ $att->user?->name ?? '—' }}
                                </td>
                            @endif
                            <td class="px-4 py-3 text-xs text-gray-400 italic truncate max-w-[200px]"
                                title="{{ $att->notes }}">
                                {{ $att->notes ? \Illuminate\Support\Str::limit($att->notes, 60) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($att->return_zap || $att->return_tel || $att->return_cel)
                                    <div class="inline-flex items-center gap-1 justify-center">
                                        @if($att->return_zap)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700" title="WhatsApp">ZAP</span>
                                        @endif
                                        @if($att->return_tel)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700" title="Telefone">TEL</span>
                                        @endif
                                        @if($att->return_cel)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-700" title="Celular">CEL</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                @if($att->return_scheduled_at || $att->returned_at)
                                    <div class="flex flex-col gap-0.5">
                                        @if($att->return_scheduled_at)
                                            <span class="font-medium text-gray-700">{{ $att->return_scheduled_at->format('d/m H:i') }}</span>
                                        @endif
                                        @if($att->return_scheduled_at && $att->returnAssignee)
                                            <span class="text-gray-400 truncate max-w-[130px]" title="{{ $att->returnAssignee->name }}">
                                                {{ $att->returnAssignee->name }}
                                            </span>
                                        @endif
                                        @if($att->returned_at)
                                            <span class="inline-flex items-center gap-0.5 text-emerald-600 font-semibold">
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                Retorno realizado
                                            </span>
                                            <span class="text-gray-400">{{ $att->returned_at->format('d/m H:i') }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isAdmin ? 7 : 6 }}" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 bg-teal-50 rounded-full flex items-center justify-center">
                                        <svg class="w-7 h-7 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3-1-3z"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-700 font-semibold">Nenhum atendimento</p>
                                    <p class="text-gray-400 text-sm">Nenhum atendimento registrado nos últimos 7 dias.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>{{-- /tab-content --}}
</div>

<script>
    function ticketManager() {
        const urlParams = new URLSearchParams(window.location.search);
        const initialTab = urlParams.get('active') || localStorage.getItem('condensed_active_tab') || '{{ $active ?? "pending" }}';

        return {
            activeTab: initialTab,
            refreshTimer: null,

            init() {
                this.updateGlobalCounters();
                this.initAutoRefresh();
                this.$watch('activeTab', (val) => {
                    localStorage.setItem('condensed_active_tab', val);
                });
            },

            initAutoRefresh() {
                const intervalMs = {{ $refreshRateMs }};
                if (intervalMs >= 5000) {
                    this.refreshTimer = setInterval(() => {
                        this.syncRecords();
                    }, intervalMs);
                }
            },

            updateGlobalCounters() {
                const map = {
                    'new_count':          {{ $pendingCount }},
                    'pending_count':      {{ $assignedCount }},
                    'schedules_count':    {{ $schedules_count }},
                    'attendances_count':  {{ $attendances_today_count }},
                };
                Object.entries(map).forEach(([id, val]) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = val;
                });
            },

            confirmDelete(id)  { window.confirmDelete   ? window.confirmDelete(id)  : console.log('confirmDelete:', id); },
            openSchedule(id)   { window.openSchedule   ? window.openSchedule(id)   : console.log('openSchedule:', id); },
            toggleSchedules() {
                const url = new URL(window.location.href);
                url.searchParams.set('agent_id', '0');
                window.location.href = url.toString();
            },
            syncRecords() {
                window.location.reload();
            },
            calendarPrevious() {
                const url = new URL(window.location.href);
                url.searchParams.set('start', '{{ $start->copy()->subWeek()->format("Y-m-d") }}');
                url.searchParams.set('active', 'schedules');
                window.location.href = url.toString();
            },
            calendarNext() {
                const url = new URL(window.location.href);
                url.searchParams.set('start', '{{ $start->copy()->addWeek()->format("Y-m-d") }}');
                url.searchParams.set('active', 'schedules');
                window.location.href = url.toString();
            },
            calendarToday() {
                const url = new URL(window.location.href);
                url.searchParams.set('start', '{{ now()->format("Y-m-d") }}');
                url.searchParams.set('active', 'schedules');
                window.location.href = url.toString();
            },
            filter(agentId) {
                const url = new URL(window.location.href);
                url.searchParams.set('agent_id', agentId);
                window.location.href = url.toString();
            },
        }
    }
</script>

<style>
    .status-pen {
        background-color: #fef9c3; color: #854d0e; border: 1px solid #fde68a;
    }
    .status-pen:hover { background-color: #fde68a; }

    .status-sch, .status-1 {
        background-color: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe;
    }
    .status-sch:hover, .status-1:hover { background-color: #bfdbfe; }

    .status-con, .status-2 {
        background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;
    }
    .status-con:hover, .status-2:hover { background-color: #bbf7d0; }

    .status-fin, .status-3 {
        background-color: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb;
    }

    .status-can, .status-4 {
        background-color: #f3f4f6; color: #9ca3af; border: 1px solid #e5e7eb;
        opacity: 0.65; pointer-events: none;
    }
</style>

@endsection
