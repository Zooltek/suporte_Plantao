@extends('admin.layouts.master')

@section('title', $type ? 'Editar Tipo de Agendamento' : 'Novo Tipo de Agendamento')

@section('content')
<div class="max-w-2xl space-y-5">

    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
        <span class="text-gray-600 font-semibold">Implantação</span>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('admin.implantacao.schedule-types.index') }}" class="hover:text-indigo-600 transition-colors">Tipos de Agendamento</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-semibold">{{ $type ? 'Editar' : 'Novo' }}</span>
    </nav>

    @include('shared.errors')

    <form action="{{ $type ? route('admin.implantacao.schedule-types.update', $type->id) : route('admin.implantacao.schedule-types.store') }}"
          method="POST"
          class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">
        @csrf
        @if($type) @method('PUT') @endif

        <h2 class="text-lg font-black text-gray-900">{{ $type ? 'Editar Tipo' : 'Novo Tipo' }}</h2>

        @if($type?->is_system)
            <div class="rounded-xl bg-indigo-50 border border-indigo-200 p-3 text-xs text-indigo-800 font-semibold">
                Este é um tipo de sistema. Slug e inativação são protegidos para preservar integrações existentes.
            </div>
        @endif

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Rótulo exibido <span class="text-red-500">*</span></label>
            <input type="text" name="label" required maxlength="80"
                   value="{{ old('label', $type?->label) }}"
                   placeholder="Ex: Implantação, Reunião, Consulta médica"
                   class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50">
            @error('label') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Slug <span class="text-red-500">*</span></label>
            <input type="text" name="slug" maxlength="30"
                   value="{{ old('slug', $type?->slug) }}"
                   placeholder="Ex: implantacao"
                   @disabled($type?->is_system)
                   class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50 disabled:bg-gray-100 disabled:cursor-not-allowed font-mono">
            <p class="text-[11px] text-gray-400 mt-1">Identificador único usado internamente. Aceita letras, números, hífen e underline.</p>
            @error('slug') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Ordem</label>
                <input type="number" name="sort_order" min="0" max="9999"
                       value="{{ old('sort_order', $type?->sort_order ?? 100) }}"
                       class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50">
                @error('sort_order') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center pt-6">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="requires_customer" value="0">
                    <input type="checkbox" name="requires_customer" value="1"
                           @checked(old('requires_customer', $type?->requires_customer))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm font-semibold text-gray-700">Exige cliente</span>
                </label>
            </div>

            <div class="flex items-center pt-6">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $type?->is_active ?? true))
                           @disabled($type?->is_system)
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-50">
                    <span class="text-sm font-semibold text-gray-700">Ativo</span>
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md transition-all active:scale-95">
                {{ $type ? 'Salvar Alterações' : 'Criar Tipo' }}
            </button>
            <a href="{{ route('admin.implantacao.schedule-types.index') }}"
               class="text-sm font-semibold text-gray-500 hover:text-gray-700 transition-colors">Cancelar</a>
        </div>
    </form>
</div>
@endsection
