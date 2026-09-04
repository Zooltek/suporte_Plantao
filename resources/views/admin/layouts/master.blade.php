<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Painel') - Administração</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/amura-icon.png') }}?v=2">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    
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
        * { font-family: 'Inter', system-ui, -apple-system, sans-serif; }

        .admin-icon-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 2.25rem; height: 2.25rem; border: 1px solid #d1d5db;
            border-radius: 0.5rem; background-color: #ffffff; transition: all .15s ease-in-out;
        }
        .admin-icon-btn svg { width: 1rem; height: 1rem; }
        .admin-icon-btn:disabled { opacity: .55; cursor: not-allowed; }
        .admin-icon-btn--edit { color: #2563eb; }
        .admin-icon-btn--edit:hover { color: #1d4ed8; background-color: #eff6ff; border-color: #93c5fd; }
        .admin-icon-btn--view { color: #64748b; }
        .admin-icon-btn--view:hover { color: #334155; background-color: #f8fafc; border-color: #cbd5e1; }
        .admin-icon-btn--delete { color: #ef4444; }
        .admin-icon-btn--delete:hover { color: #dc2626; background-color: #fef2f2; border-color: #fca5a5; }
        .admin-icon-btn--save { color: #2563eb; }
        .admin-icon-btn--save:hover { color: #1d4ed8; background-color: #eff6ff; border-color: #93c5fd; }
        .admin-icon-btn--save.is-loading { color: #2563eb; opacity: 0.5; cursor: not-allowed; }
        .admin-icon-btn--save.is-saved { color: #10b981; background-color: #d1fae5; border-color: #6ee7b7; }
        .admin-icon-btn--primary { color: #2563eb; background-color: #eff6ff; border-color: #bfdbfe; }
        .admin-icon-btn--primary:hover { color: #1d4ed8; background-color: #dbeafe; border-color: #93c5fd; }
        .admin-icon-btn--success { color: #10b981; background-color: #d1fae5; border-color: #6ee7b7; }
        .admin-icon-btn--success:hover { color: #059669; background-color: #a7f3d0; border-color: #34d399; }

        html.ocean aside section.bg-gray-50 {
            background-color: #0f172a !important;
            border-color: #334155 !important;
        }

        html.ocean aside section.bg-gray-50 > button:hover {
            background-color: #1e293b !important;
        }

        html.ocean aside .bg-gradient-to-r.from-blue-50.to-indigo-50 {
            background-image: none !important;
            background-color: #1e3a8a !important;
            border-color: #2563eb !important;
            color: #dbeafe !important;
            box-shadow: none !important;
        }

        html.ocean aside .text-gray-600:hover {
            background-color: #1e293b !important;
            color: #e2e8f0 !important;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-800 transition-colors duration-300">

    @php
        $authUser = Auth::guard('admin')->user() ?? Auth::user();
        $isAdmin  = (bool) ($authUser?->ticketit_admin);
        $isAgent  = (bool) ($authUser?->ticketit_agent);
        $isStaff  = $isAdmin || $isAgent;
        $isCrm    = !$isStaff && $authUser?->department_id == 3;

        $sidebarSections = [];

        if ($isAdmin) {
            $sidebarSections['gestao'] = [
                'active' => request()->routeIs('admin.users.*')
                    || request()->routeIs('admin.categories.*')
                    || request()->routeIs('admin.sla.*')
                    || request()->routeIs('departments.*')
                    || request()->routeIs('admin.settings*'),
            ];
        }

        $sidebarSections['tarefas'] = [
            'active' => request()->routeIs('admin.tasks.*') || request()->routeIs('tasks.*'),
        ];

        if ($isAdmin || $isStaff || $isCrm) {
            $sidebarSections['helpdesk'] = [
                'active' => request()->routeIs('admin.crm.feedback.*')
                    || request()->routeIs('admin.helpdesk.*')
                    || request()->routeIs('admin.whatsapp.*'),
            ];
        }

        if ($isAdmin) {
            $sidebarSections['implantacao'] = [
                'active' => request()->routeIs('admin.implantacao.*'),
            ];

            $sidebarSections['relatorios'] = [
                'active' => request()->routeIs('admin.reports.*') || request()->routeIs('admin.oncall.*'),
            ];
        }

        if ($isStaff || $isCrm) {
            $sidebarSections['navegacao'] = ['active' => false];
        }

        $sidebarInitialOpen = [];
        foreach ($sidebarSections as $sectionKey => $sectionData) {
            $sidebarInitialOpen[$sectionKey] = (bool) $sectionData['active'];
        }

        if (!in_array(true, $sidebarInitialOpen, true)) {
            if ($isAdmin) {
                $sidebarInitialOpen['gestao'] = true;
            } else {
                $sidebarInitialOpen['tarefas'] = true;
            }
        }
    @endphp

    <div
        class="min-h-screen flex flex-col"
        x-data="adminLayout"
        data-sidebar-sections='@json($sidebarInitialOpen)'
    >

        {{-- Top Navbar --}}
        <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-200 shadow-sm transition-colors duration-300">
            <div class="px-4 lg:px-6 h-14 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    {{-- Mobile hamburger --}}
                    <button @click.stop="sidebarOpen = !sidebarOpen"
                            class="lg:hidden p-2 -ml-1 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    {{-- Logo --}}
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                        <img src="{{ asset('img/amura-logo-light.png') }}" alt="Amura" class="h-8 w-auto dark:hidden">
                        <img src="{{ asset('img/amura-logo-dark.png') }}" alt="Amura" class="h-8 w-auto hidden dark:block">
                        <span class="font-bold text-lg bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600 tracking-tight hidden sm:inline ml-2 border-l border-gray-300 dark:border-gray-600 pl-2">Admin</span>
                    </a>
                </div>

                {{-- Page title (center) --}}
                <h1 class="hidden lg:block text-sm font-semibold text-gray-600">@yield('page-title')</h1>

                {{-- Right actions --}}
                <div class="flex items-center gap-4">
                    <div class="hidden md:block text-sm text-gray-500">
                        <span class="font-semibold text-gray-800">{{ Auth::user()->name }}</span>
                    </div>
                    <div class="scale-90">
                        <x-theme-toggle />
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}" class="m-0 p-0">
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

                    <a href="{{ route('admin.dashboard') }}" @click="handleSidebarLinkClick"
                       class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border border-transparent' }}">
                        <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Dashboard
                    </a>

                    {{-- GESTÃO — apenas administradores --}}
                    @if($isAdmin)
                    <section class="rounded-xl border border-gray-200 bg-gray-50">
                        <button type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left transition-colors hover:bg-gray-50"
                                @click="toggleSidebarSection('gestao')"
                                :aria-expanded="isSidebarSectionOpen('gestao')"
                                aria-controls="admin-sidebar-section-gestao">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Gestão</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                 :class="isSidebarSectionOpen('gestao') ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="admin-sidebar-section-gestao" x-show="isSidebarSectionOpen('gestao')" x-collapse x-cloak class="px-1 pb-2">
                            <div class="space-y-0.5">
                                <a href="{{ route('admin.users.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    Usuários
                                </a>

                                <a href="{{ route('admin.settings') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.settings*') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Minha Conta
                                </a>

                                <a href="{{ route('admin.categories.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.categories.*') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                    Categorias
                                </a>

                                <a href="{{ route('admin.sla.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.sla.*') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l2.5 2.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    SLA (Minutos)
                                </a>

                                <a href="{{ route('departments.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('departments.*') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    Departamentos
                                </a>
                            </div>
                        </div>
                    </section>
                    @endif

                    {{-- TAREFAS --}}
                    <section class="rounded-xl border border-gray-200 bg-gray-50">
                        <button type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left transition-colors hover:bg-gray-50"
                                @click="toggleSidebarSection('tarefas')"
                                :aria-expanded="isSidebarSectionOpen('tarefas')"
                                aria-controls="admin-sidebar-section-tarefas">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Tarefas</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                 :class="isSidebarSectionOpen('tarefas') ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="admin-sidebar-section-tarefas" x-show="isSidebarSectionOpen('tarefas')" x-collapse x-cloak class="px-1 pb-2">
                            <div class="space-y-0.5">
                                <a href="{{ route('tasks.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('tasks.*') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    Minhas Tarefas
                                </a>

                                <a href="{{ route('admin.tasks.report.carlos') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.tasks.report.carlos') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Relatório por Módulo
                                </a>

                                <a href="{{ route('admin.tasks.report.por-cliente') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.tasks.report.por-cliente') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    Solicitações por Cliente
                                </a>

                                <a href="{{ route('admin.tasks.report.por-modulo') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.tasks.report.por-modulo') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                    Label por Módulo
                                </a>
                            </div>
                        </div>
                    </section>

                    {{-- HELPDESK — admin, staff (agent) ou CRM --}}
                    @if($isAdmin || $isStaff || $isCrm)
                    <section class="rounded-xl border border-gray-200 bg-gray-50">
                        <button type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left transition-colors hover:bg-gray-50"
                                @click="toggleSidebarSection('helpdesk')"
                                :aria-expanded="isSidebarSectionOpen('helpdesk')"
                                aria-controls="admin-sidebar-section-helpdesk">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Helpdesk</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                 :class="isSidebarSectionOpen('helpdesk') ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="admin-sidebar-section-helpdesk" x-show="isSidebarSectionOpen('helpdesk')" x-collapse x-cloak class="px-1 pb-2">
                            <div class="space-y-0.5">
                                @if($isAdmin || $isCrm)
                                <a href="{{ route('admin.crm.feedback.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.crm.feedback.*') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                    CRM Feedback
                                </a>
                                @endif

                                @if($isAdmin)
                                <a href="{{ route('admin.helpdesk.dashboard') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.helpdesk.*') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                    Painel de Suporte
                                </a>

                                <a href="{{ route('admin.whatsapp.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.whatsapp.*') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.127.558 4.121 1.532 5.854L.057 23.882l6.186-1.454A11.934 11.934 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.894 0-3.668-.523-5.183-1.432l-.371-.22-3.676.864.923-3.577-.241-.388A9.958 9.958 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                                    WhatsApp
                                </a>
                                @endif

                                @if($isStaff)
                                <a href="{{ route('agent.knowledge.index') }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    EasyWiki
                                    <svg class="w-3 h-3 ml-auto text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                                @endif
                            </div>
                        </div>
                    </section>
                    @endif

                    {{-- IMPLANTAÇÃO / RAT — apenas administradores --}}
                    @if($isAdmin)
                    <section class="rounded-xl border border-gray-200 bg-gray-50">
                        <button type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left transition-colors hover:bg-gray-50"
                                @click="toggleSidebarSection('implantacao')"
                                :aria-expanded="isSidebarSectionOpen('implantacao')"
                                aria-controls="admin-sidebar-section-implantacao">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Implantação</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                 :class="isSidebarSectionOpen('implantacao') ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="admin-sidebar-section-implantacao" x-show="isSidebarSectionOpen('implantacao')" x-collapse x-cloak class="px-1 pb-2">
                            <div class="space-y-0.5">
                                <a href="{{ route('admin.implantacao.rat-modules.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.implantacao.rat-modules.*') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    Módulos / Projetos
                                </a>

                                <a href="{{ route('admin.implantacao.groups.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.implantacao.groups.*') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    Grupos do RAT
                                </a>

                                <a href="{{ route('admin.implantacao.rat.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.implantacao.rat.*') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    Checklist do RAT
                                </a>

                                <a href="{{ route('admin.implantacao.schedule-types.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.implantacao.schedule-types.*') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>
                                    Tipos de Agendamento
                                </a>

                                <a href="{{ route('admin.implantacao.modules.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.implantacao.modules.*') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    Módulos por Cliente
                                </a>
                            </div>
                        </div>
                    </section>
                    @endif

                    {{-- RELATÓRIOS — apenas administradores --}}
                    @if($isAdmin)
                    <section class="rounded-xl border border-gray-200 bg-gray-50">
                        <button type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left transition-colors hover:bg-gray-50"
                                @click="toggleSidebarSection('relatorios')"
                                :aria-expanded="isSidebarSectionOpen('relatorios')"
                                aria-controls="admin-sidebar-section-relatorios">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Relatórios</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                 :class="isSidebarSectionOpen('relatorios') ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="admin-sidebar-section-relatorios" x-show="isSidebarSectionOpen('relatorios')" x-collapse x-cloak class="px-1 pb-2">
                            <div class="space-y-0.5">
                                <a href="{{ route('admin.tasks.report.carlos') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.tasks.report.carlos') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                    Tarefas por Módulo
                                </a>

                                <a href="{{ route('admin.tasks.report.por-cliente') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.tasks.report.por-cliente') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    Solicitações por Cliente
                                </a>

                                <a href="{{ route('admin.tasks.report.por-modulo') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.tasks.report.por-modulo') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                    Label por Módulo
                                </a>

                                <a href="{{ route('admin.reports.daily-problems') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.reports.daily-problems') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Resumo por Problema
                                </a>

                                <a href="{{ route('admin.reports.implementation-clients') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.reports.implementation-clients') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    Clientes em Implantação
                                </a>

                                <a href="{{ route('admin.reports.clients-without-attendance') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.reports.clients-without-attendance') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                    Clientes sem Atendimento
                                </a>

                                <a href="{{ route('admin.reports.client-updates') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.reports.client-updates') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H12" /></svg>
                                    Atualização de Clientes
                                </a>

                                <a href="{{ route('admin.oncall.reports') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.oncall.*') ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 font-semibold border border-blue-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Plantão & Sobreaviso
                                </a>

                                <a href="{{ route('admin.dashboard-tv', ['token' => config('app.dashboard_tv_token', env('DASHBOARD_TV_TOKEN', 'amuratv2026'))]) }}" 
                                   target="_blank"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    Dashboard TV ↗
                                </a>
                            </div>
                        </div>
                    </section>
                    @endif

                    {{-- NAVEGAÇÃO — staff (admin/agent) ou CRM --}}
                    @if($isStaff || $isCrm)
                    <section class="rounded-xl border border-gray-200 bg-gray-50">
                        <button type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left transition-colors hover:bg-gray-50"
                                @click="toggleSidebarSection('navegacao')"
                                :aria-expanded="isSidebarSectionOpen('navegacao')"
                                aria-controls="admin-sidebar-section-navegacao">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Navegação</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                 :class="isSidebarSectionOpen('navegacao') ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="admin-sidebar-section-navegacao" x-show="isSidebarSectionOpen('navegacao')" x-collapse x-cloak class="px-1 pb-2">
                            <div class="space-y-0.5">
                                @if($isStaff)
                                <a href="{{ route('agent.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 text-gray-600 hover:bg-orange-50 hover:text-orange-700 border border-dashed border-gray-200 hover:border-orange-300">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                    Painel Agent
                                    <svg class="w-3 h-3 ml-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>

                                <a href="{{ route('agent.ticket.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 text-gray-600 hover:bg-rose-50 hover:text-rose-700 border border-dashed border-gray-200 hover:border-rose-300">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                    Painel Tickets
                                    <svg class="w-3 h-3 ml-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                                @endif

                                @if($isAdmin || $isCrm)
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
                    @endif
                </nav>

                {{-- Sidebar Footer --}}
                <div class="px-4 py-3 border-t border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-800 truncate">{{ Auth::user()->name }}</p>
                            <p class="text-[10px] text-gray-400 truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- Main Content Area --}}
            <main class="flex-1 min-w-0 p-6 lg:p-8 overflow-y-auto">
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
