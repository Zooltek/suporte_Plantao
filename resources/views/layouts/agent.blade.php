<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Suporte - Painel do Agente')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Prevent FOUC (Flash of Unstyled Content) for Dark Mode -->
    <script>
        if (localStorage.getItem('theme') === 'ocean' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('ocean');
        } else {
            document.documentElement.classList.remove('ocean');
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }

        html.ocean aside section.bg-gray-50 {
            background-color: #0f172a !important;
            border-color: #334155 !important;
        }

        html.ocean aside section.bg-gray-50 > button:hover {
            background-color: #1e293b !important;
        }

        html.ocean aside .bg-gradient-to-r.from-orange-50.to-rose-50 {
            background-image: none !important;
            background-color: #9a3412 !important;
            border-color: #c2410c !important;
            color: #ffedd5 !important;
            box-shadow: none !important;
        }

        html.ocean aside .text-gray-600:hover {
            background-color: #1e293b !important;
            color: #e2e8f0 !important;
        }

        /* Bug 4 — Minha Conta: nome invisível no hover do modo escuro */
        html.ocean aside a .text-gray-800,
        html.ocean aside a p.text-xs.font-semibold {
            color: #e2e8f0 !important;
        }
        html.ocean aside a:hover p.text-xs.font-semibold,
        html.ocean aside a.group:hover .group-hover\:text-orange-700 {
            color: #fb923c !important;
        }
        html.ocean aside div.border-t {
            border-color: #334155 !important;
        }
        html.ocean aside a:hover {
            background-color: #1e293b !important;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-800 transition-colors duration-300">

    @php
        $authUser = auth()->guard('admin')->user() ?? auth()->user();
        $accessService = app(\App\Services\Access\AccessService::class);
        $isTicketitAdmin = $authUser && $accessService->isAdmin($authUser);

        $isImplementationCalendarRoute = request()->routeIs('agent.calendar.*')
            && request()->query('active') === 'schedules';
        $isImplementationScheduleRoute = request()->routeIs('agent.schedules.*')
            || request()->routeIs('agent.record.*');
        $isImplementationReportRoute = request()->routeIs('agent.report.implementation-clients');
        $isImplementationContext = request()->routeIs('agent.implantacao.*')
            || $isImplementationCalendarRoute
            || $isImplementationScheduleRoute
            || $isImplementationReportRoute;
        $isSupportTimelineRoute = request()->routeIs('agent.calendar.*')
            && request()->query('active') !== 'schedules';

        $currentPageTitle = match (true) {
            request()->routeIs('agent.index') => 'Dashboard',
            request()->routeIs('agent.ticket.index') && (request()->boolean('unassigned') || request('agent') === 'unassigned' || request('agent') === '0') => 'Chamados Sem Agente',
            request()->routeIs('agent.ticket.index') && request()->boolean('mine') => 'Meus Chamados',
            request()->routeIs('agent.ticket.index') => 'Todos os Chamados',
            request()->routeIs('agent.ticket.create') => 'Novo Ticket',
            request()->routeIs('agent.ticket.edit') => 'Editar Ticket',
            request()->routeIs('agent.ticket.show') => 'Detalhes do Ticket',
            $isSupportTimelineRoute => 'Timeline de Atendimento',
            request()->routeIs('agent.knowledge.index') => 'Base de Conhecimento',
            request()->routeIs('agent.knowledge.create') => 'Novo Artigo',
            request()->routeIs('agent.knowledge.show') => 'Artigo',
            request()->routeIs('agent.companies.*') => 'Empresas',
            request()->routeIs('agent.monitor') => 'Monitor',
            request()->routeIs('agent.helper') => 'Ajuda',
            request()->routeIs('agent.implantacao.index') => 'Visão Geral de Implantação',
            request()->routeIs('agent.implantacao.schedules') => 'Agendamentos de Implantação',
            $isImplementationCalendarRoute => 'Calendário de Implantação',
            request()->routeIs('agent.schedules.create') => 'Novo Agendamento de Implantação',
            request()->routeIs('agent.schedules.edit') => 'Editar Agendamento de Implantação',
            request()->routeIs('agent.schedules.show') => 'Detalhes do Agendamento',
            request()->routeIs('agent.record.index') => 'RATs do Agendamento',
            request()->routeIs('agent.record.create') => 'Novo RAT',
            request()->routeIs('agent.record.edit') => 'Editar RAT',
            $isImplementationReportRoute => 'Relatório de Implantação',
            request()->routeIs('agent.account', 'agent.account.update') => 'Minha Conta',
            default => 'Painel do Agente',
        };

        $moduleContext = match (true) {
            request()->routeIs('agent.index') => [
                'label' => 'Agente',
                'homeRoute' => 'agent.index',
            ],
            $isImplementationContext => [
                'label' => 'Implantação',
                'homeRoute' => 'agent.implantacao.index',
            ],
            request()->routeIs('agent.companies.*')
                || request()->routeIs('agent.monitor')
                || request()->routeIs('agent.helper') => [
                'label' => 'Clientes',
                'homeRoute' => 'agent.companies.manage.index',
            ],
            request()->routeIs('agent.ticket.*')
                || $isSupportTimelineRoute
                || request()->routeIs('agent.knowledge.*') => [
                'label' => 'Atendimento',
                'homeRoute' => 'agent.index',
            ],
            default => [
                'label' => 'Agente',
                'homeRoute' => 'agent.index',
            ],
        };

        $sidebarSections = [
            'atendimento' => [
                'active' => request()->routeIs('agent.ticket.*')
                    || $isSupportTimelineRoute
                    || request()->routeIs('agent.knowledge.*'),
            ],
            'clientes' => [
                'active' => request()->routeIs('agent.companies.*')
                    || request()->routeIs('agent.monitor')
                    || request()->routeIs('agent.helper'),
            ],
            'implantacao' => [
                'active' => $isImplementationContext,
            ],
            'tarefas' => [
                'active' => request()->routeIs('tasks.*'),
            ],
        ];

        // Navegação para outros painéis visível a todos os usuários admin autenticados
        $sidebarSections['navegacao'] = [
            'active' => false,
        ];

        $sidebarInitialOpen = [];
        foreach ($sidebarSections as $sectionKey => $sectionData) {
            $sidebarInitialOpen[$sectionKey] = (bool) $sectionData['active'];
        }

        if (!in_array(true, $sidebarInitialOpen, true) && !empty($sidebarInitialOpen)) {
            $sidebarInitialOpen[array_key_first($sidebarInitialOpen)] = true;
        }
    @endphp

    <div
        class="min-h-screen flex flex-col"
        x-data="{
            sidebarOpen: false,
            sidebarSections: @js($sidebarInitialOpen),
            toggleSidebarSection(sectionKey) {
                const isCurrentOpen = Boolean(this.sidebarSections[sectionKey]);
                Object.keys(this.sidebarSections).forEach((key) => {
                    this.sidebarSections[key] = false;
                });
                this.sidebarSections[sectionKey] = !isCurrentOpen;
            },
            isSidebarSectionOpen(sectionKey) {
                return Boolean(this.sidebarSections[sectionKey]);
            },
            handleSidebarLinkClick() {
                if (window.innerWidth < 1024) {
                    this.sidebarOpen = false;
                }
            }
        }"
    >

        {{-- Top Navbar --}}
        <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-200 shadow-sm transition-colors duration-300">
            <div class="px-4 lg:px-6 h-14 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    {{-- Mobile hamburger --}}
                    <button @click.stop="sidebarOpen = !sidebarOpen"
                            class="lg:hidden p-2 -ml-1 text-gray-500 hover:text-orange-600 hover:bg-orange-50 rounded-xl transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    {{-- Logo --}}
                    <a href="{{ route($moduleContext['homeRoute']) }}" class="flex items-center gap-2.5">
                        <img src="{{ asset('img/amura-logo-light.png') }}" alt="Amura" class="h-8 w-auto dark:hidden">
                        <img src="{{ asset('img/amura-logo-dark.png') }}" alt="Amura" class="h-8 w-auto hidden dark:block">
                        <span class="font-bold text-lg bg-clip-text text-transparent bg-gradient-to-r from-orange-600 to-rose-600 tracking-tight hidden sm:inline ml-2 border-l border-gray-300 dark:border-gray-600 pl-2">{{ $moduleContext['label'] }}</span>
                    </a>
                </div>

                <h1 class="hidden lg:block text-sm font-semibold text-gray-600">{{ $currentPageTitle }}</h1>

                {{-- Right actions --}}
                <div class="flex items-center gap-4">
                    <div class="hidden md:block text-sm text-gray-500">
                        Bem-vindo, <span class="font-semibold text-gray-800">{{ $authUser?->name ?? 'Usuário' }}</span>
                    </div>
                    <div class="scale-90">
                        <x-theme-toggle />
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                        @csrf
                        <button type="submit"
                                class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:text-red-500 hover:bg-red-50 transition-colors"
                                title="Sair">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Layout: Sidebar + Content --}}
        <div class="flex-grow flex relative">

            {{-- Mobile Overlay --}}
            <div x-show="sidebarOpen" x-cloak
                 @click="sidebarOpen = false"
                 x-transition:enter="transition-opacity ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-black/30 backdrop-blur-sm z-40 lg:hidden">
            </div>

            {{-- Sidebar --}}
            <aside class="fixed lg:sticky top-14 left-0 z-40 h-[calc(100vh-3.5rem)] w-60 bg-white border-r border-gray-100 overflow-y-auto transition-transform duration-300 ease-in-out lg:translate-x-0 flex flex-col shadow-sm"
                   :class="sidebarOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full'"
                   @click.outside="sidebarOpen = false">

                <nav class="flex-1 px-3 py-5 space-y-2">

                    <a href="{{ route('agent.index') }}" @click="handleSidebarLinkClick"
                       class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('agent.index') ? 'bg-gradient-to-r from-orange-50 to-rose-50 text-orange-700 font-semibold border border-orange-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border border-transparent' }}">
                        <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Dashboard
                    </a>

                    {{-- ATENDIMENTO --}}
                    <section class="rounded-xl border border-gray-200 bg-gray-50">
                        <button type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left transition-colors hover:bg-gray-50"
                                @click="toggleSidebarSection('atendimento')"
                                :aria-expanded="isSidebarSectionOpen('atendimento')"
                                aria-controls="support-sidebar-section-atendimento">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Atendimento</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                 :class="isSidebarSectionOpen('atendimento') ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="support-sidebar-section-atendimento" x-show="isSidebarSectionOpen('atendimento')" x-collapse x-cloak class="px-1 pb-2">
                            <div class="space-y-0.5">
                                @php
                                    $isUnassignedActive = request()->routeIs('agent.ticket.index') && (request()->boolean('unassigned') || request('agent') === 'unassigned' || request('agent') === '0');
                                    $isMineActive = request()->routeIs('agent.ticket.index') && request()->boolean('mine') && ! $isUnassignedActive;
                                    $isAllActive  = request()->routeIs('agent.ticket.index') && ! request()->boolean('mine') && ! $isUnassignedActive;
                                @endphp

                                <a href="{{ route('agent.ticket.index', ['mine' => 1]) }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ $isMineActive ? 'bg-gradient-to-r from-orange-50 to-rose-50 text-orange-700 font-semibold border border-orange-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border border-transparent' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Meus Chamados
                                </a>

                                <a href="{{ route('agent.ticket.index', ['unassigned' => 1]) }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ $isUnassignedActive ? 'bg-gradient-to-r from-orange-50 to-rose-50 text-orange-700 font-semibold border border-orange-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border border-transparent' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                    Sem Agente
                                </a>

                                @if($isTicketitAdmin)
                                <a href="{{ route('agent.ticket.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ $isAllActive ? 'bg-gradient-to-r from-orange-50 to-rose-50 text-orange-700 font-semibold border border-orange-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border border-transparent' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Todos os Chamados
                                </a>
                                @endif

                                <a href="{{ route('agent.ticket.create') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('agent.ticket.create', 'agent.ticket.edit') ? 'bg-gradient-to-r from-orange-50 to-rose-50 text-orange-700 font-semibold border border-orange-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Novo Ticket
                                </a>

                                <a href="{{ route('agent.calendar.condensed') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('agent.calendar.*') && request()->query('active') !== 'schedules' ? 'bg-gradient-to-r from-orange-50 to-rose-50 text-orange-700 font-semibold border border-orange-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    Atendimentos (Timeline)
                                </a>

                                <a href="{{ route('agent.knowledge.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('agent.knowledge.*') ? 'bg-gradient-to-r from-orange-50 to-rose-50 text-orange-700 font-semibold border border-orange-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    EasyWiki
                                </a>
                            </div>
                        </div>
                    </section>

                    {{-- CLIENTES & EMPRESAS --}}
                    <section class="rounded-xl border border-gray-200 bg-gray-50">
                        <button type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left transition-colors hover:bg-gray-50"
                                @click="toggleSidebarSection('clientes')"
                                :aria-expanded="isSidebarSectionOpen('clientes')"
                                aria-controls="support-sidebar-section-clientes">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Clientes & Empresas</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                 :class="isSidebarSectionOpen('clientes') ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="support-sidebar-section-clientes" x-show="isSidebarSectionOpen('clientes')" x-collapse x-cloak class="px-1 pb-2">
                            <div class="space-y-0.5">
                                <a href="{{ route('agent.companies.manage.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('agent.companies.*') ? 'bg-gradient-to-r from-orange-50 to-rose-50 text-orange-700 font-semibold border border-orange-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    Empresas
                                </a>

                                <a href="{{ route('agent.monitor') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('agent.monitor') ? 'bg-gradient-to-r from-orange-50 to-rose-50 text-orange-700 font-semibold border border-orange-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    Monitor
                                </a>

                                <a href="{{ route('agent.helper') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('agent.helper') ? 'bg-gradient-to-r from-orange-50 to-rose-50 text-orange-700 font-semibold border border-orange-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Ajuda
                                </a>
                            </div>
                        </div>
                    </section>

                    {{-- IMPLANTAÇÃO --}}
                    <section class="rounded-xl border border-gray-200 bg-gray-50">
                        <button type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left transition-colors hover:bg-gray-50"
                                @click="toggleSidebarSection('implantacao')"
                                :aria-expanded="isSidebarSectionOpen('implantacao')"
                                aria-controls="support-sidebar-section-implantacao">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Implantação</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                 :class="isSidebarSectionOpen('implantacao') ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="support-sidebar-section-implantacao" x-show="isSidebarSectionOpen('implantacao')" x-collapse x-cloak class="px-1 pb-2">
                            <div class="space-y-0.5">
                                <a href="{{ route('agent.implantacao.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('agent.implantacao.index') ? 'bg-gradient-to-r from-orange-50 to-rose-50 text-orange-700 font-semibold border border-orange-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                                    Visão Geral
                                </a>
                                <a href="{{ route('agent.implantacao.schedules') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('agent.implantacao.schedules') || request()->routeIs('agent.schedules.edit', 'agent.schedules.show', 'agent.record.index', 'agent.record.show', 'agent.record.edit') ? 'bg-gradient-to-r from-orange-50 to-rose-50 text-orange-700 font-semibold border border-orange-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    Agendamentos de Implantação
                                </a>
                                <a href="{{ route('agent.calendar.condensed', ['active' => 'schedules']) }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ $isImplementationCalendarRoute ? 'bg-gradient-to-r from-orange-50 to-rose-50 text-orange-700 font-semibold border border-orange-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Calendário de Implantação
                                </a>
                                <a href="{{ route('agent.schedules.create') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('agent.schedules.create') ? 'bg-gradient-to-r from-orange-50 to-rose-50 text-orange-700 font-semibold border border-orange-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Novo Agendamento
                                </a>
                                <a href="{{ route('agent.report.implementation-clients') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ $isImplementationReportRoute ? 'bg-gradient-to-r from-orange-50 to-rose-50 text-orange-700 font-semibold border border-orange-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Relatório de Implantação
                                </a>
                            </div>
                        </div>
                    </section>

                    {{-- TAREFAS --}}
                    <section class="rounded-xl border border-gray-200 bg-gray-50">
                        <button type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left transition-colors hover:bg-gray-50"
                                @click="toggleSidebarSection('tarefas')"
                                :aria-expanded="isSidebarSectionOpen('tarefas')"
                                aria-controls="support-sidebar-section-tarefas">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Tarefas</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                 :class="isSidebarSectionOpen('tarefas') ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="support-sidebar-section-tarefas" x-show="isSidebarSectionOpen('tarefas')" x-collapse x-cloak class="px-1 pb-2">
                            <div class="space-y-0.5">
                                <a href="{{ route('tasks.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('tasks.*') ? 'bg-gradient-to-r from-orange-50 to-rose-50 text-orange-700 font-semibold border border-orange-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    Minhas Tarefas
                                </a>
                            </div>
                        </div>
                    </section>

                    {{-- NAVEGAÇÃO — visível a todos os usuários admin autenticados --}}
                    <section class="rounded-xl border border-gray-200 bg-gray-50">
                        <button type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left transition-colors hover:bg-gray-50"
                                @click="toggleSidebarSection('navegacao')"
                                :aria-expanded="isSidebarSectionOpen('navegacao')"
                                aria-controls="support-sidebar-section-navegacao">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Navegação</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                 :class="isSidebarSectionOpen('navegacao') ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="support-sidebar-section-navegacao" x-show="isSidebarSectionOpen('navegacao')" x-collapse x-cloak class="px-1 pb-2">
                            <div class="space-y-0.5">
                                @if($isTicketitAdmin)
                                <a href="{{ route('admin.dashboard') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 text-gray-600 hover:bg-blue-50 hover:text-blue-700 border border-dashed border-gray-200 hover:border-blue-300">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    Painel Admin
                                    <svg class="w-3 h-3 ml-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                                @endif

                                @if($accessService->canAccessFeedback($authUser))
                                <a href="{{ route('crm.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 text-gray-600 hover:bg-teal-50 hover:text-teal-700 border border-dashed border-gray-200 hover:border-teal-300">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    Painel CRM
                                    <svg class="w-3 h-3 ml-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                                @endif
                            </div>
                        </div>
                    </section>
                </nav>

                {{-- Sidebar Footer --}}
                <div class="px-4 py-3 border-t border-gray-100">
                    <a href="{{ route('agent.account') }}" @click="handleSidebarLinkClick"
                       class="flex items-center gap-3 rounded-xl p-1 -mx-1 hover:bg-orange-50 transition-colors group"
                       title="Minha Conta">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-orange-400 to-rose-400 flex items-center justify-center text-white text-xs font-bold shadow-sm flex-shrink-0">
                            {{ strtoupper(substr($authUser?->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-800 truncate group-hover:text-orange-700">{{ $authUser?->name ?? 'Usuário' }}</p>
                            <p class="text-[10px] text-gray-400 truncate">Minha Conta</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-orange-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </a>
                </div>
            </aside>

            {{-- Main Content Area --}}
            <main class="flex-1 min-w-0 p-6 lg:p-8">
                @yield('content')
            </main>
        </div>

        {{-- Footer --}}
        <footer class="py-5 border-t border-gray-200 bg-white">
            <div class="px-6 text-center">
                <p class="text-xs text-gray-400">
                    &copy; {{ date('Y') }} - <span class="font-semibold text-gray-500">Amura Sistemas</span>. Todos os direitos reservados.
                </p>
            </div>
        </footer>
    </div>

    @stack('scripts')
    <x-flash-toast />
    <x-confirm-modal />
</body>
</html>
