@extends('layouts.agent')

@section('title', 'Dashboard - Agente')

@section('content')
<div class="max-w-6xl mx-auto space-y-8 animate-fade-in-up" style="animation: fadeInUp 0.5s ease-out forwards;">

    {{-- Welcome Card --}}
    <div class="relative overflow-hidden bg-white rounded-2xl p-8 border border-gray-100 shadow-xl shadow-gray-200/50 transition-all duration-300 hover:shadow-2xl">
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-gradient-to-br from-orange-400/30 to-rose-400/30 blur-3xl rounded-full pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-gradient-to-tr from-blue-400/20 to-indigo-400/20 blur-3xl rounded-full pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-sm font-medium mb-4 border border-emerald-100">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    Sistema Online
                </div>
                <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight mb-2">
                    Olá, {{ explode(' ', auth('admin')->user()->name)[0] }}! 👋
                </h1>
                <p class="text-lg text-gray-600 max-w-xl">
                    Bem-vindo ao <span class="font-semibold text-orange-600">Painel do Agente</span>. Explore atendimento, implantação e os demais módulos disponíveis para você.
                </p>
            </div>

        </div>
    </div>

    {{-- Quick Access Grid --}}
    <div>
        <h2 class="text-xl font-bold text-gray-800 mb-4">Atendimento</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

            {{-- Novo Ticket --}}
            <a href="{{ route('agent.ticket.create') }}"
               class="group flex flex-col p-6 bg-white rounded-2xl border border-gray-200 hover:border-orange-400 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="w-12 h-12 rounded-xl bg-orange-50 flex items-center justify-center text-orange-600 mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Novo Ticket</h3>
                <p class="text-gray-500 text-sm flex-grow">Abra um novo chamado de suporte para um cliente.</p>
                <div class="mt-4 text-orange-600 text-sm font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                    Abrir <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </div>
            </a>

            {{-- Calendário / Visão Condensada --}}
            <a href="{{ route('agent.calendar.condensed') }}"
               class="group flex flex-col p-6 bg-white rounded-2xl border border-gray-200 hover:border-orange-400 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Atendimentos (Timeline)</h3>
                <p class="text-gray-500 text-sm flex-grow">Visualize tickets e agendamentos da semana em uma visão condensada.</p>
                <div class="mt-4 text-blue-600 text-sm font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                    Ver atendimentos <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </div>
            </a>

        </div>
    </div>

    {{-- Implantação --}}
    <div>
        <h2 class="text-xl font-bold text-gray-800 mb-4">Implantação</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

            <a href="{{ route('agent.implantacao.index') }}"
               class="group flex flex-col p-6 bg-white rounded-2xl border border-gray-200 hover:border-indigo-400 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Visão Geral</h3>
                <p class="text-gray-500 text-sm flex-grow">Acompanhe KPIs, clientes em andamento e os atalhos centrais do módulo.</p>
                <div class="mt-4 text-indigo-600 text-sm font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                    Abrir visão geral <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </div>
            </a>

            <a href="{{ route('agent.schedules.index') }}"
               class="group relative flex flex-col p-6 bg-white rounded-2xl border {{ $schedules_overdue > 0 ? 'border-red-200' : 'border-gray-200' }} hover:border-orange-400 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">

                @if($schedules_overdue > 0)
                    <span class="absolute -top-2 -right-2 flex items-center justify-center min-w-[1.5rem] h-6 px-1.5 rounded-full bg-red-500 text-white text-xs font-bold shadow-md shadow-red-200 ring-2 ring-white">
                        {{ $schedules_overdue > 9 ? '9+' : $schedules_overdue }}
                    </span>
                @endif

                <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600 mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>

                <h3 class="text-base font-bold text-gray-900 mb-1">Calendário de Implantação</h3>
                <p class="text-gray-500 text-sm flex-grow">Visualize a agenda operacional em formato compacto, com foco nos agendamentos em curso.</p>

                @if($schedules_overdue > 0 || $schedules_today > 0 || $schedules_upcoming > 0)
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        @if($schedules_overdue > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-50 text-red-600 text-xs font-semibold border border-red-200">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $schedules_overdue }} atrasado{{ $schedules_overdue > 1 ? 's' : '' }}
                            </span>
                        @endif
                        @if($schedules_today > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold border border-amber-200">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3"/></svg>
                                {{ $schedules_today }} hoje
                            </span>
                        @endif
                        @if($schedules_upcoming > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-purple-50 text-purple-600 text-xs font-semibold border border-purple-100">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ $schedules_upcoming }} próximo{{ $schedules_upcoming > 1 ? 's' : '' }}
                            </span>
                        @endif
                    </div>
                @endif

                <div class="mt-4 text-purple-600 text-sm font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                    Ver calendário <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </div>
            </a>

            <a href="{{ route('agent.schedules.create') }}"
               class="group flex flex-col p-6 bg-white rounded-2xl border border-gray-200 hover:border-orange-400 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="w-12 h-12 rounded-xl bg-orange-50 flex items-center justify-center text-orange-600 mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Novo Agendamento</h3>
                <p class="text-gray-500 text-sm flex-grow">Cadastre uma nova etapa de implantação e envie o lançamento para o fluxo operacional.</p>
                <div class="mt-4 text-orange-600 text-sm font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                    Criar agendamento <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </div>
            </a>

            <a href="{{ route('agent.report.implementation-clients') }}"
               class="group flex flex-col p-6 bg-white rounded-2xl border border-gray-200 hover:border-indigo-400 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Relatório de Implantações</h3>
                <p class="text-gray-500 text-sm flex-grow">Consulte os clientes ativos em implantação com tickets e agendamentos consolidados.</p>
                <div class="mt-4 text-indigo-600 text-sm font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                    Ver relatório <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </div>
            </a>

        </div>
    </div>

    {{-- Desenvolvimento --}}
    <div>
        <h2 class="text-xl font-bold text-gray-800 mb-4">Desenvolvimento</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

            {{-- Tarefas --}}
            @php $tasks_today = $tasks_today ?? 0; @endphp
            @php $tasks_alert = $tasks_notifications + $tasks_new + $tasks_stopped + $tasks_today; @endphp
            <a href="{{ route('tasks.index') }}"
               class="group relative flex flex-col p-6 bg-white rounded-2xl border {{ $tasks_alert > 0 ? 'border-indigo-200' : 'border-gray-200' }} hover:border-indigo-400 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">

                {{-- Badge de alertas --}}
                @if($tasks_alert > 0)
                    <span class="absolute -top-2 -right-2 flex items-center justify-center min-w-[1.5rem] h-6 px-1.5 rounded-full bg-indigo-500 text-white text-xs font-bold shadow-md shadow-indigo-200 ring-2 ring-white">
                        {{ $tasks_alert > 9 ? '9+' : $tasks_alert }}
                    </span>
                @endif

                <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>

                <h3 class="text-base font-bold text-gray-900 mb-1">Tarefas</h3>
                <p class="text-gray-500 text-sm flex-grow">Acompanhe e gerencie as tarefas de desenvolvimento.</p>

                {{-- Indicadores de status --}}
                @if($tasks_notifications > 0 || $tasks_new > 0 || $tasks_stopped > 0 || $tasks_today > 0)
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        @if($tasks_today > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3"/></svg>
                                {{ $tasks_today }} para hoje
                            </span>
                        @endif
                        @if($tasks_notifications > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 text-xs font-semibold border border-indigo-200">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zm0 16a2 2 0 002-2H8a2 2 0 002 2z"/></svg>
                                {{ $tasks_notifications }} notificaç{{ $tasks_notifications > 1 ? 'ões' : 'ão' }}
                            </span>
                        @endif
                        @if($tasks_new > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold border border-amber-200">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                {{ $tasks_new }} nova{{ $tasks_new > 1 ? 's' : '' }}
                            </span>
                        @endif
                        @if($tasks_stopped > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-50 text-red-600 text-xs font-semibold border border-red-200">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $tasks_stopped }} parada{{ $tasks_stopped > 1 ? 's' : '' }}
                            </span>
                        @endif
                    </div>
                @endif

                <div class="mt-4 text-indigo-600 text-sm font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                    Ver tarefas <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </div>
            </a>

        </div>
    </div>

    {{-- Clientes & Empresas --}}
    <div>
        <h2 class="text-xl font-bold text-gray-800 mb-4">Clientes & Empresas</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

            {{-- Gerenciar Empresas --}}
            <a href="{{ route('agent.companies.manage.index') }}"
               class="group flex flex-col p-6 bg-white rounded-2xl border border-gray-200 hover:border-emerald-400 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Empresas</h3>
                <p class="text-gray-500 text-sm flex-grow">Visualize e gerencie o cadastro de empresas clientes.</p>
                <div class="mt-4 text-emerald-600 text-sm font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                    Gerenciar <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </div>
            </a>

            {{-- Monitor --}}
            <a href="{{ route('agent.monitor') }}"
               class="group flex flex-col p-6 bg-white rounded-2xl border border-gray-200 hover:border-sky-400 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="w-12 h-12 rounded-xl bg-sky-50 flex items-center justify-center text-sky-600 mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Monitor</h3>
                <p class="text-gray-500 text-sm flex-grow">Monitore o status e a atividade dos atendimentos em tempo real.</p>
                <div class="mt-4 text-sky-600 text-sm font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                    Monitorar <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </div>
            </a>

        </div>
    </div>



</div>

<style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>
@endsection
