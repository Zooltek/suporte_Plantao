@extends('admin.layouts.master')

@section('title', isset($priority) ? "Editar Prioridade: {$priority->name}" : 'Nova Prioridade')

@section('content')
<div class="max-w-lg space-y-5">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
        <a href="{{ route('admin.helpdesk.dashboard') }}" class="hover:text-indigo-600 transition-colors">Helpdesk</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('admin.helpdesk.priority.index') }}" class="hover:text-amber-600 transition-colors">Prioridades</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-semibold">{{ isset($priority) ? 'Editar' : 'Nova' }}</span>
    </nav>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h1 class="text-base font-black text-gray-900">
                {{ isset($priority) ? "Editar Prioridade: {$priority->name}" : 'Nova Prioridade' }}
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

        <form action="{{ isset($priority) ? route('admin.helpdesk.priority.update', $priority->id) : route('admin.helpdesk.priority.store') }}"
              method="POST"
              class="p-5 sm:p-6 space-y-5"
              x-data="{ color: '{{ old('color', $priority->color ?? '#f59e0b') }}' }">
            @csrf
            @isset($priority)
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
                       value="{{ old('name', $priority->name ?? '') }}"
                       required
                       placeholder="Ex: Baixa, Média, Alta, Urgente..."
                       class="w-full px-4 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl outline-none
                              focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
            </div>

            {{-- Cor --}}
            <div>
                <label for="color" class="block text-xs font-bold text-gray-600 mb-2">
                    Cor <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-3">
                    <input type="color"
                           x-model="color"
                           name="color"
                           id="color"
                           class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5">
                    <input type="text"
                           x-model="color"
                           placeholder="#f59e0b"
                           class="flex-1 px-4 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl outline-none
                                  focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all font-mono">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold transition-all"
                          :style="`background-color: ${color}20; color: ${color}`"
                          x-text="color || 'Preview'"></span>
                </div>
            </div>

            {{-- Ações --}}
            <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-gray-100">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-600
                               text-white rounded-xl font-bold text-sm shadow-md transition-all active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ isset($priority) ? 'Atualizar Prioridade' : 'Criar Prioridade' }}
                </button>
                <a href="{{ route('admin.helpdesk.priority.index') }}"
                   class="text-sm font-semibold text-gray-500 hover:text-gray-700 transition-colors">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
