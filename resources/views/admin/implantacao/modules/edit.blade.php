@extends('admin.layouts.master')

@section('title', "Implantação — Módulos de {$company->trade_name}")

@section('content')
<div class="max-w-2xl space-y-5">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
        <span class="text-gray-600 font-semibold">Implantação</span>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('admin.implantacao.modules.index') }}" class="hover:text-indigo-600 transition-colors">Módulos por Cliente</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-semibold">{{ $company->trade_name }}</span>
    </nav>

    @if($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
            <ul class="list-disc pl-4 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

        {{-- Header do card --}}
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h1 class="text-base font-black text-gray-900">{{ $company->trade_name }}</h1>
                <p class="text-[11px] text-gray-400 mt-0.5">
                    Selecione os módulos de implantação disponíveis. Deixar tudo desmarcado exibe todos os módulos (padrão).
                </p>
            </div>
        </div>

        <form action="{{ route('admin.implantacao.modules.update', $company->id) }}" method="POST" class="p-5 sm:p-6 space-y-6">
            @csrf
            @method('PUT')

            @forelse($allModules as $project => $modules)
                <div>
                    {{-- Grupo/Projeto --}}
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">{{ $project }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($modules as $module)
                            <label class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition-all
                                          {{ in_array($module->id, $assigned) ? 'border-indigo-300 bg-indigo-50' : 'border-gray-200 bg-gray-50 hover:border-indigo-200 hover:bg-indigo-50/40' }}
                                          group">
                                <input type="checkbox"
                                       name="module_ids[]"
                                       value="{{ $module->id }}"
                                       @checked(in_array($module->id, $assigned))
                                       class="w-4 h-4 text-indigo-600 bg-white border-gray-300 rounded focus:ring-indigo-500 focus:ring-2 transition-colors cursor-pointer">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-gray-800 truncate">{{ Str::upper($module->name) }}</p>
                                    @if($module->description)
                                        <p class="text-[11px] text-gray-500 truncate">{{ $module->description }}</p>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 py-4 text-center">Nenhum módulo técnico cadastrado. <a href="{{ route('admin.helpdesk.modules.index') }}" class="text-indigo-600 font-bold hover:underline">Cadastrar módulos</a>.</p>
            @endforelse

            {{-- Ações --}}
            <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-gray-100">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700
                               text-white rounded-xl font-bold text-sm shadow-md transition-all active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Salvar Configuração
                </button>
                <a href="{{ route('admin.implantacao.modules.index') }}"
                   class="text-sm font-semibold text-gray-500 hover:text-gray-700 transition-colors">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
