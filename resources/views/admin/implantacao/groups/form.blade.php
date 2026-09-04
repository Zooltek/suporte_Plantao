@extends('admin.layouts.master')

@section('title', $group ? 'Editar Grupo RAT' : 'Novo Grupo RAT')

@section('content')
<div class="max-w-2xl space-y-5">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
        <span class="text-gray-600 font-semibold">Implantação</span>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('admin.implantacao.groups.index') }}" class="hover:text-indigo-600 transition-colors">Grupos do RAT</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-semibold">{{ $group ? 'Editar' : 'Novo' }}</span>
    </nav>

    <form action="{{ $group ? route('admin.implantacao.groups.update', $group->id) : route('admin.implantacao.groups.store') }}"
          method="POST"
          class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">
        @csrf
        @if($group) @method('PUT') @endif

        <h2 class="text-lg font-black text-gray-900">{{ $group ? 'Editar Grupo' : 'Novo Grupo' }}</h2>

        {{-- Nome --}}
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nome do Grupo <span class="text-red-500">*</span></label>
            <input type="text" name="name" required maxlength="100"
                   value="{{ old('name', $group?->name) }}"
                   placeholder="Ex: Instalação de Componentes Básicos"
                   class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50">
            <p class="text-[11px] text-gray-400 mt-1">Este nome será exibido como cabeçalho de seção no checklist do RAT.</p>
            @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Ações --}}
        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md transition-all active:scale-95">
                {{ $group ? 'Salvar Alterações' : 'Criar Grupo' }}
            </button>
            <a href="{{ route('admin.implantacao.groups.index') }}"
               class="text-sm font-semibold text-gray-500 hover:text-gray-700 transition-colors">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
