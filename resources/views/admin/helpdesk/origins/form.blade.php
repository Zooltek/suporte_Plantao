@extends('admin.layouts.master')

@section('title', isset($origin) ? "Editar Origem: {$origin->name}" : 'Nova Origem')

@section('content')
<div class="max-w-lg space-y-5">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
        <a href="{{ route('admin.helpdesk.dashboard') }}" class="hover:text-indigo-600 transition-colors">Helpdesk</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('admin.helpdesk.origins.index') }}" class="hover:text-indigo-600 transition-colors">Origens</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-semibold">{{ isset($origin) ? 'Editar' : 'Nova' }}</span>
    </nav>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h1 class="text-base font-black text-gray-900">
                {{ isset($origin) ? "Editar Origem: {$origin->name}" : 'Nova Origem' }}
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

        <form action="{{ isset($origin) ? route('admin.helpdesk.origins.update', $origin->id) : route('admin.helpdesk.origins.store') }}"
              method="POST"
              class="p-5 sm:p-6 space-y-5">
            @csrf
            @isset($origin)
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
                       value="{{ old('name', $origin->name ?? '') }}"
                       required
                       placeholder="Ex: WhatsApp, Telefone, E-mail, Presencial..."
                       class="w-full px-4 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl outline-none
                              focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
            </div>

            {{-- Descrição --}}
            <div>
                <label for="description" class="block text-xs font-bold text-gray-600 mb-2">
                    Descrição <span class="text-gray-400 font-normal">(opcional)</span>
                </label>
                <input type="text"
                       name="description"
                       id="description"
                       value="{{ old('description', $origin->description ?? '') }}"
                       placeholder="Breve descrição do canal de origem..."
                       class="w-full px-4 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl outline-none
                              focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
            </div>

            {{-- Status --}}
            <div>
                <label class="flex items-center gap-3 cursor-pointer select-none group">
                    <input type="hidden" name="status" value="0">
                    <input type="checkbox" name="status" value="1"
                           @checked(old('status', $origin->status ?? true))
                           class="sr-only peer">
                    <div class="relative w-10 h-6 bg-gray-200 peer-checked:bg-indigo-500 rounded-full transition-colors
                                after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full
                                after:w-5 after:h-5 after:transition-transform peer-checked:after:translate-x-4 shadow-inner"></div>
                    <div>
                        <p class="text-sm font-bold text-gray-700">Origem Ativa</p>
                        <p class="text-[11px] text-gray-400">Disponível para seleção em novos chamados</p>
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
                    {{ isset($origin) ? 'Atualizar Origem' : 'Criar Origem' }}
                </button>
                <a href="{{ route('admin.helpdesk.origins.index') }}"
                   class="text-sm font-semibold text-gray-500 hover:text-gray-700 transition-colors">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
