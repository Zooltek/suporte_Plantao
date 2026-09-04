@extends('admin.layouts.master')

@section('title', isset($module) ? "Editar Módulo: {$module->name}" : 'Novo Módulo')

@section('content')
<div class="max-w-lg space-y-5">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
        <a href="{{ route('admin.helpdesk.dashboard') }}" class="hover:text-indigo-600 transition-colors">Helpdesk</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('admin.helpdesk.modules.index') }}" class="hover:text-indigo-600 transition-colors">Módulos</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-semibold">{{ isset($module) ? 'Editar' : 'Novo' }}</span>
    </nav>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h1 class="text-base font-black text-gray-900">
                {{ isset($module) ? "Editar Módulo: {$module->name}" : 'Novo Módulo' }}
            </h1>
        </div>

        @if($errors->any())
            <div class="mx-5 mt-5 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ isset($module) ? route('admin.helpdesk.modules.update', $module->id) : route('admin.helpdesk.modules.store') }}"
              method="POST"
              class="p-5 sm:p-6 space-y-5">
            @csrf
            @isset($module)
                @method('PUT')
            @endisset

            {{-- Nome --}}
            <div>
                <label for="name" class="block text-xs font-bold text-gray-600 mb-2">
                    Nome <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       name="name"
                       id="name"
                       value="{{ old('name', $module->name ?? '') }}"
                       required
                       placeholder="Ex: Caixa Autom., Aplicativo, E-commerce..."
                       class="w-full px-4 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl outline-none
                              focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                @isset($module)
                    <p class="text-[10px] text-gray-400 mt-1.5">Slug atual: <code class="text-indigo-600">{{ $module->slug }}</code> (gerado automaticamente no cadastro)</p>
                @endisset
            </div>

            {{-- Template RAT --}}
            <div>
                <label for="rat_module_id" class="block text-xs font-bold text-gray-600 mb-2">
                    Template RAT (Checklist Técnico)
                </label>
                <select name="rat_module_id"
                        id="rat_module_id"
                        class="w-full px-4 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl outline-none
                               focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all cursor-pointer">
                    <option value="">— Sem checklist RAT —</option>
                    @foreach($ratModules as $rat)
                        <option value="{{ $rat->id }}"
                                @selected(old('rat_module_id', $module->rat_module_id ?? '') == $rat->id)>
                            {{ $rat->name }}
                            @if($rat->project) ({{ $rat->project }}) @endif
                            — {{ $rat->element_types_count }} item(ns)
                        </option>
                    @endforeach
                </select>
                <p class="text-[10px] text-gray-400 mt-1.5">
                    Ao vincular um template RAT, o agente verá um checklist padrão ao selecionar este módulo no chamado.
                    Se o módulo ficar sem vínculo, o ticket dependerá da seleção manual do checklist técnico.
                </p>
                @error('rat_module_id')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            @isset($module)
                @if(is_null($module->rat_module_id) && $module->companies_count > 0)
                    <div class="p-4 rounded-xl border border-amber-200 bg-amber-50 text-sm text-amber-800">
                        Este módulo já está vinculado a {{ $module->companies_count }} cliente(s) e não possui checklist RAT padrão.
                        No ticket, o agente precisará escolher o template técnico manualmente.
                    </div>
                @elseif($module->ratModule && ($module->ratModule->element_types_count ?? 0) === 0)
                    <div class="p-4 rounded-xl border border-amber-200 bg-amber-50 text-sm text-amber-800">
                        O template RAT vinculado não possui itens cadastrados. O checklist continuará vazio no ticket até que os itens sejam configurados.
                    </div>
                @endif
            @endisset

            {{-- Ordem --}}
            <div>
                <label for="sort_order" class="block text-xs font-bold text-gray-600 mb-2">
                    Ordem de Exibição
                </label>
                <input type="number"
                       name="sort_order"
                       id="sort_order"
                       value="{{ old('sort_order', $module->sort_order ?? 0) }}"
                       min="0"
                       placeholder="0"
                       class="w-32 px-4 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl outline-none
                              focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                <p class="text-[10px] text-gray-400 mt-1.5">Menor número = aparece primeiro na listagem.</p>
            </div>

            {{-- Status --}}
            <div>
                <label class="flex items-center gap-3 cursor-pointer select-none group">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $module->is_active ?? true))
                           class="sr-only peer">
                    <div class="relative w-10 h-6 bg-gray-200 peer-checked:bg-indigo-500 rounded-full transition-colors
                                after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full
                                after:w-5 after:h-5 after:transition-transform peer-checked:after:translate-x-4 shadow-inner"></div>
                    <div>
                        <p class="text-sm font-bold text-gray-700">Módulo Ativo</p>
                        <p class="text-[11px] text-gray-400">Disponível para vinculação em clientes</p>
                    </div>
                </label>
            </div>

            {{-- Ações --}}
            <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-gray-100">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700
                               text-white rounded-xl font-bold text-sm shadow-md transition-all active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ isset($module) ? 'Atualizar Módulo' : 'Criar Módulo' }}
                </button>
                <a href="{{ route('admin.helpdesk.modules.index') }}"
                   class="text-sm font-semibold text-gray-500 hover:text-gray-700 transition-colors">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
