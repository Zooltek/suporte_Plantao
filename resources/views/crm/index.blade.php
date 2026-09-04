@extends('crm.layouts.master')

@section('title', 'CRM - Dashboard')

@section('content')

{{-- Welcome + KPI Section --}}
<div class="space-y-6 mb-8" style="animation: fadeInUp 0.5s ease-out forwards;">

    {{-- Welcome Card --}}
    <div class="relative overflow-hidden bg-white rounded-2xl p-7 border border-gray-100 shadow-xl shadow-gray-200/50">
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-gradient-to-br from-teal-400/30 to-cyan-400/30 blur-3xl rounded-full pointer-events-none"></div>
        <div class="absolute -bottom-20 -left-20 w-48 h-48 bg-gradient-to-tr from-emerald-400/20 to-teal-400/20 blur-3xl rounded-full pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-50 text-teal-600 text-sm font-medium mb-3 border border-teal-100">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-teal-500"></span>
                    </span>
                    CRM Ativo
                </div>
                <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight mb-1">
                    @php $crmUser = auth()->guard('admin')->user() ?? auth()->user(); @endphp
                    Olá, {{ explode(' ', $crmUser?->name ?? 'Usuário')[0] }}! 👋
                </h1>
                <p class="text-gray-600">
                    Gerencie feedbacks e acompanhe a satisfação dos seus clientes.
                </p>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

        {{-- Feedbacks Pendentes --}}
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded-full">Pendentes</span>
            </div>
            <p class="text-3xl font-extrabold text-gray-900">{{ $feedbacks->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">feedbacks aguardando</p>
        </div>

        {{-- Feedbacks Concluídos --}}
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full">Concluídos</span>
            </div>
            <p class="text-3xl font-extrabold text-gray-900">{{ $feedbacks_concluidos->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">feedbacks finalizados</p>
        </div>

        {{-- Novo Feedback --}}
        <a href="{{ route('feedback.create') }}"
           class="group bg-white rounded-xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-teal-300 transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-teal-50 flex items-center justify-center text-teal-600 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </div>
            </div>
            <p class="text-sm font-bold text-gray-900 group-hover:text-teal-700 transition-colors">Novo Feedback</p>
            <p class="text-xs text-gray-500 mt-1">Criar novo registro</p>
        </a>


    </div>
</div>

{{-- Feedbacks Section --}}
<div
    x-data="crmTabs({
        pendingCount: {{ $feedbacks->count() }},
        finishedCount: {{ $feedbacks_concluidos->count() }}
    })"
    class="w-full"
>
    
    <div class="bg-white dark:bg-slate-800 shadow-sm border border-gray-200 dark:border-slate-700 rounded-lg p-6 transition-colors duration-300">
        
        {{-- TOPO: Navegação e Ações --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            
            {{-- TABS (Navegação) --}}
            <div class="flex border-b border-gray-200 dark:border-slate-700 w-full md:w-auto">
                <button
                    @click="setTab('feedbacks')"
                    :class="isActive('feedbacks')
                        ? 'border-orange-500 text-orange-600 dark:text-orange-400 font-bold'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 font-medium'"
                    class="px-4 py-3 border-b-2 text-sm transition-all duration-200 flex items-center gap-2 -mb-[1px]">
                    Feedbacks
                    <span 
                        :class="isActive('feedbacks') ? 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-300'"
                        class="px-2 py-0.5 text-xs rounded-full transition-colors">
                        {{ $feedbacks->count() }}
                    </span>
                </button>
                
                <button
                    @click="setTab('concluidos')"
                    :class="isActive('concluidos')
                        ? 'border-orange-500 text-orange-600 dark:text-orange-400 font-bold'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 font-medium'"
                    class="px-4 py-3 border-b-2 text-sm transition-all duration-200 ml-4 flex items-center gap-2 -mb-[1px]">
                    Concluídos
                    <span
                        :class="isActive('concluidos') ? 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-300'"
                        class="px-2 py-0.5 text-xs rounded-full transition-colors">
                        {{ $feedbacks_concluidos->count() }}
                    </span>
                </button>
            </div>

            {{-- FILTROS E BOTÕES --}}
            <div class="flex flex-wrap items-center gap-3">
                
                {{-- Select Customizado com Ícone --}}
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-filter text-gray-400 dark:text-gray-500 group-focus-within:text-orange-500"></i>
                    </div>
                    {{-- 
                        Nota: Adicionei x-on:change para submeter o form ou filtrar via AJAX.
                        Se for recarregar a página, mantenha o name="form_id" e envolva num <form>.
                        Se for AJAX, use x-model.
                    --}}
                    <select
                        class="pl-10 pr-10 py-2 bg-gray-50 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 text-sm rounded-lg focus:ring-orange-500 focus:border-orange-500 block w-full appearance-none transition-shadow"
                        id="feedback-forms"
                        name="form_id"
                        onchange="changeFormFilter(this.value)">
                        @foreach($forms as $form)
                            <option value="{{ $form->id }}" @selected(isset($selected_form) && $selected_form->id === $form->id)>
                                {{ $form->name }}
                            </option>
                        @endforeach
                    </select>
                    {{-- Ícone Chevron (Tailwind puro) --}}
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>

                {{-- Botão Novo Feedback --}}
                <button
                    type="button"
                    @click="openCreateFeedback(@js(route('feedback.create')))"
                    class="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold rounded-lg transition-colors shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Novo Feedback
                </button>
            </div>
        </div>

        {{-- CONTEÚDO DAS ABAS --}}
        <div class="relative min-h-[200px]">
            
            {{-- Loading Overlay --}}
            <div x-show="isLoading" 
                 x-transition.opacity.duration.200ms
                 class="absolute inset-0 z-10 flex items-center justify-center bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm rounded-lg"
                 x-cloak>
                <div class="flex flex-col items-center">
                    <svg class="animate-spin h-8 w-8 text-orange-500 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Carregando...</span>
                </div>
            </div>

            {{-- Aba: Feedbacks Pendentes --}}
            <div x-show="!isLoading && isActive('feedbacks')"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-cloak>
                
                {{-- Container da Tabela --}}
                <div class="w-full overflow-x-auto rounded-lg border border-gray-200">
                    @if(view()->exists('crm.feedback.partials.table'))
                        @include('crm.feedback.partials.table', [
                            'feedbacks' => $feedbacks,
                            'type' => 'pending'
                        ])
                    @else
                        <div class="px-4 py-6 text-sm text-amber-700 bg-amber-50">
                            A visualização de feedbacks pendentes não está disponível neste ambiente.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Aba: Concluídos --}}
            <div x-show="!isLoading && isActive('concluidos')"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-cloak>
                
                <div class="w-full overflow-x-auto rounded-lg border border-gray-200">
                    @if(view()->exists('crm.feedback.partials.table-concluidos'))
                        @include('crm.feedback.partials.table-concluidos', [
                            'feedbacks' => $feedbacks_concluidos,
                            'type' => 'finished'
                        ])
                    @else
                        <div class="px-4 py-6 text-sm text-amber-700 bg-amber-50">
                            A visualização de feedbacks concluídos não está disponível neste ambiente.
                        </div>
                    @endif
                </div>
            </div>
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

@section('footer')
<script>
    function changeFormFilter(formId) {
        if (!formId) return;
        const url = new URL(window.location.href);
        url.searchParams.set('form_id', formId);
        window.location.href = url.toString();
    }
</script>
@endsection
