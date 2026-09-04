@extends('admin.layouts.master')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-6xl mx-auto space-y-8" style="animation: fadeInUp 0.5s ease-out forwards;">

    {{-- Welcome Card --}}
    <div class="relative overflow-hidden bg-white rounded-2xl p-7 border border-gray-100 shadow-xl shadow-gray-200/50">
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-gradient-to-br from-blue-400/30 to-indigo-400/30 blur-3xl rounded-full pointer-events-none"></div>
        <div class="absolute -bottom-20 -left-20 w-48 h-48 bg-gradient-to-tr from-violet-400/20 to-blue-400/20 blur-3xl rounded-full pointer-events-none"></div>
        <div class="relative z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-600 text-sm font-medium mb-3 border border-blue-100">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                </span>
                Painel Administrativo
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight mb-1">
                Olá, {{ explode(' ', Auth::user()->name)[0] }}! 👋
            </h1>
            <p class="text-gray-600">
                Gerencie usuários, categorias, módulos e configurações do sistema.
            </p>
        </div>
    </div>

    {{-- Gestão Section --}}
    <div>
        <h2 class="text-lg font-bold text-gray-800 mb-4">Gestão do Sistema</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            {{-- Usuários --}}
            <a href="{{ route('admin.users.index') }}"
               class="group flex flex-col p-5 bg-white rounded-2xl border border-gray-100 hover:border-blue-300 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-gray-900 mb-1">Usuários</h3>
                <p class="text-gray-500 text-xs flex-grow">Gerenciar contas e permissões de acesso.</p>
                <div class="mt-3 text-blue-600 text-xs font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                    Gerenciar <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </div>
            </a>

            {{-- Categorias --}}
            <a href="{{ route('admin.categories.index') }}"
               class="group flex flex-col p-5 bg-white rounded-2xl border border-gray-100 hover:border-amber-300 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-gray-900 mb-1">Categorias</h3>
                <p class="text-gray-500 text-xs flex-grow">Conteúdo, subcategorias e prioridades.</p>
                <div class="mt-3 text-amber-600 text-xs font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                    Gerenciar <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </div>
            </a>

            {{-- Módulos de Implantação --}}
            <a href="{{ route('admin.implantacao.rat-modules.index') }}"
               class="group flex flex-col p-5 bg-white rounded-2xl border border-gray-100 hover:border-violet-300 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="w-11 h-11 rounded-xl bg-violet-50 flex items-center justify-center text-violet-600 mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <h3 class="text-sm font-bold text-gray-900 mb-1">Módulos</h3>
                <p class="text-gray-500 text-xs flex-grow">Catálogo de projetos e módulos técnicos.</p>
                <div class="mt-3 text-violet-600 text-xs font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                    Gerenciar <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </div>
            </a>

            {{-- Departamentos --}}
            <a href="{{ route('departments.index') }}"
               class="group flex flex-col p-5 bg-white rounded-2xl border border-gray-100 hover:border-emerald-300 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <h3 class="text-sm font-bold text-gray-900 mb-1">Departamentos</h3>
                <p class="text-gray-500 text-xs flex-grow">Setores e áreas da organização.</p>
                <div class="mt-3 text-emerald-600 text-xs font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                    Gerenciar <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </div>
            </a>
        </div>
    </div>

    {{-- Helpdesk Section --}}
    <div>
        <h2 class="text-lg font-bold text-gray-800 mb-4">Helpdesk & CRM</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            {{-- CRM Feedback --}}
            <a href="{{ route('admin.crm.feedback.index') }}"
               class="group flex flex-col p-5 bg-white rounded-2xl border border-gray-100 hover:border-teal-300 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="w-11 h-11 rounded-xl bg-teal-50 flex items-center justify-center text-teal-600 mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-gray-900 mb-1">CRM Feedback</h3>
                <p class="text-gray-500 text-xs flex-grow">Formulários, elementos e configurações de feedback.</p>
                <div class="mt-3 text-teal-600 text-xs font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                    Acessar <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </div>
            </a>

            {{-- Ticketit Admin --}}
            <a href="{{ route('admin.helpdesk.dashboard') }}"
               class="group flex flex-col p-5 bg-white rounded-2xl border border-gray-100 hover:border-rose-300 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="w-11 h-11 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600 mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-gray-900 mb-1">Painel de Suporte</h3>
                <p class="text-gray-500 text-xs flex-grow">Painel do helpdesk, agentes e configurações de tickets.</p>
                <div class="mt-3 text-rose-600 text-xs font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                    Acessar <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </div>
            </a>
        </div>
    </div>

    {{-- Relatórios Section --}}
    <div>
        <h2 class="text-lg font-bold text-gray-800 mb-4">Relatórios & Analíticos</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            {{-- Relatório Carlos --}}
            <a href="{{ route('admin.tasks.report.carlos') }}"
               class="group flex flex-col p-5 bg-white rounded-2xl border border-gray-100 hover:border-blue-300 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                </div>
                <h3 class="text-sm font-bold text-gray-900 mb-1">Tarefas por Módulo</h3>
                <p class="text-gray-500 text-xs flex-grow">Visão gerencial consolidada das tarefas separadas por módulos do sistema.</p>
                <div class="mt-3 text-blue-600 text-xs font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                    Acessar <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
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
