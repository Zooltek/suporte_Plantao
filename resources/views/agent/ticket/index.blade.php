@extends('layouts.agent')

@section('title', 'Chamados - Helpdesk')

@section('content')

{{-- ═══════════════════════════════════════════════════════════════════
     Ticket Index — Listagem de chamados do agente/admin
     Alpine component: ticketIndex (resources/js/agent/tickets/index.js)
     ═══════════════════════════════════════════════════════════════════ --}}

@php
    $authUser = auth('admin')->user() ?? auth()->user();
    $refreshRate = max(5, (int) ($authUser?->refresh_rate ?? 60));
    $refreshRateMs = $refreshRate * 1000;
    $hasActiveFilters = request()->anyFilled(['q', 'status', 'category', 'company', 'agent', 'origin', 'department', 'date_from', 'date_to']);
    $isMineView = (bool) ($isMineView ?? request()->boolean('mine'));
    $isUnassignedView = (bool) ($isUnassignedView ?? (request()->boolean('unassigned') || request('agent') === 'unassigned' || request('agent') === '0'));
    $isAdmin = (bool) ($isAdmin ?? ($authUser?->isAdmin() ?? false));
    $currentAgentName = (string) ($currentAgentName ?? ($authUser?->name ?? 'Você'));
    $dateFrom = (string) ($dateFrom ?? request('date_from', ''));
    $dateTo = (string) ($dateTo ?? request('date_to', ''));
    $todayStr = now()->toDateString();
    $sevenDaysAgoStr = now()->subDays(7)->toDateString();
    $firstDayOfMonthStr = now()->startOfMonth()->toDateString();
    $isTodayActive = ($dateFrom === $todayStr && $dateTo === $todayStr);
    $isSevenDaysActive = ($dateFrom === $sevenDaysAgoStr && $dateTo === $todayStr);
    $isMonthActive = ($dateFrom === $firstDayOfMonthStr && $dateTo === $todayStr);
    $statuses = $statuses ?? collect();
    $categories = $categories ?? collect();
    $companies = $companies ?? collect();
    $agents = $agents ?? collect();
    $origins = $origins ?? collect();
    $departments = $departments ?? collect();
    $clearFiltersRoute = route('agent.ticket.index', $isUnassignedView ? ['unassigned' => 1] : ($isMineView ? ['mine' => 1] : []));
    $openTicketNewTab = (bool) ($settings['open_ticket_new_tab'] ?? true);
    $pageHeading = match (true) {
        $isUnassignedView => 'Chamados Sem Agente (Fila)',
        $isMineView => 'Meus Chamados (Capturados)',
        default => 'Todos os Chamados',
    };
@endphp

<div x-data="ticketIndex({{ $refreshRateMs }})" class="space-y-6">

    {{-- ── Header ────────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">
                {{ $pageHeading }}
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">
                <span class="font-bold text-indigo-600">{{ $tickets->total() }}</span>
                resultado(s) encontrado(s)
            </p>
        </div>
        <a href="{{ route('agent.ticket.create') }}"
           @if($openTicketNewTab) target="_blank" rel="noopener noreferrer" @endif
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700
                  text-white rounded-xl font-bold text-sm shadow-md shadow-indigo-500/30
                  transition-all active:scale-95 whitespace-nowrap">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Chamado
        </a>
    </div>

    {{-- ── Quick Navigation Tabs: Meus Chamados | Sem Agente | Todos os Chamados ── --}}
    <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 dark:border-slate-700 pb-3">
        <a href="{{ route('agent.ticket.index', ['mine' => 1]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all {{ $isMineView && ! $isUnassignedView ? 'bg-indigo-50 text-indigo-700 shadow-sm border border-indigo-100 dark:bg-indigo-950/70 dark:text-indigo-300 dark:border-indigo-800' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-slate-400 dark:hover:text-slate-100 dark:hover:bg-slate-800 border border-transparent' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Meus Chamados
        </a>
        <a href="{{ route('agent.ticket.index', ['unassigned' => 1]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all {{ $isUnassignedView ? 'bg-amber-50 text-amber-800 shadow-sm border border-amber-200 dark:bg-amber-950/70 dark:text-amber-300 dark:border-amber-800' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-slate-400 dark:hover:text-slate-100 dark:hover:bg-slate-800 border border-transparent' }}">
            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            Sem Agente (Fila)
        </a>
        @if($isAdmin)
        <a href="{{ route('agent.ticket.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all {{ ! $isMineView && ! $isUnassignedView ? 'bg-indigo-50 text-indigo-700 shadow-sm border border-indigo-100 dark:bg-indigo-950/70 dark:text-indigo-300 dark:border-indigo-800' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-slate-400 dark:hover:text-slate-100 dark:hover:bg-slate-800 border border-transparent' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Todos os Chamados
        </a>
        @endif
    </div>

    {{-- ── Grid principal: Filtros + Lista ──────────────────────────────── --}}
    <div class="flex flex-col lg:flex-row gap-6 items-start">

        {{-- ── Sidebar de filtros ─────────────────────────────────────────── --}}
        <aside class="w-full lg:w-64 flex-shrink-0">
            <div class="rounded-2xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm overflow-hidden lg:sticky lg:top-6">

                {{-- Header do sidebar --}}
                <div class="px-5 py-4 bg-gray-50 dark:bg-slate-900/60 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-xs font-black text-gray-700 dark:text-slate-200 uppercase tracking-widest">Filtros</h3>
                    <button @click="toggleFilters()"
                            class="lg:hidden p-1.5 rounded-lg text-gray-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-slate-700 transition-colors"
                            :aria-expanded="filtersOpen">
                        <svg class="w-4 h-4 transition-transform duration-200" :class="filtersOpen ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                </div>

                {{-- Formulário --}}
                <div x-show="filtersOpen" x-collapse>
                    <form method="GET" action="{{ route('agent.ticket.index') }}" class="p-5 space-y-4">
                        @if($isUnassignedView)
                            <input type="hidden" name="unassigned" value="1">
                        @elseif($isMineView)
                            <input type="hidden" name="mine" value="1">
                        @endif

                        {{-- Busca por keyword --}}
                        <div>
                            <label for="q" class="block text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">
                                Palavra-chave
                            </label>
                            <div class="relative">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="text" id="q" name="q" value="{{ request('q') }}"
                                       placeholder="Buscar chamado..."
                                       class="w-full pl-9 pr-3 py-2 text-sm bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100
                                              rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            </div>
                        </div>

                        {{-- Período (Criação) --}}
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">
                                Período (Criação)
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label for="filter-date-from" class="block text-[9px] font-semibold text-gray-400 dark:text-slate-500 mb-1">De</label>
                                    <input type="date" id="filter-date-from" name="date_from" value="{{ $dateFrom }}"
                                           class="w-full text-xs bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 rounded-xl py-2 px-2.5
                                                  outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                                </div>
                                <div>
                                    <label for="filter-date-to" class="block text-[9px] font-semibold text-gray-400 dark:text-slate-500 mb-1">Até</label>
                                    <input type="date" id="filter-date-to" name="date_to" value="{{ $dateTo }}"
                                           class="w-full text-xs bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 rounded-xl py-2 px-2.5
                                                  outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-1 mt-2">
                                <button type="button" @click="setPeriod('today')"
                                        class="px-2 py-0.5 text-[10px] font-semibold rounded-lg transition-colors {{ $isTodayActive ? 'bg-indigo-600 text-white shadow-xs' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/70 hover:text-indigo-600 dark:hover:text-indigo-300' }}">
                                    Hoje
                                </button>
                                <button type="button" @click="setPeriod('7days')"
                                        class="px-2 py-0.5 text-[10px] font-semibold rounded-lg transition-colors {{ $isSevenDaysActive ? 'bg-indigo-600 text-white shadow-xs' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/70 hover:text-indigo-600 dark:hover:text-indigo-300' }}">
                                    7 dias
                                </button>
                                <button type="button" @click="setPeriod('month')"
                                        class="px-2 py-0.5 text-[10px] font-semibold rounded-lg transition-colors {{ $isMonthActive ? 'bg-indigo-600 text-white shadow-xs' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/70 hover:text-indigo-600 dark:hover:text-indigo-300' }}">
                                    Este Mês
                                </button>
                                <button type="button" @click="setPeriod('clear')"
                                        class="px-2 py-0.5 text-[10px] font-semibold rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-400 dark:text-slate-400 hover:bg-red-50 dark:hover:bg-red-950/70 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                                    Limpar
                                </button>
                            </div>
                        </div>

                        {{-- Status --}}
                        <div>
                            <label for="filter-status" class="block text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">
                                Status
                            </label>
                            <select id="filter-status" name="status"
                                    class="w-full text-sm bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 rounded-xl py-2 px-3
                                           outline-none focus:ring-2 focus:ring-indigo-500 appearance-none cursor-pointer">
                                <option value="">Todos</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status->id }}" @selected(request('status') == $status->id)>
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Categoria --}}
                        <div>
                            <label for="filter-category" class="block text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">
                                Categoria
                            </label>
                            <select id="filter-category" name="category"
                                    class="w-full text-sm bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 rounded-xl py-2 px-3
                                           outline-none focus:ring-2 focus:ring-indigo-500 appearance-none cursor-pointer">
                                <option value="">Todas</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->category_id }}" @selected(request('category') == $cat->category_id)>
                                        {{ $cat->display_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Empresa (agente/admin) --}}
                        @if($companies->isNotEmpty())
                        <div>
                            <label for="filter-company" class="block text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">
                                Empresa
                            </label>
                            <select id="filter-company" name="company"
                                    class="w-full text-sm bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 rounded-xl py-2 px-3
                                           outline-none focus:ring-2 focus:ring-indigo-500 appearance-none cursor-pointer">
                                <option value="">Todas</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" @selected(request('company') == $company->id)>
                                        {{ $company->trade_name ?: $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- Agente --}}
                        @if($isUnassignedView)
                        <div>
                            <label class="block text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-widest mb-1.5">
                                Responsável
                            </label>
                            <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/40 px-3 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm font-semibold text-amber-900 dark:text-amber-200">Sem Agente</span>
                                    <span class="inline-flex items-center rounded-full border border-amber-200 dark:border-amber-800 bg-white dark:bg-slate-800 px-2 py-0.5 text-[10px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-300">
                                        Fila
                                    </span>
                                </div>
                                <p class="mt-2 text-[11px] text-amber-700 dark:text-amber-300">
                                    Exibindo apenas chamados pendentes aguardando captura por um atendente.
                                </p>
                            </div>
                        </div>
                        @elseif($isMineView)
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">
                                Agente
                            </label>
                            <div class="rounded-xl border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/40 px-3 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm font-semibold text-indigo-900 dark:text-indigo-200">{{ $currentAgentName }}</span>
                                    <span class="inline-flex items-center rounded-full border border-indigo-200 dark:border-indigo-800 bg-white dark:bg-slate-800 px-2 py-0.5 text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-300">
                                        Você
                                    </span>
                                </div>
                                <p class="mt-2 text-[11px] text-indigo-700 dark:text-indigo-300">
                                    Em Meus Chamados são exibidos apenas os chamados capturados por você.
                                </p>
                            </div>
                        </div>
                        @elseif($agents->isNotEmpty())
                        <div>
                            <label for="filter-agent" class="block text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">
                                Agente
                            </label>
                            <select id="filter-agent" name="agent"
                                    class="w-full text-sm bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 rounded-xl py-2 px-3
                                           outline-none focus:ring-2 focus:ring-indigo-500 appearance-none cursor-pointer">
                                <option value="">Todos</option>
                                <option value="unassigned" @selected(request('agent') === 'unassigned' || request('agent') === '0')>
                                    Sem Agente (Fila de Pendências)
                                </option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" @selected(request('agent') == $agent->id)>
                                        {{ $agent->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- Origem --}}
                        @if(($origins ?? collect())->isNotEmpty())
                        <div>
                            <label for="filter-origin" class="block text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">
                                Origem
                            </label>
                            <select id="filter-origin" name="origin"
                                    class="w-full text-sm bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 rounded-xl py-2 px-3
                                           outline-none focus:ring-2 focus:ring-indigo-500 appearance-none cursor-pointer">
                                <option value="">Todas</option>
                                @foreach($origins as $origin)
                                    <option value="{{ $origin->id }}" @selected(request('origin') == $origin->id)>
                                        {{ $origin->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- Departamento (Admin) --}}
                        @if(($departments ?? collect())->isNotEmpty())
                        <div>
                            <label for="filter-department" class="block text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">
                                Departamento
                            </label>
                            <select id="filter-department" name="department"
                                    class="w-full text-sm bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 rounded-xl py-2 px-3
                                           outline-none focus:ring-2 focus:ring-indigo-500 appearance-none cursor-pointer">
                                <option value="">Todos</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" @selected(request('department') == $department->id)>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- Ordenação --}}
                        <div>
                            <label for="filter-order" class="block text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">
                                Ordenação
                            </label>
                            <select id="filter-order" name="order"
                                    class="w-full text-sm bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 rounded-xl py-2 px-3
                                           outline-none focus:ring-2 focus:ring-indigo-500 appearance-none cursor-pointer">
                                <option value="3" @selected(!in_array(request('order'), ['1', '2']))>Prioridade + Tempo</option>
                                <option value="1" @selected(request('order') === '1')>Última Atualização</option>
                                <option value="2" @selected(request('order') === '2')>Mais Recentes</option>
                            </select>
                        </div>

                        {{-- Ações do filtro --}}
                        <button type="submit"
                                class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs
                                       font-black uppercase tracking-wider rounded-xl transition-all shadow-md active:scale-95">
                            Aplicar Filtros
                        </button>

                        @if($hasActiveFilters)
                            <a href="{{ $clearFiltersRoute }}"
                               class="block text-center text-[10px] font-bold text-gray-400 dark:text-slate-500
                                      hover:text-red-500 dark:hover:text-red-400 transition-colors uppercase tracking-widest">
                                Limpar tudo
                            </a>
                        @endif

                    </form>
                </div>
            </div>
        </aside>

        {{-- ── Lista de tickets ──────────────────────────────────────────── --}}
        <section class="flex-1 min-w-0 space-y-3">

            @forelse($tickets as $ticket)
                @php
                    $slaLevel = $ticket->sla_level ?? 'normal';
                    $badgeSla = match($slaLevel) {
                        'attention' => 'bg-blue-100 text-blue-700',
                        'warning'   => 'bg-yellow-100 text-yellow-700',
                        'critical'  => 'bg-red-100 text-red-700',
                        'resolved'  => 'bg-emerald-100 text-emerald-700',
                        default     => 'bg-emerald-100 text-emerald-700',
                    };
                    $labelSla = match($slaLevel) {
                        'attention' => 'Atenção',
                        'warning'   => 'Aviso',
                        'critical'  => 'Crítico',
                        'resolved'  => 'Concluído',
                        default     => 'No Prazo',
                    };
                    $statusColor = $ticket->status?->color ?? '#6366f1';
                    $isNew       = session('new_id') == $ticket->id;
                    $isWhatsApp  = (int) $ticket->origin_id === (int) config('whatsapp.chatbot.origin_id', 5)
                        || strcasecmp((string) $ticket->origin?->name, 'WhatsApp') === 0;
                @endphp

                {{-- Card do ticket --}}
                <article class="rounded-2xl border bg-white dark:bg-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200
                                {{ $isNew ? 'border-indigo-400 ring-2 ring-indigo-100 dark:ring-indigo-900/40' : 'border-gray-200 dark:border-slate-700' }}"
                         style="border-left: 5px solid {{ $statusColor }}">

                    <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-4">

                        {{-- ID Badge --}}
                        <div class="relative flex-shrink-0">
                            <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-100 dark:border-indigo-800 flex items-center
                                        justify-center text-indigo-600 dark:text-indigo-300 font-black text-xs leading-tight text-center">
                                #{{ $ticket->id }}
                            </div>
                            @if($isNew)
                                <span class="absolute -top-1 -right-1 flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
                                </span>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 mb-0.5 text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">
                                <span>{{ $ticket->category?->display_name ?? 'Sem categoria' }}</span>
                                @if($ticket->subCategory)
                                    <span class="text-gray-300 dark:text-slate-600">›</span>
                                    <span class="text-indigo-500 dark:text-indigo-400">{{ $ticket->subCategory->display_name }}</span>
                                @endif
                                @if($ticket->department)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 normal-case tracking-normal text-[10px] font-semibold border border-transparent dark:border-blue-800/50">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        {{ $ticket->department->name }}
                                    </span>
                                @endif
                            </div>

                            <h2 class="font-bold text-gray-900 dark:text-slate-100 truncate hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                <a href="{{ route('agent.ticket.show', $ticket->id) }}"
                                   @if($openTicketNewTab) target="_blank" rel="noopener noreferrer" @endif
                                   class="focus:outline-none">
                                    {{ $ticket->subject }}
                                </a>
                            </h2>

                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-xs text-gray-500">
                                @if($ticket->company)
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/>
                                        </svg>
                                        {{ $ticket->company->trade_name ?: $ticket->company->name }}
                                    </span>
                                @endif
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    {{ $ticket->agent?->name ?? 'Sem agente' }}
                                </span>
                                @if($ticket->contact)
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        {{ $ticket->contact }}
                                    </span>
                                @endif
                                @if($ticket->origin)
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-width="2" d="M3 5h18M7 12h10M10 19h4"/>
                                        </svg>
                                        {{ $ticket->origin->name }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Badges + Puxar para mim --}}
                        <div class="flex-shrink-0 flex sm:flex-col items-center sm:items-end gap-1.5">
                            <div class="flex items-center gap-1.5 flex-wrap justify-end">
                                {{-- SLA --}}
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $badgeSla }}">
                                    SLA: {{ $labelSla }}
                                </span>
                                @if($isWhatsApp)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-green-100 text-green-700 border border-green-200">
                                        WhatsApp
                                    </span>
                                @endif
                                {{-- Status --}}
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                                      style="background-color: {{ $statusColor }}22; color: {{ $statusColor }}">
                                    {{ $ticket->status?->name ?? 'Aberto' }}
                                </span>
                            </div>
                            <time class="text-[10px] text-gray-400 italic" datetime="{{ $ticket->updated_at?->toIso8601String() }}">
                                {{ $ticket->updated_at?->diffForHumans() }}
                            </time>
                            {{-- Puxar para mim: visível apenas quando o ticket está sem agente --}}
                            @if(! $ticket->agent_id)
                                <form action="{{ route('agent.ticket.capture', $ticket->id) }}" method="POST" class="mt-0.5">
                                    @csrf
                                    <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-indigo-50 text-indigo-600 border border-indigo-200 hover:bg-indigo-100 transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/>
                                        </svg>
                                        Puxar para mim
                                    </button>
                                </form>
                            @endif
                        </div>

                    </div>
                </article>

            @empty
                {{-- Empty state --}}
                <div class="flex flex-col items-center justify-center py-24 text-center rounded-2xl border border-dashed border-gray-200 bg-white">
                    <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-gray-600 font-semibold mb-1">Nenhum chamado encontrado</p>
                    <p class="text-gray-400 text-sm mb-5">Ajuste os filtros ou registre um novo chamado.</p>
                    <a href="{{ route('agent.ticket.create') }}"
                       @if($openTicketNewTab) target="_blank" rel="noopener noreferrer" @endif
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-bold text-sm
                              hover:bg-indigo-700 transition-all shadow-md">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Novo Chamado
                    </a>
                </div>
            @endforelse

            {{-- Paginação --}}
            @if($tickets->hasPages())
                <div class="pt-2">
                    {{ $tickets->links() }}
                </div>
            @endif

        </section>
    </div>

</div>

@endsection
