@extends('admin.layouts.master')

@section('title', $module ? 'Editar Módulo RAT' : 'Novo Módulo RAT')

@section('content')
<div class="max-w-2xl space-y-5" x-data="ratModuleForm()">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
        <span class="text-gray-600 font-semibold">Implantação</span>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('admin.implantacao.rat-modules.index') }}" class="hover:text-indigo-600 transition-colors">Módulos do RAT</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-semibold">{{ $module ? 'Editar' : 'Novo' }}</span>
    </nav>

    <form action="{{ $module ? route('admin.implantacao.rat-modules.update', $module->id) : route('admin.implantacao.rat-modules.store') }}"
          method="POST"
          class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">
        @csrf
        @if($module) @method('PUT') @endif

        <h2 class="text-lg font-black text-gray-900">{{ $module ? 'Editar Módulo' : 'Novo Módulo' }}</h2>

        {{-- Nome --}}
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nome do Módulo <span class="text-red-500">*</span></label>
            <input type="text" name="name" required maxlength="100"
                   value="{{ old('name', $module?->name) }}"
                   placeholder="Ex: Instalação, Vendas, Financeiro"
                   class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50">
            @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Projeto --}}
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Projeto</label>
            <div class="flex gap-2">
                <select x-model="projectSelect" @change="onProjectSelect"
                        class="flex-1 text-sm border border-gray-200 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50">
                    <option value="">— Novo projeto —</option>
                    @foreach($projects as $project)
                        <option value="{{ $project }}">{{ $project }}</option>
                    @endforeach
                </select>
                <input type="text" name="project" x-model="projectField" maxlength="100"
                       placeholder="Nome do projeto..."
                       class="flex-1 text-sm border border-gray-200 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50">
            </div>
            <p class="text-[11px] text-gray-400 mt-1">Selecione um projeto existente ou digite um novo. Ex: EasyMaster, EasyPDV, Geral.</p>
            @error('project') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Descrição --}}
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Descrição</label>
            <textarea name="description" rows="3" maxlength="255"
                      placeholder="Breve descrição do módulo (opcional)..."
                      class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50 resize-none">{{ old('description', $module?->description) }}</textarea>
            @error('description') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Ações --}}
        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md transition-all active:scale-95">
                {{ $module ? 'Salvar Alterações' : 'Criar Módulo' }}
            </button>
            <a href="{{ route('admin.implantacao.rat-modules.index') }}"
               class="text-sm font-semibold text-gray-500 hover:text-gray-700 transition-colors">
                Cancelar
            </a>
        </div>
    </form>
</div>

<script>
function ratModuleForm() {
    return {
        projectSelect: '{{ old('project', $module?->project ?? '') }}',
        projectField: '{{ old('project', $module?->project ?? '') }}',
        onProjectSelect() {
            this.projectField = this.projectSelect;
        },
    };
}
</script>
@endsection
